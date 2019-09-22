<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Form\RegistrationFormType;
use App\Form\ResetPasswordType;
use App\Form\DoResetType;
use App\Security\LoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/reset-password", name="app_reset_password")
     */
    public function resetPasswordFrontend(Request $request, UserRepository $userRepository, \Swift_Mailer $mailer): Response
    {
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $savedUser = $userRepository->findOneByEmail($data['email']);
            if($savedUser)
            {
                $savedUser->setResetPasswordHash(uniqid("", true));

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->flush();

                $message = (new \Swift_Message('Action required: Reset your password'))
                    ->setFrom('contact@webbamboo.net')
                    ->setTo($savedUser->getEmail())
                    ->setBody(
                        $this->renderView(
                            // templates/emails/registration.html.twig
                            'emails/reset.html.twig',
                            ['user' => $savedUser]
                        ),
                        'text/html'
                    )
                ;

                $mailer->send($message);
                $this->addFlash('success', 'We`ve sent you a confirmation link');
            }
            else
            {
                $this->addFlash('fail', 'A user with this email does not exist');
            }
        }

        return $this->render('registration/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/reset/{hash}", name="app_do_reset")
     */
    public function doReset($hash, Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = $userRepository->findOneBy(['reset_password_hash' => $hash]);
        if(!$user)
        {
            throw new \Exception("Invalid Hash");
        }

        $form = $this->createForm(DoResetType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPasswordString = $form->get('newPassword')->getData();
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $newPasswordString
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addFlash('success', 'Password changed');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/doReset.html.twig', [
            'doResetForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, GuardAuthenticatorHandler $guardHandler, LoginFormAuthenticator $authenticator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
