<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UsersAuthenticator $authenticator, EntityManagerInterface $entityManager, SendMailService $mail, JWTService $jwt): Response
    {
        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
            $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            // Create header
            $header = [
                'alg' => 'HS256',
                'type' => 'JWT'
            ];

            //Create payload
            $payload = [
                'user_id' => $user->getId()
            ];

            // Generate Token
            $token = $jwt->generate($header, $payload, $this->getparameter('app.jwtsecret'));

            //Sent a mail
            $mail->send(
                'no-reply@monsite.com',
                $user->getEmail(),
                'Activation de votre compte sur le site e-commerce',
                'register',
                [
                    'user' => $user,
                    'token' => $token
                ]
            );

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verif/{token}", name="verify_user")
     */
    public function VerifyUser($token, JWTService $jwt, UsersRepository $usersRepository, EntityManagerInterface $emi): Response
    {
        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret')))
        {
            //Catch the payload
            $payload = $jwt->getPayload($token);
            //Catch the Token's user
            $user = $usersRepository->find($payload['user_id']);
            //Check if the user exist and not active yet his account
            if($user && !$user->getIsVerified()){
                $user->setIsVerified(true);
                $emi->flush($user);

                $this->addFlash('success', 'Utilisateur activé');
                return $this->redirectToRoute('products_index');
            }
        }
        $this->addFlash('danger', 'Le token est invalide ou a expiré');
        return $this->redirectToRoute('app_login');

    }
}
