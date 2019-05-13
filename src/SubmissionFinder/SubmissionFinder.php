<?php

namespace App\SubmissionFinder;

use App\Entity\Submission;
use App\Entity\User;
use App\Repository\SubmissionRepository;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Submission finder that does things:
 *
 * - keyset pagination
 * -
 */
final class SubmissionFinder {
    /**
     * `$sortBy` -> ordered column name mapping.
     *
     * @var array[]
     */
    public const SORT_COLUMN_MAP = [
        Submission::SORT_ACTIVE => ['last_active' => 'DESC', 'id' => 'DESC'],
        Submission::SORT_HOT => ['ranking' => 'DESC', 'id' => 'DESC'],
        Submission::SORT_NEW => ['id' => 'DESC'],
        Submission::SORT_TOP => ['net_score' => 'DESC', 'id' => 'DESC'],
        Submission::SORT_CONTROVERSIAL => ['net_score' => 'ASC', 'id' => 'ASC'],
        Submission::SORT_MOST_COMMENTED => ['comment_count' => 'DESC', 'id' => 'DESC'],
    ];

    public const SORT_COLUMN_TYPES = [
        'last_active' => 'datetimetz',
        'ranking' => 'bigint',
        'id' => 'bigint',
        'net_score' => 'integer',
        'comment_count' => 'integer',
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

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
        RequestStack $requestStack,
        SubmissionRepository $repository
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->repository = $repository;
    }

    /**
     * Finds submissions!
     *
     * @throws NoSubmissionsException if there are no submissions
     */
    public function find(Criteria $criteria): Pager {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            $request = new Request();
        }

        $rsm = new ResultSetMappingBuilder($this->entityManager);
        $rsm->addRootEntityFromClassMetadata(Submission::class, 's');

        $qb = $this->entityManager->getConnection()->createQueryBuilder()
            ->select($rsm->generateSelectClause())
            ->from('submissions', 's')
            ->where('s.visibility = :visibility')
            ->setParameter('visibility', Submission::VISIBILITY_VISIBLE)
            ->setMaxResults($criteria->getMaxPerPage() + 1);

        $this->addTimeClause($qb);

        $pager = Pager::getParamsFromRequest($criteria->getSortBy(), $request);

        if ($criteria->getStickiesFirst()) {
            $this->addStickyClause($qb, $pager);
        }

        foreach (self::SORT_COLUMN_MAP[$criteria->getSortBy()] as $column => $order) {
            $qb->addOrderBy('s.'.$column, $order);
        }

        if ($pager) {
            $this->paginate($qb, $pager, $criteria->getSortBy());
        }

        $this->filter($qb, $criteria);

        $results = $this->entityManager
            ->createNativeQuery($qb->getSQL(), $rsm)
            ->setParameters($qb->getParameters())
            ->execute();

        if ($pager && \count($results) === 0) {
            throw new NoSubmissionsException();
        }

        $this->repository->hydrate(...$results);

        return new Pager($results, $criteria->getMaxPerPage(), $criteria->getSortBy());
    }

    private function paginate(QueryBuilder $qb, array $pager, string $sortBy): void {
        $qb->andWhere(sprintf('(%s) <= (:next_%s)',
            implode(', ', \array_keys(self::SORT_COLUMN_MAP[$sortBy])),
            implode(', :next_', \array_keys(self::SORT_COLUMN_MAP[$sortBy]))
        ));

        foreach (self::SORT_COLUMN_MAP[$sortBy] as $column => $order) {
            $qb->setParameter('next_'.$column, $pager[$column]);
        }
    }

    private function addStickyClause(QueryBuilder $qb, array $pager): void {
        if (empty($pager)) {
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

    private function addTimeClause(QueryBuilder $qb): void {
        $request = $this->requestStack->getCurrentRequest() ?? new Request();

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
