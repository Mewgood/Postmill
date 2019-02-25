<?php

namespace App\Controller;

use App\Entity\MessageThread;
use App\Entity\User;
use App\Form\MessageReplyType;
use App\Form\MessageThreadType;
use App\Form\Model\MessageData;
use App\Repository\MessageThreadRepository;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MessageController extends AbstractController {
    /**
     * @IsGranted("ROLE_USER")
     *
     * @param MessageThreadRepository $repository
     * @param int                     $page
     *
     * @return Response
     */
    public function list(MessageThreadRepository $repository, int $page) {
        $messages = $repository->findUserMessages($this->getUser(), $page);

        return $this->render('message/list.html.twig', [
            'messages' => $messages,
        ]);
    }

    /**
     * Start a new message thread.
     *
     * @IsGranted("ROLE_USER")
     * @IsGranted("message", subject="receiver", statusCode=403)
     * @Entity("receiver", expr="repository.findOneOrRedirectToCanonical(username, 'username')")
     *
     * @param Request       $request
     * @param EntityManager $em
     * @param User          $receiver
     *
     * @return Response
     */
    public function compose(Request $request, EntityManager $em, User $receiver) {
        $data = new MessageData($this->getUser(), $request->getClientIp());

        $form = $this->createForm(MessageThreadType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $thread = $data->toThread($receiver);

            $em->persist($thread);
            $em->flush();

            return $this->redirectToRoute('message', [
                'id' => $thread->getId(),
            ]);
        }

        return $this->render('message/compose.html.twig', [
            'form' => $form->createView(),
            'receiver' => $receiver,
        ]);
    }

    /**
     * View a message thread.
     *
     * @IsGranted("ROLE_USER")
     * @IsGranted("access", subject="thread", statusCode=403)
     *
     * @param MessageThread $thread
     *
     * @return Response
     */
    public function message(MessageThread $thread) {
        return $this->render('message/message.html.twig', [
            'thread' => $thread,
        ]);
    }

    public function replyForm($threadId) {
        $form = $this->createForm(MessageReplyType::class, null, [
            'action' => $this->generateUrl('reply_to_message', [
                'id' => $threadId,
            ]),
        ]);

        return $this->render('message/reply_form_fragment.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("reply", subject="thread", statusCode=40333)
     *
     * @param Request       $request
     * @param EntityManager $em
     * @param MessageThread $thread
     *
     * @return Response
     */
    public function reply(Request $request, EntityManager $em, MessageThread $thread) {
        $data = new MessageData($this->getUser(), $request->getClientIp());

        $form = $this->createForm(MessageReplyType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $thread->addReply($data->toReply($thread));

            $em->flush();

            return $this->redirectToRoute('message', [
                'id' => $thread->getId(),
            ]);
        }

        return $this->render('message/reply_errors.html.twig', [
            'form' => $form->createView(),
            'thread' => $thread,
        ]);
    }
}
