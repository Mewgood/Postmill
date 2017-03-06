<?php

namespace Raddit\AppBundle\Controller;

use Raddit\AppBundle\Entity\Forum;
use Raddit\AppBundle\Entity\Moderator;
use Raddit\AppBundle\Form\ForumType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ForumController extends Controller {
    /**
     * Create a new forum.
     *
     * @Security("is_granted('ROLE_USER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createForumAction(Request $request) {
        $forum = new Forum();

        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $moderator = new Moderator();
            $moderator->setUser($this->getUser());
            $moderator->setForum($forum);

            $forum->setModerators([$moderator]);

            $em = $this->getDoctrine()->getManager();

            $em->persist($forum);
            $em->flush();

            return $this->redirectToRoute('raddit_app_forum', [
                'forum_name' => $forum->getName(),
            ]);
        }

        return $this->render('@RadditApp/create-forum.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @ParamConverter("forum", options={"mapping": {"forum_name": "name"}})
     *
     * @Security("is_granted('edit', forum)")
     *
     * @param Request $request
     * @param Forum   $forum
     *
     * @return Response
     */
    public function editForumAction(Request $request, Forum $forum) {
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'edit_forum.edit_notice');

            return $this->redirect($request->getUri());
        }

        return $this->render('@RadditApp/edit-forum.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
        ]);
    }
}
