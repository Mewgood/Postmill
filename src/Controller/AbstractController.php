<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Submission;
use App\Utils\Slugger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class AbstractController extends BaseAbstractController {
    public static function getSubscribedServices(): array {
        return [
            'event_dispatcher' => EventDispatcherInterface::class,
        ] + parent::getSubscribedServices();
    }

    /**
     * @param string|mixed $token
     *
     * @throws BadRequestHttpException if the token isn't valid
     */
    protected function validateCsrf(string $id, $token): void {
        if (!\is_string($token) || !$this->isCsrfTokenValid($id, $token)) {
            throw new BadRequestHttpException('Invalid CSRF token');
        }
    }

    protected function createNamedForm(
        $name,
        $type = FormType::class,
        $data = null,
        array $options = []
    ): FormInterface {
        return $this->container
            ->get('form.factory')
            ->createNamed($name, $type, $data, $options);
    }

    protected function dispatchEvent(Event $event): Event {
        return $this->container->get('event_dispatcher')->dispatch($event);
    }

    protected function generateSubmissionUrl(Submission $submission): string {
        $id = $submission->getId();

        if (!$id) {
            throw new \InvalidArgumentException('Cannot redirect to non-persisted submission');
        }

        return $this->generateUrl('submission', [
            'forum_name' => $submission->getForum()->getName(),
            'submission_id' => $id,
            'slug' => Slugger::slugify($submission->getTitle()),
        ]);
    }

    protected function generateCommentUrl(Comment $comment): string {
        $id = $comment->getId();

        if (!$id) {
            throw new \InvalidArgumentException('Cannot redirect to non-persisted comment');
        }

        return $this->generateUrl('comment', [
            'forum_name' => $comment->getSubmission()->getForum()->getName(),
            'submission_id' => $comment->getSubmission()->getId(),
            'slug' => Slugger::slugify($comment->getSubmission()->getTitle()),
            'comment_id' => $comment->getId(),
        ]);
    }
}
