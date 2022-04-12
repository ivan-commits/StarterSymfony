<?php

namespace App\Controller\Security;

use App\Constant\SecurityConstant;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\SendConfirmationEmailType;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private $entityManager;
    private $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request,SecurityService $securityService): Response
    {
        $form = $this->createForm(RegistrationFormType::class, new User());

        if($request->isMethod('POST')){
            $result = $securityService->register($request, $form);

            $this->addFlash($result->getData()['messageType'] ?? 'error',$result->getData()['messageFlash'] ?? 'error registration');

            return $this->redirectToRoute(!$result->hasError() ? 'app_login' : 'app_register');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verify/email", name="app_verify_email")
     */
    public function verifyUserEmail(Request $request): Response
    {
        $user = $this->userRepository->find($request->query->get('id',''));

        if (!$user || $user->isBanned() || $user->isVerified()) {
            throw $this->createNotFoundException();
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', $exception->getReason());
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('app_login',['email_verified'=>'Your email address has been verified.']);
    }

    /**
     * @Route("/send/confirmation/email", name="app_send_confirmation_email")
     */
    public function sendConfirmationEmail(Request $request, SecurityService $securityService): Response
    {
        $form = $this->createForm(SendConfirmationEmailType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $credentials= ['email'=>$form->getData()['email'] ?? null,'password'=>$form->getData()['password'] ?? null];
            $authenticated = $securityService->authenticateUser($credentials);
            $messageFlash = $authenticated ? SecurityConstant::success_send_confirmation_email : SecurityConstant::error_send_confirmation_email;
            $messageType = $authenticated ? 'success' : 'error';
            $this->addFlash($messageType,$messageFlash);
            if($authenticated){
                $user = $this->userRepository->findOneBy(['email'=>$credentials['email']]);
                $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                    (new TemplatedEmail())
                        ->from(new Address('registration@tchat.com', 'ivan'))
                        ->to($user->getEmail())
                        ->subject('Please Confirm your Email')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                );
                return $this->redirectToRoute('app_login');
            }
        }
        return $this->render('registration/send_confirmation_email.html.twig', [
            'sendConfirmationEmail' => $form->createView(),
        ]);
    }
}
