<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Forum;
use App\Entity\Submission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CommentRepository extends ServiceEntityRepository {
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(
        ManagerRegistry $registry,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($registry, Comment::class);

        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return Comment
     *
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
     * @return Pagerfanta|Comment[]
     */
    public function findRecentPaginated(int $page, int $maxPerPage = 25) {
        $query = $this->createQueryBuilder('c')
            ->where('c.softDeleted = FALSE')
            ->orderBy('c.id', 'DESC');

        $pager = new Pagerfanta(new DoctrineORMAdapter($query, false, false));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        $this->hydrate(...$pager);

        return $pager;
    }

    /**
     * @return Pagerfanta|Comment[]
     */
    public function findRecentPaginatedInForum(Forum $forum, int $page, int $maxPerPage = 25) {
        $query = $this->createQueryBuilder('c')
            ->join('c.submission', 's')
            ->where('s.forum = :forum')
            ->setParameter('forum', $forum)
            ->andWhere('c.softDeleted = FALSE')
            ->orderBy('c.id', 'DESC');

        $pager = new Pagerfanta(new DoctrineORMAdapter($query, false, false));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        $this->hydrate(...$pager);

        return $pager;
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
