<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\DataObject\UserData;
use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/users", defaults={"_format": "json"})
 */
final class UserController extends AbstractController {
    /**
     * @Route("/{id}", methods={"GET"}, requirements={"id": "%number_regex%"})
     */
    public function read(User $user): Response {
        return $this->json($user, 200, [], [
            'groups' => ['user:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * @Route("/self", methods={"GET"})
     */
    public function self(): Response {
        return $this->json($this->getUser(), 200, [], [
            'groups' => ['abbreviated_relations'],
        ]);
    }

    /**
     * @Route("/{id}/preferences", methods={"GET"})
     * @IsGranted("edit_user", subject="user")
     */
    public function readPreferences(User $user): Response {
        return $this->json($user, 200, [], [
            'groups' => ['user:preferences'],
        ]);
    }

    /**
     * @Route("/{id}/preferences", methods={"PUT"})
     * @IsGranted("edit_user", subject="user")
     */
    public function updatePreferences(User $user, ObjectManager $em): Response {
        return $this->apiUpdate(new UserData($user), UserData::class, [
            'normalization_groups' => ['user:preferences'],
            'denormalization_groups' => ['user:preferences'],
            'validation_groups' => ['settings'],
        ], function (UserData $data) use ($em, $user) {
            $data->updateUser($user);

            $em->flush();
        });
    }
}
