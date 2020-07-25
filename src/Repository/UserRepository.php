<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Submission;
use App\Entity\User;
use App\Pagination\TimestampPage;
use App\Repository\Contracts\PrunesIpAddresses;
use App\Repository\Traits\PrunesIpAddressesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\DoctrineSelectableAdapter;
use Pagerfanta\Pagerfanta;
use PagerWave\Adapter\DoctrineAdapter;
use PagerWave\Adapter\UnionAdapter;
use PagerWave\CursorInterface;
use PagerWave\PaginatorInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @method User|null findOneByUsername(string|string[] $username)
 * @method User|null findOneByNormalizedUsername(string|string[] $normalizedUsername)
 * @method User[]    findByUsername(string|string[] $usernames)
 * @method User[]    findByNormalizedUsername(string|string[] $usernames)
 */
class UserRepository extends ServiceEntityRepository implements PrunesIpAddresses, UserLoaderInterface {
    use PrunesIpAddressesTrait;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(
        ManagerRegistry $registry,
        PaginatorInterface $paginator,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($registry, User::class);
        $this->paginator = $paginator;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param string|null $username
     */
    public function loadUserByUsername($username): ?User {
        if ($username === null) {
            return null;
        }

        return $this->findOneByNormalizedUsername(User::normalizeUsername($username));
    }

    public function findOneOrRedirectToCanonical(?string $username, string $param): ?User {
        $user = $this->loadUserByUsername($username);

        if ($user && $user->getUsername() !== $username) {
            $request = $this->requestStack->getCurrentRequest();

            if (
                !$request ||
                $this->requestStack->getParentRequest() ||
                !$request->isMethodCacheable()
            ) {
                return $user;
            }

            $route = $request->attributes->get('_route');
            $params = $request->attributes->get('_route_params', []);
            $params[$param] = $user->getUsername();

            throw new HttpException(302, 'Redirecting to canonical', null, [
                'Location' => $this->urlGenerator->generate($route, $params),
            ]);
        }

        return $user;
    }

    /**
     * @return User[]
     */
    public function lookUpByEmail(string $email): array {
        // Normalization of email address is prone to change, so look them up
        // by both canonical and normalized variations just in case.
        return $this->createQueryBuilder('u')
            ->where('u.email = ?1')
            ->orWhere('u.normalizedEmail = ?2')
            ->setParameter(1, $email)
            ->setParameter(2, User::normalizeEmail($email))
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->execute();
    }

    /**
     * Find the combined contributions (comments and submissions) of a user.
     *
     * This has the potential of skipping some contributions if they were posted
     * at the same second, and if they were to appear on separate pages. This is
     * an edge case, so we don't really care.
     *
     * @return CursorInterface|Submission[]|Comment[]
     */
    public function findContributions(User $user): CursorInterface {
        $submissionsQuery = $this->_em->createQueryBuilder()
            ->select('s')
            ->from(Submission::class, 's')
            ->andWhere('s.user = :user')
            ->andWhere('s.visibility = :visibility')
            ->setParameter('user', $user)
            ->setParameter('visibility', Submission::VISIBILITY_VISIBLE);

        $commentsQuery = $this->_em->createQueryBuilder()
            ->select('c')
            ->from(Comment::class, 'c')
            ->andWhere('c.user = :user')
            ->andWhere('c.visibility = :visibility')
            ->setParameter('user', $user)
            ->setParameter('visibility', Comment::VISIBILITY_VISIBLE);

        $adapter = new UnionAdapter(
            new DoctrineAdapter($submissionsQuery),
            new DoctrineAdapter($commentsQuery)
        );

        $cursor = $this->paginator->paginate($adapter, 25, new TimestampPage());

        $this->hydrateContributions($cursor);

        return $cursor;
    }

    /**
     * @return Pagerfanta|User[]
     */
    public function findPaginated(int $page, Criteria $criteria): Pagerfanta {
        $pager = new Pagerfanta(new DoctrineSelectableAdapter($this, $criteria));
        $pager->setMaxPerPage(25);
        $pager->setCurrentPage($page);

        return $pager;
    }

    public function findIpsUsedByUser(User $user): \Generator {
        $sql = 'SELECT DISTINCT ip FROM ('.
            'SELECT registration_ip AS ip FROM users WHERE id = :id AND registration_ip IS NOT NULL UNION ALL '.
            'SELECT ip FROM submissions WHERE user_id = :id AND ip IS NOT NULL UNION ALL '.
            'SELECT ip FROM comments WHERE user_id = :id AND ip IS NOT NULL UNION ALL '.
            'SELECT ip FROM submission_votes WHERE user_id = :id AND ip IS NOT NULL UNION ALL '.
            'SELECT ip FROM comment_votes WHERE user_id = :id AND ip IS NOT NULL UNION ALL '.
            'SELECT ip FROM messages WHERE sender_id = :id AND ip IS NOT NULL'.
        ') q';

        $sth = $this->_em->getConnection()->prepare($sql);
        $sth->bindValue(':id', $user->getId());
        $sth->execute();

        while ($ip = $sth->fetchColumn()) {
            yield $ip;
        }
    }

    protected function getIpAddressField(): string {
        return 'registrationIp';
    }

    protected function getTimestampField(): string {
        return 'created';
    }

    private function hydrateContributions(iterable $contributions): void {
        $submissions = $comments = [];

        foreach ($contributions as $entity) {
            if ($entity instanceof Submission) {
                $submissions[] = $entity;
            } elseif ($entity instanceof Comment) {
                $comments[] = $entity;
            }
        }

        $this->_em->getRepository(Submission::class)->hydrate(...$submissions);
        $this->_em->getRepository(Comment::class)->hydrate(...$comments);
    }
}
