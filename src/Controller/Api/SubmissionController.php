<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\DataObject\SubmissionData;
use App\Entity\Submission;
use App\Event\DeleteSubmissionEvent;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/submissions", defaults={"_format": "json"})
 */
final class SubmissionController extends AbstractController {
    /**
     * @Route("/{id}", methods={"GET"})
     */
    public function read(Submission $submission): Response {
        return $this->json($submission, 200, [], [
            'groups' => ['submission:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * @Route("", methods={"POST"})
     */
    public function create(ObjectManager $em, Request $request): Response {
        return $this->apiCreate(SubmissionData::class, [
            'normalization_groups' => ['submission:read', 'abbreviated_relations'],
            'denormalization_groups' => ['submission:create'],
            'validation_groups' => ['create'],
        ], function (SubmissionData $data) use ($em, $request) {
            $submission = $data->toSubmission($this->getUser(), $request->getClientIp());

            $em->persist($submission);
            $em->flush();

            return $submission;
        });
    }

    /**
     * @Route("/{id}", methods={"PUT"})
     * @IsGranted("edit", subject="submission")
     */
    public function update(Submission $submission, ObjectManager $em): Response {
        $data = new SubmissionData($submission);

        return $this->apiUpdate($data, SubmissionData::class, [
            'normalization_groups' => ['submission:read'],
            'denormalization_groups' => ['submission:update'],
            'validation_groups' => ['update'],
        ], function (SubmissionData $data) use ($em, $submission) {
            $data->updateSubmission($submission, $this->getUser());

            $em->flush();
        });
    }

    /**
     * @Route("/{id}", methods={"DELETE"})
     * @IsGranted("delete_own", subject="submission")
     */
    public function delete(Submission $submission, ObjectManager $em): Response {
        if ($submission->getCommentCount() > 0) {
            $submission->softDelete();
        } else {
            $em->remove($submission);
        }

        $em->flush();
        $this->dispatchEvent(new DeleteSubmissionEvent($submission));

        return $this->createEmptyResponse();
    }
}
