<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Forum;
use App\Entity\ForumLogCommentDeletion;
use App\Entity\Submission;
use App\Entity\User;
use App\Event\EntityModifiedEvent;
use App\Events;
use App\Form\CommentType;
use App\Form\Model\CommentData;
use App\Repository\CommentRepository;
use App\Repository\ForumRepository;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Entity("forum", expr="repository.findOneOrRedirectToCanonical(forum_name, 'forum_name')")
 * @Entity("submission", expr="repository.findOneBy({forum: forum, id: submission_id})")
 * @Entity("comment", expr="repository.findOneBySubmissionAndIdOr404(submission, comment_id)")
 */
final class CommentController extends AbstractController {
    /**
     * @var CommentRepository
     */
    private $comments;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ForumRepository
     */
    private $forums;

    public function __construct(
        CommentRepository $comments,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        ForumRepository $forums
    ) {
        $this->comments = $comments;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->forums = $forums;
    }

    public function list(int $page) {
        return $this->render('comment/list.html.twig', [
            'comments' => $this->comments->findRecentPaginated($page),
        ]);
    }

    /**
     * Render the comment form only (no layout).
     */
    public function commentForm($forumName, $submissionId, $commentId = null): Response {
        $routeParams = [
            'forum_name' => $forumName,
            'submission_id' => $submissionId,
        ];

        if ($commentId !== null) {
            $routeParams['comment_id'] = $commentId;
        }

        $name = $this->getFormName($submissionId, $commentId);

        $form = $this->createNamedForm($name, CommentType::class, null, [
            'action' => $this->generateUrl('comment_post', $routeParams),
            'forum' => $this->forums->findOneByCaseInsensitiveName($forumName),
        ]);

        return $this->render('comment/form_fragment.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Submit a comment. This is intended for users without JS enabled.
     *
     * @IsGranted("ROLE_USER")
     */
    public function comment(Forum $forum, Submission $submission, ?Comment $comment, Request $request) {
        $name = $this->getFormName($submission, $comment);
        $data = new CommentData($submission);

        $form = $this->createNamedForm($name, CommentType::class, $data, [
            'forum' => $forum,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reply = $data->toComment($this->getUser(), $comment, $request->getClientIp());

            $this->entityManager->persist($reply);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(Events::NEW_COMMENT, new GenericEvent($reply));

            return $this->redirectToRoute('comment', [
                'forum_name' => $forum->getName(),
                'submission_id' => $submission->getId(),
                'comment_id' => $reply->getId(),
                'slug' => Slugger::slugify($submission->getTitle()),
            ]);
        }

        return $this->render('comment/form_errors.html.twig', [
            'comment' => $comment,
            'editing' => false,
            'form' => $form->createView(),
            'forum' => $forum,
            'submission' => $submission,
        ]);
    }

    public function commentJson(Forum $forum, Submission $submission, Comment $comment) {
        return $this->json($comment, 200, [], [
            'groups' => ['comment:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * Edits a comment.
     *
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit", subject="comment", statusCode=403)
     */
    public function editComment(Forum $forum, Submission $submission, Comment $comment, Request $request) {
        $data = CommentData::createFromComment($comment);

        $form = $this->createForm(CommentType::class, $data, ['forum' => $forum]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $before = clone $comment;
            $data->updateComment($comment, $this->getUser());

            $this->entityManager->flush();

            $event = new EntityModifiedEvent($before, $comment);
            $this->eventDispatcher->dispatch(Events::EDIT_COMMENT, $event);

            return $this->redirectToRoute('comment', [
                'forum_name' => $forum->getName(),
                'submission_id' => $submission->getId(),
                'comment_id' => $comment->getId(),
            ]);
        }

        return $this->render('comment/form_errors.html.twig', [
            'editing' => true,
            'form' => $form->createView(),
            'forum' => $forum,
            'submission' => $submission,
            'comment' => $comment,
        ]);
    }

    /**
     * Delete a comment thread.
     *
     * @IsGranted("ROLE_USER")
     * @IsGranted("delete_thread", subject="comment", statusCode=403)
     */
    public function deleteComment(Submission $submission, Forum $forum, Comment $comment, Request $request): Response {
        $this->validateCsrf('delete_comment', $request->request->get('token'));

        $submission->removeComment($comment);
        $this->entityManager->remove($comment);

        $this->logDeletion($forum, $comment);

        $commentId = $comment->getId(); // not available on entity after flush()

        $this->entityManager->flush();

        if ($request->headers->has('Referer')) {
            $commentUrl = $this->generateUrl('comment', [
                'forum_name' => $forum->getName(),
                'submission_id' => $submission->getId(),
                'comment_id' => $commentId,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            if (strpos($request->headers->get('Referer'), $commentUrl) === 0) {
                // redirect to forum since redirect to referrer will 404
                return $this->redirectToRoute('forum', [
                    'forum_name' => $forum->getName(),
                ]);
            }
        }

        return $this->redirectAfterAction($comment, $request);
    }

    /**
     * "Soft deletes" a comment by blanking its body.
     *
     * @IsGranted("ROLE_USER")
     * @IsGranted("softdelete", subject="comment", statusCode=403)
     */
    public function softDeleteComment(Forum $forum, Submission $submission, Comment $comment, Request $request): Response {
        $this->validateCsrf('softdelete_comment', $request->request->get('token'));

        $comment->softDelete();

        $this->logDeletion($forum, $comment);

        $this->entityManager->flush();

        return $this->redirectAfterAction($comment, $request);
    }

    private function logDeletion(Forum $forum, Comment $comment) {
        /* @var User $user */
        $user = $this->getUser();

        if ($user !== $comment->getUser()) {
            $forum->addLogEntry(new ForumLogCommentDeletion($comment, $user));
        }
    }

    private function redirectAfterAction(Comment $comment, Request $request): Response {
        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirectToRoute('submission', [
            'forum_name' => $comment->getSubmission()->getForum()->getName(),
            'submission_id' => $comment->getSubmission()->getId(),
            'slug' => Slugger::slugify($comment->getSubmission()->getTitle()),
        ]);
    }

    private function getFormName($submission, $comment): string {
        $submissionId = $submission instanceof Submission ? $submission->getId() : $submission;
        $commentId = $comment instanceof Comment ? $comment->getId() : $comment;

        return isset($commentId)
            ? 'reply_to_comment_'.$commentId
            : 'reply_to_submission_'.$submissionId;
    }
}
