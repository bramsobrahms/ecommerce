<?php

namespace App\Controller;

use App\Form\ResetPasswordType;
use App\Service\SendMailService;
use App\Repository\UsersRepository;
use App\Form\ResetPasswordRequestType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/forget-password", name="forgotten_password")
     */
    public function forgottenPassword(Request $request, UsersRepository $usersRepository, TokenGeneratorInterface $tokenGeneratorInterface, EntityManagerInterface $entityManagerInterface, SendMailService $mail): Response
    {
        $form = $this->createForm(ResetPasswordRequestType::class);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            //Search email from User
            $user = $usersRepository->findOneByEmail($form->get('email')->getData());
            //dd($user);

            //Check if we've an user
            if($user){
                //Generate a reset Token
                $token = $tokenGeneratorInterface->generateToken();
                $user->setResetToken($token);
                $entityManagerInterface->persist($user);
                $entityManagerInterface->flush();

                //Create a new link for new password
                $url = $this->generateUrl('reset_pass', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                
                //Create mail's data
                $context = [
                    'url' => $url,
                    'user' => $user
                ];

                //send email
                $mail->send('no-reply@ecommerce',
                $user->getEmail(),
                'Réinitialisation du mot de passe',
                'password_reset',
                $context    
                );

                $this->addFlash('success', 'Email bien envoyé');
                return $this->redirectToRoute('app_login');
            }
            $this->addFlash('danger', 'Un problème est survenu');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password_request.html.twig',[
            'requestPassForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/forget-password/{token}", name="reset_pass")
     */
    public function resetPass(string $token, Request $request, UsersRepository $usersRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        //Check if I've this Token
        $user = $usersRepository->findOneByResetToken($token);

        if($user){
            $form = $this->createForm(ResetPasswordType::class);

            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                //delete the token
                $user->setResetToken('');
                //Get the new Password
                $user->setPassword($userPasswordHasher->hashPassword(
                    $user, 
                    $form->get('password')->getData()
                ));

                //Put in database
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Le mot de passe a été modifier avec succès');
                return $this->redirectToRoute('app_login');
            }

            return $this->render('security/reset_password.html.twig',[
                'ResePassForm' => $form->createView()
            ]);
        }
        $this->addFlash('danger','il n\'est pas valide');
        return $this->redirectToRoute('app_login');
    }
}
