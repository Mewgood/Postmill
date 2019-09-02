<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use App\Entity\UserBlock;
use App\Form\ConfirmDeletionType;
use App\Form\Model\UserBlockData;
use App\Form\Model\UserData;
use App\Form\Model\UserFilterData;
use App\Form\UserBiographyType;
use App\Form\UserBlockType;
use App\Form\UserFilterType;
use App\Form\UserSettingsType;
use App\Form\UserType;
use App\Mailer\ResetPasswordMailer;
use App\Message\DeleteUser;
use App\Repository\CommentRepository;
use App\Repository\ForumBanRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Security\AuthenticationHelper;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\SubmissionFinder;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * @Entity("user", expr="repository.findOneOrRedirectToCanonical(username, 'username')")
 */
final class UserController extends AbstractController {
    use TargetPathTrait;

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

    public function comments(CommentRepository $repository, User $user, int $page): Response {
        $comments = $user->getPaginatedComments($page);

        $repository->hydrate(...$comments);

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

    public function login(AuthenticationUtils $helper, ResetPasswordMailer $mailer, Request $request): Response {
        // store the last visited location if none exists
        if (!$this->getTargetPath($request->getSession(), 'main')) {
            $referer = $request->headers->get('Referer');

            if ($referer) {
                $this->saveTargetPath($request->getSession(), 'main', $referer);
            }
        }

        return $this->render('user/login.html.twig', [
            'can_reset_password' => $mailer->canMail(),
            'error' => $helper->getLastAuthenticationError(),
            'last_username' => $helper->getLastUsername(),
            'remember_me' => $request->getSession()->get('remember_me'),
        ]);
    }

    public function registration(Request $request, ObjectManager $em, AuthenticationHelper $auth): Response {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('front');
        }

        $data = new UserData();
        $data->setLocale($request->getLocale());

        $form = $this->createForm(UserType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $data->toUser();

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
    public function editUser(ObjectManager $em, User $user, Request $request): Response {
        $data = UserData::fromUser($user);

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
    public function userSettings(ObjectManager $em, User $user, Request $request): Response {
        $data = UserData::fromUser($user);

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
    public function editBiography(ObjectManager $em, User $user, Request $request): Response {
        $data = UserData::fromUser($user);

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
     * @Entity("blockee", expr="repository.findOneOrRedirectToCanonical(username, 'username')")
     */
    public function block(User $blockee, Request $request, ObjectManager $em): Response {
        /* @var User $blocker */
        $blocker = $this->getUser();

        if ($blocker->isBlocking($blockee)) {
            throw $this->createNotFoundException('The user is already blocked');
        }

        $data = new UserBlockData();

        $form = $this->createForm(UserBlockType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $block = $data->toBlock($blocker, $blockee);

            $em->persist($block);
            $em->flush();

            $this->addFlash('success', 'flash.user_blocked');

            return $this->redirectToRoute('user_block_list', [
                'username' => $blocker->getUsername(),
            ]);
        }

        return $this->render('user/block.html.twig', [
            'form' => $form->createView(),
            'user' => $blockee,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Security("user === block.getBlocker()", statusCode=403)
     */
    public function unblock(UserBlock $block, ObjectManager $em, Request $request): Response {
        $this->validateCsrf('unblock', $request->request->get('token'));

        $em->remove($block);
        $em->flush();

        $this->addFlash('success', 'flash.user_unblocked');

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
    public function clearNotifications(Request $request, NotificationRepository $repository, ObjectManager $em): Response {
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
    public function markAsTrusted(Request $request, User $user, ObjectManager $em, bool $trusted): Response {
        $this->validateCsrf('mark_trusted', $request->request->get('token'));

        $user->setTrusted($trusted);
        $em->flush();

        $this->addFlash('success', $trusted ? 'flash.user_marked_trusted' : 'flash.user_marked_untrusted');

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
    public function hideForum(ObjectManager $em, Request $request, User $user, Forum $forum, bool $hide): Response {
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
    public function toggleNightMode(ObjectManager $em, Request $request, bool $enabled): Response {
        $this->validateCsrf('toggle_night_mode', $request->request->get('token'));

        $this->getUser()->setNightMode($enabled);
        $em->flush();

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirectToRoute('front');
    }
}
