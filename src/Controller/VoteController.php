<?php

namespace App\Controller;

use App\Entity\Contracts\VotableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class VoteController extends AbstractController {
    /**
     * Vote on a votable entity.
     *
     * @IsGranted("ROLE_USER")
     */
    public function __invoke(EntityManagerInterface $em, Request $request, string $entityClass, int $id): Response {
        $this->validateCsrf('vote', $request->request->get('token'));

        $choice = (int) $request->request->get('choice');

        $votable = $em->find($entityClass, $id);

        if (!$votable instanceof VotableInterface) {
            throw $this->createNotFoundException('Entity not found');
        }

        $em->transactional(function () use ($votable, $choice, $request): void {
            $votable->vote($choice, $this->getUser(), $request->getClientIp());
        });

        if ($request->getRequestFormat() === 'json') {
            return $this->json(['netScore' => $votable->getNetScore()]);
        }

        if (!$request->headers->has('Referer')) {
            return $this->redirectToRoute('front');
        }

        return $this->redirect($request->headers->get('Referer'));
    }
}
