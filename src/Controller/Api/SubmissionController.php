<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\DataObject\SubmissionData;
use App\Entity\Submission;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\SubmissionFinder;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/submissions", defaults={"_format": "json"}, requirements={"id": "%number_regex%"})
 */
final class SubmissionController extends AbstractController {
    /**
     * @Route("", methods={"GET"})
     */
    public function list(Request $request, SubmissionFinder $finder): Response {
        \assert($this->getUser() !== null);

        $sortBy = $request->query->get('sortBy', $this->getUser()->getFrontPageSortMode());

        if (!\in_array($sortBy, Submission::SORT_OPTIONS, true)) {
            return $this->json(['message' => 'unknown sort mode'], 400);
        }

        $criteria = new Criteria($sortBy, $this->getUser());

        switch ($request->query->get('filter', $this->getUser()->getFrontPage())) {
        case Submission::FRONT_FEATURED:
            $criteria->showFeatured()->excludeHiddenForums();
            break;
        case Submission::FRONT_SUBSCRIBED:
            $criteria->showSubscribed();
            break;
        case Submission::FRONT_MODERATED:
            $criteria->showModerated();
            break;
        case Submission::FRONT_ALL:
            $criteria->excludeHiddenForums();
            break;
        default:
            return $this->json(['message' => 'unknown filter mode', 400]);
        }

        return $this->json($finder->find($criteria), 200, [], [
            'groups' => ['submission:read', 'abbreviated_relations'],
        ]);
    }

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
    public function create(EntityManagerInterface $em, Request $request): Response {
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
    public function update(Submission $submission, EntityManagerInterface $em): Response {
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
    public function delete(Submission $submission, EntityManagerInterface $em): Response {
        if ($submission->getCommentCount() > 0) {
            $submission->softDelete();
        } else {
            $em->remove($submission);
        }

        $em->flush();

        return $this->createEmptyResponse();
    }

    /**
     * @Route("/{id}/comments", methods={"GET"})
     */
    public function comments(Submission $submission): Response {
        return $this->json($submission->getTopLevelComments(), 200, [], [
            'groups' => ['comment:read', 'comment:nested', 'abbreviated_relations'],
        ]);
    }
}
