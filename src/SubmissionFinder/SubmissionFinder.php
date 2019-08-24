<?php

namespace App\SubmissionFinder;

use App\Entity\Submission;
use App\Pagination\Adapter\ArrayAdapter;
use App\Pagination\DTO\SubmissionPage;
use App\Pagination\Pager;
use App\Pagination\Paginator;
use App\Repository\SubmissionRepository;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

final class SubmissionFinder {
    private const SORT_CLAUSE_FORMATS = [
        'DESC' => '(%s) <= (:next_%s)',
        'ASC' => '(%s) >= (:next_%s)',
    ];

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
        $rsm = new ResultSetMappingBuilder($this->entityManager);
        $rsm->addRootEntityFromClassMetadata(Submission::class, 's');

        $qb = $this->entityManager->getConnection()->createQueryBuilder()
            ->select($rsm->generateSelectClause())
            ->from('submissions', 's')
            ->where('s.visibility = :visibility')
            ->setParameter('visibility', Submission::VISIBILITY_VISIBLE)
            ->setMaxResults($criteria->getMaxPerPage() + 1);

        $page = $this->getPage($criteria);

        $this->addTimeClause($qb);
        $this->addStickyClause($qb, $criteria, $page);
        $this->order($qb, $criteria);
        $this->paginate($qb, $criteria, $page);
        $this->filter($qb, $criteria);

        $results = $this->entityManager
            ->createNativeQuery($qb->getSQL(), $rsm)
            ->setParameters($qb->getParameters())
            ->execute();

        if ($page && \count($results) === 0) {
            throw new NoSubmissionsException();
        }

        $this->repository->hydrate(...$results);

        return $this->paginator->paginate(
            new ArrayAdapter($results),
            $criteria->getMaxPerPage(),
            SubmissionPage::class,
            $criteria->getSortBy()
        );
    }

    private function getPage(Criteria $criteria): ?SubmissionPage {
        /** @var SubmissionPage|null $page */
        $page = $this->paginator->getPage(SubmissionPage::class, $criteria->getSortBy());

        return $page;
    }

    private function addTimeClause(QueryBuilder $qb): void {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        $time = $request->query->get('t', Submission::TIME_ALL);

        if ($time !== Submission::TIME_ALL) {
            $since = new \DateTime();

            $qb->andWhere('s.timestamp > :time');
            $qb->setParameter('time', $since, Type::DATETIMETZ);

            switch ($time) {
            case Submission::TIME_YEAR:
                $since->modify('-1 year');
                break;
            case Submission::TIME_MONTH:
                $since->modify('-1 month');
                break;
            case Submission::TIME_WEEK:
                $since->modify('-1 week');
                break;
            case Submission::TIME_DAY:
                $since->modify('-1 day');
                break;
            default:
                // 404 on bad query parameter
                throw new NoSubmissionsException();
            }
        }
    }

    private function addStickyClause(QueryBuilder $qb, Criteria $criteria, ?SubmissionPage $page): void {
        if (!$criteria->getStickiesFirst()) {
            return;
        }

        if (!$page) {
            // Order by stickies on page 1.
            $qb->addOrderBy('s.sticky', 'DESC');
        } else {
            // Exclude all stickies from page 2 and onward, since they're all
            // assumed to be on page 1. Will miss all stickies that are meant to
            // be on the next page. The solution is to not be a doofus and
            // sticky more than the max number posts per page.
            $qb->andWhere($qb->expr()->eq('s.sticky', 'false'));
        }
    }

    private function order(QueryBuilder $qb, Criteria $criteria): void {
        $metadata = $this->entityManager->getClassMetadata(Submission::class);
        $sortBy = $criteria->getSortBy();

        foreach (SubmissionPage::SORT_FIELD_MAP[$sortBy] as $field) {
            $column = $metadata->getColumnName($field);
            $order = SubmissionPage::SORT_ORDER[$sortBy];

            $qb->addOrderBy("s.$column", $order);
        }
    }

    private function paginate(QueryBuilder $qb, Criteria $criteria, ?SubmissionPage $page): void {
        if (!$page) {
            return;
        }

        $metadata = $this->entityManager->getClassMetadata(Submission::class);
        $sortBy = $criteria->getSortBy();

        foreach (SubmissionPage::SORT_FIELD_MAP[$sortBy] as $field) {
            $columns[$field] = $metadata->getColumnName($field);
        }

        $format = self::SORT_CLAUSE_FORMATS[SubmissionPage::SORT_ORDER[$sortBy]];

        $qb->andWhere(sprintf($format,
            implode(', ', $columns),
            implode(', :next_', $columns)
        ));

        foreach ($columns as $field => $column) {
            $qb->setParameter('next_'.$column, $page->{$field});
        }
    }

    private function filter(QueryBuilder $qb, Criteria $criteria): void {
        switch ($criteria->getView()) {
        case Criteria::VIEW_FEATURED:
            $qb->andWhere('s.forum_id IN (SELECT id FROM forums WHERE featured = TRUE)');
            break;
        case Criteria::VIEW_SUBSCRIBED:
            $qb->andWhere('s.forum_id IN (SELECT forum_id FROM forum_subscriptions WHERE user_id = :user)');
            $qb->setParameter('user', $criteria->getUser());
            break;
        case Criteria::VIEW_MODERATED:
            $qb->andWhere('s.forum_id IN (SELECT forum_id FROM moderators WHERE user_id = :user)');
            $qb->setParameter('user', $criteria->getUser());
            break;
        case Criteria::VIEW_FORUMS:
            $forums = $criteria->getForums();
            if (\count($forums) > 0) {
                $qb->andWhere('s.forum_id IN (:forums)');
                $qb->setParameter('forums', $forums);
            }
            break;
        case Criteria::VIEW_USERS:
            $users = $criteria->getUsers();
            if (\count($users) > 0) {
                $qb->andWhere('s.user_id IN (:users)');
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
            $qb->andWhere('s.forum_id NOT IN (SELECT forum_id FROM hidden_forums WHERE user_id = :user)');
            $qb->setParameter('user', $criteria->getUser());
        }
    }
}
