<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\DataObject\ForumData;
use App\Entity\Forum;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/forums", defaults={"_format": "json"})
 */
class ForumController extends AbstractController {
    /**
     * @Route("/{id}", methods={"GET"})
     */
    public function read(Forum $forum): Response {
        return $this->json($forum, 200, [], [
            'groups' => ['forum:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * @Route("", methods={"POST"})
     * @IsGranted("create_forum")
     */
    public function create(ObjectManager $em): Response {
        return $this->apiCreate(ForumData::class, [
            'normalization_groups' => ['forum:read', 'abbreviated_relations'],
            'denormalization_groups' => ['forum:create'],
            'validation_groups' => ['create'],
        ], function (ForumData $data) use ($em) {
            $forum = $data->toForum($this->getUser());

            $em->persist($forum);
            $em->flush();

            return $forum;
        });
    }

    /**
     * @Route("/{id}", methods={"PUT"})
     * @IsGranted("moderator", subject="forum")
     */
    public function update(Forum $forum, ObjectManager $em): Response {
        return $this->apiUpdate($forum, ForumData::class, [
            'normalization_groups' => ['forum:read'],
            'denormalization_groups' => ['forum:update'],
            'validation_groups' => ['update'],
        ], function (ForumData $data) use ($forum, $em) {
            $data->updateForum($forum);

            $em->flush();
        });
    }
}
