<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Repository\ForumRepository;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\SubmissionFinder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
 * Actions that list submissions across many forums.
 *
 * @Cache(smaxage="10 seconds")
 */
final class FrontController extends AbstractController {
    /**
     * @var ForumRepository
     */
    private $forums;

    /**
     * @var SubmissionFinder
     */
    private $submissionFinder;

    public function __construct(
        ForumRepository $forums,
        SubmissionFinder $submissionFinder
    ) {
        $this->forums = $forums;
        $this->submissionFinder = $submissionFinder;
    }

    public function front(string $sortBy = null): Response {
        if ($this->isGranted('ROLE_USER')) {
            /* @var \App\Entity\User $user */
            $user = $this->getUser();

            $listing = $user->getFrontPage();
            $sortBy = $sortBy ?? $user->getFrontPageSortMode();

            if (
                $listing === Submission::FRONT_SUBSCRIBED &&
                $user->getSubscriptions()->isEmpty()
            ) {
                $listing = Submission::FRONT_FEATURED;
            }
        } else {
            $listing = Submission::FRONT_FEATURED;
            $sortBy = $sortBy ?? Submission::SORT_HOT;
        }

        return $this->$listing($sortBy, 'html');
    }

    public function featured(string $sortBy, string $_format): Response {
        $criteria = (new Criteria($sortBy, $this->getUser()))
            ->showFeatured()
            ->excludeHiddenForums();

        $submissions = $this->submissionFinder->find($criteria);

        return $this->render("front/featured.$_format.twig", [
            'forums' => $this->forums->findFeaturedForumNames(),
            'sort_by' => $sortBy,
            'submissions' => $submissions,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function subscribed(string $sortBy): Response {
        $forums = $this->forums->findSubscribedForumNames($this->getUser());

        if (!$forums) {
            // To avoid showing new users a blank page, we show them the
            // featured forums instead.
            return $this->redirectToRoute('featured', ['sortBy' => $sortBy]);
        }

        $criteria = (new Criteria($sortBy, $this->getUser()))
            ->showSubscribed();

        $submissions = $this->submissionFinder->find($criteria);

        return $this->render("front/subscribed.html.twig", [
            'forums' => $forums,
            'sort_by' => $sortBy,
            'submissions' => $submissions,
        ]);
    }

    public function all(string $sortBy, string $_format): Response {
        $criteria = (new Criteria($sortBy, $this->getUser()))
            ->excludeHiddenForums();

        $submissions = $this->submissionFinder->find($criteria);

        return $this->render("front/all.$_format.twig", [
            'sort_by' => $sortBy,
            'submissions' => $submissions,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function moderated(string $sortBy): Response {
        $forums = $this->forums->findModeratedForumNames($this->getUser());

        $criteria = (new Criteria($sortBy, $this->getUser()))
            ->showModerated();

        $submissions = $this->submissionFinder->find($criteria);

        return $this->render('front/moderated.html.twig', [
            'forums' => $forums,
            'sort_by' => $sortBy,
            'submissions' => $submissions,
        ]);
    }
}
