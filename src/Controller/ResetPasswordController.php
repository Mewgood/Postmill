<?php

namespace App\Controller;

use App\DataObject\UserData;
use App\Entity\User;
use App\Form\RequestPasswordResetType;
use App\Form\UserType;
use App\Mailer\ResetPasswordMailer;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResetPasswordController extends AbstractController {
    public function requestReset(Request $request, UserRepository $users, ResetPasswordMailer $mailer): Response {
        if (!$mailer->canMail()) {
            return $this->render('reset_password/cannot_reset.html.twig', [],
                new Response('', 403)
            );
        }

        $form = $this->createForm(RequestPasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData()->getEmail();

            // TODO - this is susceptible to timing attacks.
            // TODO - send only one email with all the links.
            foreach ($users->lookUpByEmail($email) as $user) {
                $mailer->mail($user, $request);
            }

            $this->addFlash('success', 'flash.reset_password_email_sent');

            return $this->redirectToRoute('front');
        }

        return $this->render('reset_password/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @ParamConverter("expires", options={"format": "U"}, converter="datetime")
     */
    public function reset(
        Request $request,
        EntityManagerInterface $em,
        User $user,
        ResetPasswordMailer $mailer,
        \DateTime $expires,
        string $checksum
    ): Response {
        if (!$mailer->validateChecksum($checksum, $user, $expires)) {
            throw $this->createNotFoundException('Invalid checksum');
        }

        if (new \DateTime('@'.time()) >= $expires) {
            throw $this->createNotFoundException('The link has expired');
        }

        $data = new UserData($user);

        $form = $this->createForm(UserType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateUser($user);

            $em->flush();

            $this->addFlash('success', 'flash.user_password_updated');

            return $this->redirectToRoute('front');
        }

        return $this->render('reset_password/reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
