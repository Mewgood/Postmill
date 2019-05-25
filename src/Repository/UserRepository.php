<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Submission;
use App\Entity\User;
use App\Pagination\DTO\UserContributionsPage;
use App\Pagination\Form\PageType;
use App\Pagination\Pager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\DoctrineSelectableAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @method User|null findOneByUsername(string|string[] $username)
 * @method User|null findOneByNormalizedUsername(string|string[] $normalizedUsername)
 * @method User[]    findByUsername(string|string[] $usernames)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface {
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

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
        FormFactoryInterface $formFactory,
        NormalizerInterface $normalizer,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($registry, User::class);

        $this->formFactory = $formFactory;
        $this->normalizer = $normalizer;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param string|null $username
     *
     * @return User|null
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
     * @param string $email
     *
     * @return User[]|Collection
     */
    public function lookUpByEmail(string $email) {
        // Normalization of email address is prone to change, so look them up
        // by both canonical and normalized variations just in case.
        return $this->createQueryBuilder('u')
            ->where('u.email = ?1')
            ->orWhere('u.normalizedEmail = ?2')
            ->setParameter(1, $email)
            ->setParameter(2, User::normalizeEmail($email))
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
     * @param User $user
     *
     * @return Pager
     */
    public function findContributions(User $user): Pager {
        $page = null;
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $page = new UserContributionsPage();

            $form = $this->formFactory->createNamed('next', PageType::class, $page);
            $form->handleRequest($request);

            if (!$form->isSubmitted() || !$form->isValid()) {
                $page = null;
            }
        }

        $qb = $this->_em->createQueryBuilder()
            ->select('s')
            ->from(Submission::class, 's')
            ->where('s.user = :user')
            ->andWhere('s.visibility = :visibility')
            ->setParameter('user', $user)
            ->setParameter('visibility', Submission::VISIBILITY_VISIBLE)
            ->orderBy('s.timestamp', 'DESC')
            ->setMaxResults(26);

        if ($page) {
            $qb->andWhere('s.timestamp <= :next_timestamp');
            $qb->setParameter('next_timestamp', $page->timestamp);
        }

        $submissions = $qb->getQuery()->execute();

        $qb = $this->_em->createQueryBuilder()
            ->select('c')
            ->from(Comment::class, 'c')
            ->where('c.softDeleted = FALSE')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.timestamp', 'DESC')
            ->setMaxResults(26);

        if ($page) {
            $qb->setParameter('next_timestamp', $page->timestamp);
            $qb->andWhere('c.timestamp <= :next_timestamp');
        }

        $comments = $qb->getQuery()->execute();

        $combined = \array_merge($submissions, $comments);

        $this->_em->getRepository(Submission::class)->hydrate(...$submissions);
        $this->_em->getRepository(Comment::class)->hydrate(...$comments);

        \usort($combined, function ($a, $b) {
            return $b->getTimestamp() <=> $a->getTimestamp();
        });

        $pagerEntity = $combined[25] ?? null;

        if ($pagerEntity) {
            $nextPageParams = $this->normalizer->normalize(
                UserContributionsPage::createFromContribution($pagerEntity),
                null,
                ['groups' => ['pager']]
            );

            $combined = \array_slice($combined, 0, 25);
        }

        $contributions = \array_map(function ($element) {
            $type = $element instanceof Submission ? 'submission' : 'comment';

            return ['type' => $type, $type => $element];
        }, $combined);

        return new Pager($contributions, $nextPageParams ?? []);
    }

    /**
     * @param int      $page
     * @param Criteria $criteria
     *
     * @return User[]|Pagerfanta
     */
    public function findPaginated(int $page, Criteria $criteria) {
        $pager = new Pagerfanta(new DoctrineSelectableAdapter($this, $criteria));
        $pager->setMaxPerPage(25);
        $pager->setCurrentPage($page);

        return $pager;
    }

    public function findIpsUsedByUser(User $user): \Traversable {
        $sql = 'SELECT DISTINCT ip FROM ('.
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

    /**
     * @param User $user
     *
     * @return array|int[]
     */
    public function findHiddenForumIdsByUser(User $user): array {
        $sql = 'SELECT forum_id FROM hidden_forums WHERE user_id = :user_id';

        $sth = $this->_em->getConnection()->prepare($sql);
        $sth->bindValue(':user_id', $user->getId());
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_COLUMN);
    }
}
