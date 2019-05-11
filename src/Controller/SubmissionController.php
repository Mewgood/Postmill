<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Forum;
use App\Entity\ForumLogSubmissionDeletion;
use App\Entity\ForumLogSubmissionLock;
use App\Entity\Submission;
use App\Event\EntityModifiedEvent;
use App\Events;
use App\Form\DeleteReasonType;
use App\Form\Model\SubmissionData;
use App\Form\SubmissionType;
use App\Repository\CommentRepository;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Entity("forum", expr="repository.findOneOrRedirectToCanonical(forum_name, 'forum_name')")
 * @Entity("submission", expr="repository.findOneBy({forum: forum, id: submission_id})")
 * @Entity("comment", expr="repository.findOneBy({submission: submission, id: comment_id})")
 */
final class SubmissionController extends AbstractController {
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

    public function __construct(
        CommentRepository $comments,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->comments = $comments;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Show a submission's comment page.
     *
     * @Cache(smaxage="10 seconds")
     */
    public function submission(Forum $forum, Submission $submission): Response {
        $this->comments->hydrate(...$submission->getComments());

        return $this->render('submission/submission.html.twig', [
            'forum' => $forum,
            'submission' => $submission,
        ]);
    }

    public function submissionJson(Forum $forum, Submission $submission): Response {
        return $this->json($submission, 200, [], [
            'groups' => ['submission:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * Show a single comment and its replies.
     */
    public function commentPermalink(Forum $forum, Submission $submission, Comment $comment): Response {
        $this->comments->hydrate(...$submission->getComments());

        return $this->render('submission/comment.html.twig', [
            'comment' => $comment,
            'forum' => $forum,
            'submission' => $submission,
        ]);
    }

    /**
     * @Entity("submission", expr="repository.find(id)")
     */
    public function shortcut(Submission $submission): Response {
        return $this->redirectToRoute('submission', [
            'forum_name' => $submission->getForum()->getName(),
            'submission_id' => $submission->getId(),
            'slug' => Slugger::slugify($submission->getTitle()),
        ]);
    }

    /**
     * Create a new submission.
     *
     * @IsGranted("ROLE_USER")
     */
    public function submit(Request $request, ?Forum $forum): Response {
        $data = new SubmissionData($forum);

        $form = $this->createForm(SubmissionType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $submission = $data->toSubmission($this->getUser(), $request->getClientIp());

            $this->entityManager->persist($submission);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(Events::NEW_SUBMISSION, new GenericEvent($submission));

            return $this->redirectToRoute('submission', [
                'forum_name' => $submission->getForum()->getName(),
                'submission_id' => $submission->getId(),
                'slug' => Slugger::slugify($submission->getTitle()),
            ]);
        }

        return $this->render('submission/create.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit", subject="submission", statusCode=403)
     */
    public function editSubmission(Forum $forum, Submission $submission, Request $request): Response {
        $data = SubmissionData::createFromSubmission($submission);

        $form = $this->createForm(SubmissionType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $before = clone $submission;
            $data->updateSubmission($submission, $this->getUser());

            $this->entityManager->flush();

            $this->addFlash('notice', 'flash.submission_edited');

            $event = new EntityModifiedEvent($before, $submission);
            $this->eventDispatcher->dispatch(Events::EDIT_SUBMISSION, $event);

            return $this->redirectToRoute('submission', [
                'forum_name' => $forum->getName(),
                'submission_id' => $submission->getId(),
                'slug' => Slugger::slugify($submission->getTitle()),
            ]);
        }

        return $this->render('submission/edit.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
            'submission' => $submission,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Security("is_granted(purge ? 'purge' : 'mod_delete', submission)", statusCode=403)
     */
    public function modDelete(Request $request, Forum $forum, Submission $submission, bool $purge): Response {
        $form = $this->createForm(DeleteReasonType::class, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forum->addLogEntry(new ForumLogSubmissionDeletion(
                $submission,
                $this->getUser(),
                $form->getData()['reason']
            ));

            if ($purge || $submission->getCommentCount() === 0) {
                $this->entityManager->remove($submission);
            } else {
                $submission->softDelete();
            }

            $this->entityManager->flush();

            $this->addFlash('notice', 'flash.submission_deleted');

            return $this->redirectToRoute('forum', [
                'forum_name' => $forum->getName(),
            ]);
        }

        return $this->render('submission/delete_with_reason.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
            'purge' => $purge,
            'submission' => $submission,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("delete_own", subject="submission", statusCode=403)
     */
    public function deleteOwn(Request $request, Forum $forum, Submission $submission): Response {
        $this->validateCsrf('delete_submission', $request->request->get('token'));

        if ($submission->getCommentCount() > 0) {
            $submission->softDelete();
        } else {
            $this->entityManager->remove($submission);
        }

        $this->entityManager->flush();

        $this->addFlash('notice', 'flash.submission_deleted');

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirectToRoute('forum', ['forum_name' => $forum->getName()]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("lock", subject="submission", statusCode=403)
     */
    public function lock(Request $request, Forum $forum, Submission $submission, bool $lock): Response {
        $this->validateCsrf('lock', $request->request->get('token'));

        $submission->setLocked($lock);

        $this->entityManager->persist(new ForumLogSubmissionLock($submission, $this->getUser(), $lock));
        $this->entityManager->flush();

        if ($lock) {
            $this->addFlash('success', 'flash.submission_locked');
        } else {
            $this->addFlash('success', 'flash.submission_unlocked');
        }

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirectToRoute('submission', [
            'forum_name' => $forum->getName(),
            'submission_id' => $submission->getId(),
            'slug' => Slugger::slugify($submission->getTitle()),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("pin", subject="submission", statusCode=403)
     */
    public function pin(Request $request, Forum $forum, Submission $submission, bool $pin): Response {
        $this->validateCsrf('pin', $request->request->get('token'));

        $submission->setSticky($pin);

        $this->entityManager->flush();

        if ($pin) {
            $this->addFlash('notice', 'flash.submission_pinned');
        } else {
            $this->addFlash('notice', 'flash.submission_unpinned');
        }

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirectToRoute('submission', [
            'forum_name' => $forum->getName(),
            'submission_id' => $submission->getId(),
            'slug' => Slugger::slugify($submission->getTitle()),
        ]);
    }
}
