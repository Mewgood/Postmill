<?php

namespace App\Controller;

use App\Entity\Votable;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class VoteController extends AbstractController {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * Vote on a votable entity.
     *
     * @IsGranted("ROLE_USER")
     */
    public function __invoke(Request $request, $entityClass, $id, $_format): Response {
        $this->validateCsrf('vote', $request->request->get('token'));

        $choice = $request->request->getInt('choice', null);

        if (!in_array($choice, Votable::VOTE_CHOICES, true)) {
            throw new BadRequestHttpException('Bad choice');
        }

        $entity = $this->entityManager->find($entityClass, $id);

        if (!$entity instanceof Votable) {
            throw $this->createNotFoundException('Entity not found');
        }

        $entity->vote($this->getUser(), $request->getClientIp(), $choice);

        $this->entityManager->flush();

        if ($_format === 'json') {
            return $this->json(['message' => 'successful vote']);
        }

        if (!$request->headers->has('Referer')) {
            return $this->redirectToRoute('front');
        }

        return $this->redirect($request->headers->get('Referer'));
    }
}
