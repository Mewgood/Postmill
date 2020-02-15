<?php

namespace App\Controller;

use App\DataObject\UserData;
use App\Entity\Forum;
use App\Entity\Site;
use App\Entity\Submission;
use App\Entity\User;
use App\Form\ConfirmDeletionType;
use App\Form\Model\UserBlockData;
use App\Form\Model\UserFilterData;
use App\Form\UserBiographyType;
use App\Form\UserBlockType;
use App\Form\UserFilterType;
use App\Form\UserSettingsType;
use App\Form\UserType;
use App\Message\DeleteUser;
use App\Repository\CommentRepository;
use App\Repository\ForumBanRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Security\AuthenticationHelper;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\SubmissionFinder;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Entity("user", expr="repository.findOneOrRedirectToCanonical(username, 'username')")
 * @Entity("site", expr="repository.findCurrentSite()")
 */
final class UserController extends AbstractController {
    /**
     * Show the user's profile page.
     */
    public function userPage(User $user, UserRepository $users): Response {
        $contributions = $users->findContributions($user);

        return $this->render('user/user.html.twig', [
            'contributions' => $contributions,
            'user' => $user,
        ]);
    }

    public function submissions(SubmissionFinder $finder, User $user): Response {
        $criteria = (new Criteria(Submission::SORT_NEW))
            ->showUsers($user);

        $submissions = $finder->find($criteria);

        return $this->render('user/submissions.html.twig', [
            'submissions' => $submissions,
            'user' => $user,
        ]);
    }

    public function comments(CommentRepository $repository, User $user): Response {
        $comments = $repository->findPaginatedByUser($user);

        return $this->render('user/comments.html.twig', [
            'comments' => $comments,
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function list(UserRepository $users, int $page, Request $request): Response {
        $filter = new UserFilterData();
        $criteria = $filter->buildCriteria();

        $form = $this->createForm(UserFilterType::class, $filter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $filter->buildCriteria();
        }

        return $this->render('user/list.html.twig', [
            'form' => $form->createView(),
            'page' => $page,
            'users' => $users->findPaginated($page, $criteria),
        ]);
    }

    /**
     * @IsGranted("register", subject="site", statusCode=403)
     */
    public function registration(Site $site, Request $request, EntityManagerInterface $em, AuthenticationHelper $auth): Response {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('front');
        }

        $data = new UserData();
        $data->setLocale($request->getLocale());
        $data->setFrontPageSortMode($site->getDefaultSortMode());

        $form = $this->createForm(UserType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $data->toUser($request->getClientIp());

            $em->persist($user);
            $em->flush();

            $response = $this->redirectToRoute('front');

            $auth->login($user, $request, $response, 'main');

            $this->addFlash('success', 'flash.user_account_registered');

            return $response;
        }

        return $this->render('user/registration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     */
    public function editUser(EntityManagerInterface $em, User $user, Request $request): Response {
        $data = new UserData($user);

        $form = $this->createForm(UserType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateUser($user);

            $em->flush();

            $this->addFlash('success', 'flash.user_settings_updated');

            return $this->redirectToRoute('edit_user', [
                'username' => $user->getUsername(),
            ]);
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     */
    public function deleteAccount(User $user, Request $request, TokenStorageInterface $tokenStorage): Response {
        $form = $this->createForm(ConfirmDeletionType::class, null, [
            'name' => $user->getUsername(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user === $this->getUser()) {
                $tokenStorage->setToken(null);
            }

            $this->dispatchMessage(new DeleteUser($user));

            $this->addFlash('notice', 'flash.account_deletion_in_progress');

            return $this->redirectToRoute('front');
        }

        return $this->render('user/delete_account.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     */
    public function userSettings(EntityManagerInterface $em, User $user, Request $request): Response {
        $data = new UserData($user);

        $form = $this->createForm(UserSettingsType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateUser($user);

            $em->flush();

            $this->addFlash('success', 'flash.user_settings_updated');

            return $this->redirect($request->getUri());
        }

        return $this->render('user/settings.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     */
    public function editBiography(EntityManagerInterface $em, User $user, Request $request): Response {
        $data = new UserData($user);

        $form = $this->createForm(UserBiographyType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateUser($user);

            $em->flush();

            $this->addFlash('success', 'flash.user_biography_updated');

            return $this->redirect($request->getUri());
        }

        return $this->render('user/edit_biography.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     */
    public function blockList(User $user, int $page): Response {
        return $this->render('user/block_list.html.twig', [
            'blocks' => $user->getPaginatedBlocks($page),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Security("not user.isBlocking(blockee)", statusCode=403)
     * @Security("user !== blockee", statusCode=403)
     */
    public function block(User $blockee, Request $request, EntityManagerInterface $em): Response {
        \assert($this->getUser() instanceof User);

        $data = new UserBlockData();

        $form = $this->createForm(UserBlockType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getUser()->block($blockee, $data->getComment());

            $em->flush();

            $this->addFlash('success', 'flash.user_blocked');

            return $this->redirectToRoute('user_block_list', [
                'username' => $this->getUser()->getUsername(),
            ]);
        }

        return $this->render('user/block.html.twig', [
            'form' => $form->createView(),
            'user' => $blockee,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function unblock(User $user, EntityManagerInterface $em, Request $request): Response {
        $this->validateCsrf('unblock', $request->request->get('token'));

        \assert($this->getUser() instanceof User);
        $this->getUser()->unblock($user);

        $em->flush();

        $this->addFlash('success', 'flash.user_unblocked');

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirectToRoute('user_block_list', [
            'username' => $this->getUser()->getUsername(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function notifications(int $page): Response {
        /* @var User $user */
        $user = $this->getUser();

        return $this->render('user/notifications.html.twig', [
            'notifications' => $user->getPaginatedNotifications($page),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function clearNotifications(Request $request, NotificationRepository $repository, EntityManagerInterface $em): Response {
        $this->validateCsrf('clear_notifications', $request->request->get('token'));

        $ids = array_filter((array) $request->request->get('id'), function ($id) {
            return is_numeric($id) && \is_int(+$id);
        });

        $repository->clearNotifications($this->getUser(), ...$ids);
        $em->flush();

        $this->addFlash('notice', 'flash.notifications_cleared');

        return $this->redirectToRoute('notifications');
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function whitelist(Request $request, User $user, EntityManagerInterface $em, bool $whitelist): Response {
        $this->validateCsrf('whitelist', $request->request->get('token'));

        $user->setWhitelisted($whitelist);
        $em->flush();

        $this->addFlash('success', $whitelist ? 'flash.user_whitelisted' : 'flash.user_whitelist_removed');

        return $this->redirectToRoute('user', [
            'username' => $user->getUsername(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function listForumBans(User $user, ForumBanRepository $repository, int $page): Response {
        return $this->render('user/forum_bans.html.twig', [
            'bans' => $repository->findActiveBansByUser($user, $page),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     */
    public function hiddenForums(User $user, int $page): Response {
        return $this->render('user/hidden_forums.html.twig', [
            'forums' => $user->getPaginatedHiddenForums($page),
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit_user", subject="user", statusCode=403)
     */
    public function hideForum(EntityManagerInterface $em, Request $request, User $user, Forum $forum, bool $hide): Response {
        $this->validateCsrf('hide_forum', $request->request->get('token'));

        if ($hide) {
            $user->hideForum($forum);
        } else {
            $user->unhideForum($forum);
        }

        $em->flush();

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirectToRoute('hidden_forums', [
            'username' => $this->getUser()->getUsername(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function toggleNightMode(EntityManagerInterface $em, Request $request, bool $enabled): Response {
        $this->validateCsrf('toggle_night_mode', $request->request->get('token'));

        $this->getUser()->setNightMode($enabled);
        $em->flush();

        if ($request->getRequestFormat() === 'json') {
            return $this->json(['night_mode' => $enabled]);
        }

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirectToRoute('front');
    }
}
