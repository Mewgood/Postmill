<?php

namespace App\Repository;

use App\Entity\Submission;
use App\Repository\Submission\NoSubmissionsException;
use App\Repository\Submission\SubmissionPager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SubmissionRepository extends ServiceEntityRepository {
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

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

    private const MAX_PER_PAGE = 25;

    public function __construct(
        ManagerRegistry $registry,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($registry, Submission::class);

        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * The amazing submission finder.
     *
     * @param string  $sortBy  One of Submission::SORT_* constants
     * @param array   $options An array with the following keys:
     *                         <ul>
     *                         <li><kbd>forums</kbd>
     *                         <li><kbd>excluded_forums</kbd>
     *                         <li><kbd>users</kbd>
     *                         <li><kbd>excluded_users</kbd>
     *                         <li><kbd>stickies</kbd> - show stickies first
     *                         <li><kbd>max_per_page</kbd>
     *                         <li><kbd>time</kbd> - One of Submission::TIME_*
     *                           constants
     *                         </ul>
     * @param Request $request Request to retrieve pager options and time filter
     *                         from
     *
     * @return Submission[]|SubmissionPager
     *
     * @throws \InvalidArgumentException if $sortBy is bad
     * @throws NoSubmissionsException    if there are no submissions
     */
    public function findSubmissions(string $sortBy, array $options = [], Request $request = null) {
        $maxPerPage = $options['max_per_page'] ?? self::MAX_PER_PAGE;
        $time = $request->query->get('t', Submission::TIME_ALL);

        // Silently fail on invalid time
        if (!\in_array($time, Submission::TIME_OPTIONS, true)) {
            $time = Submission::TIME_ALL;
        }

        $rsm = $this->createResultSetMappingBuilder('s');

        $qb = $this->_em->getConnection()->createQueryBuilder()
            ->select($rsm->generateSelectClause())
            ->from('submissions', 's')
            ->where('s.visibility IN (:visibility)')
            ->setParameter('visibility', Submission::VISIBILITY_VISIBLE)
            ->setMaxResults($maxPerPage + 1);

        if (!\in_array($sortBy, Submission::SORT_OPTIONS, true)) {
            throw new \InvalidArgumentException("Sort mode '$sortBy' not implemented");
        }

        $since = null;

        if ($time !== Submission::TIME_ALL) {
            $since = new \DateTime();

            $qb->andWhere('s.timestamp > :time');
            $qb->setParameter('time', $since, Type::DATETIMETZ);
        }

        switch ($time) {
        case Submission::TIME_ALL:
            break;
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
            throw new \InvalidArgumentException("Time mode '$time' not implemented");
        }

        $pager = $request
            ? SubmissionPager::getParamsFromRequest($sortBy, $request)
            : [];

        if (!empty($options['stickies'])) {
            if (!$pager) {
                // Order by stickies on page 1.
                $qb->orderBy('sticky', 'DESC');
            } else {
                // Exclude all stickies from page 2 and onward, since they're
                // assumed to be on page 1. Will miss all stickies that are
                // meant to be on the next page. The solution is to not be a
                // doofus and sticky more than $maxPerPage posts.
                $qb->andWhere($qb->expr()->eq('sticky', 'false'));
            }
        }

        foreach (self::SORT_COLUMN_MAP[$sortBy] as $column => $order) {
            $qb->addOrderBy($column, $order);
        }

        if ($pager) {
            $qb->andWhere(sprintf('(%s) <= (:next_%s)',
                implode(', ', \array_keys(self::SORT_COLUMN_MAP[$sortBy])),
                implode(', :next_', \array_keys(self::SORT_COLUMN_MAP[$sortBy]))
            ));

            foreach (self::SORT_COLUMN_MAP[$sortBy] as $column => $order) {
                $qb->setParameter('next_'.$column, $pager[$column]);
            }
        }

        self::filterQuery($qb, $options);

        $results = $this->_em
            ->createNativeQuery($qb->getSQL(), $rsm)
            ->setParameters($qb->getParameters())
            ->execute();

        if ($pager && \count($results) === 0) {
            throw new NoSubmissionsException();
        }

        $submissions = new SubmissionPager($results, $maxPerPage, $sortBy);

        $this->hydrate(...$submissions);

        return $submissions;
    }

    private static function filterQuery(QueryBuilder $qb, array $options): void {
        if (!empty($options['forums'])) {
            /* @noinspection NotOptimalIfConditionsInspection */
            if (!empty($options['excluded_forums'])) {
                $options['forums'] = array_diff(
                    $options['forums'],
                    $options['excluded_forums']
                );
            }

            $qb->andWhere('s.forum_id IN (:forum_ids)');
            $qb->setParameter('forum_ids', $options['forums']);
        } elseif (!empty($options['excluded_forums'])) {
            $qb->andWhere('s.forum_id NOT IN (:forum_ids)');
            $qb->setParameter('forum_ids', $options['excluded_forums']);
        }

        if (!empty($options['users'])) {
            /* @noinspection NotOptimalIfConditionsInspection */
            if (!empty($options['excluded_users'])) {
                $options['users'] = array_diff(
                    $options['users'],
                    $options['excluded_users']
                );
            }

            $qb->andWhere('s.user_id IN (:user_ids)');
            $qb->setParameter('user_ids', $options['users']);
        } elseif (!empty($options['excluded_users'])) {
            $qb->andWhere('s.user_id NOT IN (:user_ids)');
            $qb->setParameter('user_ids', $options['excluded_users']);
        }
    }

    public function hydrate(Submission ...$submissions): void {
        $this->_em->createQueryBuilder()
            ->select('PARTIAL s.{id}')
            ->addSelect('u')
            ->addSelect('f')
            ->from(Submission::class, 's')
            ->join('s.user', 'u')
            ->join('s.forum', 'f')
            ->where('s IN (?1)')
            ->setParameter(1, $submissions)
            ->getQuery()
            ->getResult();

        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            // hydrate submission votes for fast checking of user choice
            $this->_em->createQueryBuilder()
                ->select('PARTIAL s.{id}')
                ->addSelect('sv')
                ->from(Submission::class, 's')
                ->leftJoin('s.votes', 'sv')
                ->where('s IN (?1)')
                ->setParameter(1, $submissions)
                ->getQuery()
                ->getResult();
        }
    }
}
