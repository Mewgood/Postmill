<?php

/** @noinspection PhpUnusedParameterInspection */

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Forum;
use App\Entity\ForumLogCommentDeletion;
use App\Entity\Submission;
use App\Entity\User;
use App\Event\EditCommentEvent;
use App\Event\NewCommentEvent;
use App\Form\CommentType;
use App\Form\Model\CommentData;
use App\Repository\CommentRepository;
use App\Repository\ForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @var ForumRepository
     */
    private $forums;

    public function __construct(
        CommentRepository $comments,
        EntityManagerInterface $entityManager,
        ForumRepository $forums
    ) {
        $this->comments = $comments;
        $this->entityManager = $entityManager;
        $this->forums = $forums;
    }

    public function list(int $page): Response {
        return $this->render('comment/list.html.twig', [
            'comments' => $this->comments->findRecentPaginated($page),
        ]);
    }

    /**
     * Render the comment form only (no layout).
     */
    public function commentForm(string $forumName, int $submissionId, int $commentId = null): Response {
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
     * Submit a comment.
     *
     * @IsGranted("ROLE_USER")
     */
    public function comment(Forum $forum, Submission $submission, ?Comment $comment, Request $request): Response {
        $name = $this->getFormName($submission, $comment);
        $data = new CommentData($submission);

        $form = $this->createNamedForm($name, CommentType::class, $data, [
            'forum' => $forum,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reply = $data->toComment($this->getUser(), $request->getClientIp());

            if ($comment) {
                $comment->addReply($reply);
            }

            $this->entityManager->persist($reply);
            $this->entityManager->flush();

            $this->dispatchEvent(new NewCommentEvent($reply));

            return $this->redirect($this->generateCommentUrl($reply));
        }

        return $this->render('comment/form_errors.html.twig', [
            'comment' => $comment,
            'editing' => false,
            'form' => $form->createView(),
            'forum' => $forum,
            'submission' => $submission,
        ]);
    }

    public function commentJson(Forum $forum, Submission $submission, Comment $comment): Response {
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
    public function editComment(Forum $forum, Submission $submission, Comment $comment, Request $request): Response {
        $data = CommentData::createFromComment($comment);

        $form = $this->createForm(CommentType::class, $data, ['forum' => $forum]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $before = clone $comment;
            $data->updateComment($comment, $this->getUser());

            $this->entityManager->flush();

            $this->dispatchEvent(new EditCommentEvent($before, $comment));

            return $this->redirect($this->generateCommentUrl($comment));
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

        $this->entityManager->flush();

        return $this->redirectAfterDelete($request);
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

        return $this->redirectAfterDelete($request);
    }

    private function logDeletion(Forum $forum, Comment $comment): void {
        /* @var User $user */
        $user = $this->getUser();

        if ($user !== $comment->getUser()) {
            $forum->addLogEntry(new ForumLogCommentDeletion($comment, $user));
        }
    }

    private function redirectAfterDelete(Request $request): Response {
        $url = $request->headers->get('Referer', '');
        preg_match('!/f/[^/]++/\d+/[^/]++/comment/(\d+)!', $url, $matches);

        if (!$url || $request->attributes->get('comment_id') === ($matches[1] ?? '')) {
            $url = $this->generateSubmissionUrl($request->attributes->get('submission'));
        }

        return $this->redirect($url);
    }

    /**
     * @param Submission|int   $submission
     * @param Comment|int|null $comment
     */
    private function getFormName($submission, $comment): string {
        $submissionId = $submission instanceof Submission ? $submission->getId() : $submission;
        $commentId = $comment instanceof Comment ? $comment->getId() : $comment;

        return isset($commentId)
            ? 'reply_to_comment_'.$commentId
            : 'reply_to_submission_'.$submissionId;
    }
}
