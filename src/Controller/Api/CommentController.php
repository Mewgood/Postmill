<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\DataObject\CommentData;
use App\Entity\Comment;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/comments", defaults={"_format": "json"})
 */
final class CommentController extends AbstractController {
    /**
     * @Route("/{id}", methods={"GET"})
     */
    public function read(Comment $comment): Response {
        return $this->json($comment, 200, [], ['groups' => ['comment:read']]);
    }

    /**
     * @Route("/{id}", methods={"PUT"})
     * @IsGranted("edit", subject="comment")
     */
    public function update(Comment $comment, ObjectManager $em): Response {
        return $this->apiUpdate($comment, CommentData::class, [
            'normalization_groups' => ['comment:read'],
            'denormalization_groups' => ['comment:update']
        ], function (CommentData $data) use ($comment, $em) {
            $data->updateComment($comment, $this->getUser());

            $em->flush();
        });
    }
}
