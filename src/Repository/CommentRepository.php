<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use App\Pagination\Adapter\DoctrineAdapter;
use App\Pagination\DTO\CommentPage;
use App\Pagination\Pager;
use App\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CommentRepository extends ServiceEntityRepository {
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var Paginator
     */
    private $paginator;

    public function __construct(
        ManagerRegistry $registry,
        AuthorizationCheckerInterface $authorizationChecker,
        Paginator $paginator
    ) {
        parent::__construct($registry, Comment::class);

        $this->authorizationChecker = $authorizationChecker;
        $this->paginator = $paginator;
    }

    /**
     * @throws NotFoundHttpException if no such comment
     */
    public function findOneBySubmissionAndIdOr404(
        ?Submission $submission,
        ?int $id
    ): ?Comment {
        if (!$submission || !$id) {
            return null;
        }

        $comment = $this->findOneBy(['submission' => $submission, 'id' => $id]);

        if (!$comment instanceof Comment) {
            throw new NotFoundHttpException('No such comment');
        }

        return $comment;
    }

    /**
     * @return Pager|Comment[]
     */
    public function findPaginated(callable $queryModifier = null): Pager {
        $qb = $this->createQueryBuilder('c')
            ->where('c.visibility = :visibility')
            ->setParameter('visibility', Comment::VISIBILITY_VISIBLE);

        if ($queryModifier) {
            $queryModifier($qb);
        }

        $pager = $this->paginator->paginate(new DoctrineAdapter($qb), 25, CommentPage::class);
        $this->hydrate(...$pager);

        return $pager;
    }

    /**
     * @return Pager|Comment[]
     */
    public function findPaginatedByForum(Forum $forum): Pager {
        return $this->findPaginated(function (QueryBuilder $qb) use ($forum): void {
            $qb->join('c.submission', 's', 'WITH', 's.forum = :forum');
            $qb->setParameter('forum', $forum);
        });
    }

    /**
     * @return Pager|Comment[]
     */
    public function findPaginatedByUser(User $user): Pager {
        return $this->findPaginated(function (QueryBuilder $qb) use ($user): void {
            $qb->andWhere('c.user = :user')->setParameter('user', $user);
        });
    }

    public function hydrate(Comment ...$comments): void {
        $this->createQueryBuilder('c')
            ->select('PARTIAL c.{id}')
            ->addSelect('u')
            ->addSelect('s')
            ->addSelect('sf')
            ->addSelect('su')
            ->join('c.user', 'u')
            ->join('c.submission', 's')
            ->join('s.forum', 'sf')
            ->join('s.user', 'su')
            ->where('c IN (?1)')
            ->setParameter(1, $comments)
            ->getQuery()
            ->execute();

        $this->createQueryBuilder('c')
            ->select('PARTIAL c.{id}')
            ->addSelect('cc')
            ->leftJoin('c.children', 'cc')
            ->where('c IN (?1)')
            ->setParameter(1, $comments)
            ->getQuery()
            ->execute();

        // for fast retrieval of user vote
        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            $this->createQueryBuilder('c')
                ->select('PARTIAL c.{id}')
                ->addSelect('cv')
                ->leftJoin('c.votes', 'cv')
                ->where('c IN (?1)')
                ->setParameter(1, $comments)
                ->getQuery()
                ->execute();
        }
    }
}
