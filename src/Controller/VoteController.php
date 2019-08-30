<?php

namespace App\Controller;

use App\Entity\Contracts\VotableInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class VoteController extends AbstractController {
    /**
     * Vote on a votable entity.
     *
     * @IsGranted("ROLE_USER")
     */
    public function __invoke(ObjectManager $em, Request $request, string $entityClass, int $id): Response {
        $this->validateCsrf('vote', $request->request->get('token'));

        $choice = $request->request->getInt('choice', null);

        $votable = $em->find($entityClass, $id);

        if (!$votable instanceof VotableInterface) {
            throw $this->createNotFoundException('Entity not found');
        }

        $votable->vote($choice, $this->getUser(), $request->getClientIp());

        $em->flush();

        if ($request->getRequestFormat() === 'json') {
            return $this->json(['message' => 'successful vote']);
        }

        if (!$request->headers->has('Referer')) {
            return $this->redirectToRoute('front');
        }

        return $this->redirect($request->headers->get('Referer'));
    }
}
