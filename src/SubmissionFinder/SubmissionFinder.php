<?php

namespace App\SubmissionFinder;

use App\Entity\Submission;
use App\Pagination\Adapter\DoctrineAdapter;
use App\Pagination\DTO\SubmissionPage;
use App\Pagination\Pager;
use App\Pagination\Paginator;
use App\Repository\SubmissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

final class SubmissionFinder {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Paginator
     */
    private $paginator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var SubmissionRepository
     */
    private $repository;

    public function __construct(
        EntityManagerInterface $entityManager,
        Paginator $paginator,
        RequestStack $requestStack,
        SubmissionRepository $repository
    ) {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
        $this->requestStack = $requestStack;
        $this->repository = $repository;
    }

    /**
     * Finds submissions!
     *
     * @throws NoSubmissionsException if there are no submissions
     */
    public function find(Criteria $criteria): Pager {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(Submission::class, 's')
            ->where('s.visibility = :visibility')
            ->setParameter('visibility', Submission::VISIBILITY_VISIBLE);

        $page = $this->getPage($criteria);

        $this->addTimeClause($qb);
        $this->addStickyClause($qb, $criteria, $page);
        $this->filter($qb, $criteria);

        $results = $this->paginator->paginate(
            new DoctrineAdapter($qb),
            $criteria->getMaxPerPage(),
            SubmissionPage::class,
            $criteria->getSortBy()
        );

        if ($page && \count($results) === 0) {
            throw new NoSubmissionsException();
        }

        $this->repository->hydrate(...$results);

        return $results;
    }

    private function getPage(Criteria $criteria): ?SubmissionPage {
        /** @var SubmissionPage|null $page */
        $page = $this->paginator->getPage(SubmissionPage::class, $criteria->getSortBy());

        return $page;
    }

    private function addTimeClause(QueryBuilder $qb): void {
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $time = $request->query->get('t', Submission::TIME_ALL);

            if ($time !== Submission::TIME_ALL) {
                $since = new \DateTimeImmutable();

                switch ($time) {
                case Submission::TIME_YEAR:
                    $since = $since->modify('-1 year');
                    break;
                case Submission::TIME_MONTH:
                    $since = $since->modify('-1 month');
                    break;
                case Submission::TIME_WEEK:
                    $since = $since->modify('-1 week');
                    break;
                case Submission::TIME_DAY:
                    $since = $since->modify('-1 day');
                    break;
                default:
                    // 404 on bad query parameter
                    throw new NoSubmissionsException();
                }

                $qb->andWhere('s.timestamp > :time');
                $qb->setParameter('time', $since, Types::DATETIMETZ_IMMUTABLE);
            }
        }
    }

    private function addStickyClause(QueryBuilder $qb, Criteria $criteria, ?SubmissionPage $page): void {
        if ($criteria->getStickiesFirst()) {
            if (!$page) {
                // Order by stickies on page 1.
                $qb->addOrderBy('s.sticky', 'DESC');
            } else {
                // Exclude all stickies from page 2 and onward, since they're
                // all assumed to be on page 1. Will miss all stickies that are
                // meant to be on the next page. The solution is to not be a
                // doofus and sticky more than the max number posts per page.
                $qb->andWhere($qb->expr()->eq('s.sticky', 'false'));
            }
        }
    }

    private function filter(QueryBuilder $qb, Criteria $criteria): void {
        switch ($criteria->getView()) {
        case Criteria::VIEW_FEATURED:
            $qb->andWhere('s.forum IN (SELECT f FROM App\Entity\Forum f WHERE f.featured = TRUE)');
            break;
        case Criteria::VIEW_SUBSCRIBED:
            $qb->andWhere('s.forum IN (SELECT IDENTITY(fs.forum) FROM App\Entity\ForumSubscription fs WHERE fs.user = :user)');
            $qb->setParameter('user', $criteria->getUser());
            break;
        case Criteria::VIEW_MODERATED:
            $qb->andWhere('s.forum IN (SELECT IDENTITY(m.forum) FROM App\Entity\Moderator m WHERE m.user = :user)');
            $qb->setParameter('user', $criteria->getUser());
            break;
        case Criteria::VIEW_FORUMS:
            $forums = $criteria->getForums();
            if (\count($forums) > 0) {
                $qb->andWhere('s.forum IN (:forums)');
                $qb->setParameter('forums', $forums);
            }
            break;
        case Criteria::VIEW_USERS:
            $users = $criteria->getUsers();
            if (\count($users) > 0) {
                $qb->andWhere('s.user IN (:users)');
                $qb->setParameter('users', $users);
            }
            break;
        case Criteria::VIEW_ALL:
            // noop
            break;
        default:
            throw new \LogicException("Bad sort mode {$criteria->getView()}");
        }

        if ($criteria->getExclusions() & Criteria::EXCLUDE_HIDDEN_FORUMS) {
            $qb->andWhere('s.forum NOT IN (SELECT hf FROM App\Entity\User u JOIN u.hiddenForums AS hf WHERE u = :user)');
            $qb->setParameter('user', $criteria->getUser());
        }
    }
}
