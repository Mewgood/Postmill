<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Repository\ForumRepository;
use App\Repository\SubmissionRepository;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
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
     * @var SubmissionRepository
     */
    private $submissions;

    /**
     * @var UserRepository
     */
    private $users;

    public function __construct(
        ForumRepository $forums,
        SubmissionRepository $submissions,
        UserRepository $users
    ) {
        $this->forums = $forums;
        $this->submissions = $submissions;
        $this->users = $users;
    }

    public function front(string $sortBy = null, Request $request): Response {
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

        return [$this, $listing]($sortBy, $request);
    }

    public function featured(string $sortBy, Request $request): Response {
        $forums = $this->forums->findFeaturedForumNames();

        if ($this->isGranted('ROLE_USER')) {
            $excludedForums = $this->users->findHiddenForumIdsByUser($this->getUser());
        }

        $submissions = $this->submissions->findSubmissions($sortBy, [
            'excluded_forums' => $excludedForums ?? [],
            'forums' => array_keys($this->forums->findFeaturedForumNames()),
        ], $request);

        return $this->render('front/featured.html.twig', [
            'forums' => $forums,
            'listing' => 'featured',
            'submissions' => $submissions,
            'sort_by' => $sortBy,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function subscribed(string $sortBy, Request $request): Response {
        $forums = $this->forums->findSubscribedForumNames($this->getUser());

        if (\count($forums) === 0) {
            // To avoid showing new users a blank page, we show them the
            // featured forums instead.
            return $this->redirectToRoute('featured', ['sortBy' => $sortBy]);
        }

        $submissions = $this->submissions->findSubmissions($sortBy, [
            'forums' => array_keys($forums),
        ], $request);

        return $this->render('front/subscribed.html.twig', [
            'forums' => $forums,
            'listing' => 'subscribed',
            'sort_by' => $sortBy,
            'submissions' => $submissions,
        ]);
    }

    public function all(string $sortBy, Request $request): Response {
        if ($this->isGranted('ROLE_USER')) {
            $excludedForums = $this->users->findHiddenForumIdsByUser($this->getUser());
        }

        $submissions = $this->submissions->findSubmissions($sortBy, [
            'excluded_forums' => $excludedForums ?? [],
        ], $request);

        return $this->render('front/base.html.twig', [
            'listing' => 'all',
            'sort_by' => $sortBy,
            'submissions' => $submissions,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function moderated(string $sortBy, Request $request): Response {
        $forums = $this->forums->findModeratedForumNames($this->getUser());

        $submissions = $this->submissions->findSubmissions($sortBy, [
            'forums' => array_keys($forums),
        ], $request);

        return $this->render('front/moderated.html.twig', [
            'forums' => $forums,
            'listing' => 'moderated',
            'sort_by' => $sortBy,
            'submissions' => $submissions,
        ]);
    }

    public function featuredFeed(string $sortBy, Request $request): Response {
        $forums = $this->forums->findFeaturedForumNames();

        $submissions = $this->submissions->findSubmissions($sortBy, [
            'forums' => array_keys($forums),
        ], $request);

        return $this->render('front/featured.xml.twig', [
            'forums' => $forums,
            'submissions' => $submissions,
        ]);
    }
}
