<?php

namespace App\Controller\Security;

use App\Constant\SecurityConstant;
use App\Entity\User;
use App\Form\ForgotPasswordType;
use App\Form\RegistrationFormType;
use App\Form\ResendConfirmationEmailType;
use App\Form\RetrievePasswordType;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request,SecurityService $securityService): Response
    {
        $form = $this->createForm(RegistrationFormType::class, new User());

        if($request->isMethod('POST')){
            $result = $securityService->register($request, $form);

            $this->addFlash(
                $result->getData()['messageType'] ?? SecurityConstant::flash_type_error,
                $result->getData()['messageFlash'] ?? SecurityConstant::error_unknown
            );

            return $this->redirectToRoute(!$result->hasError() ? 'app_login' : 'app_register');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/register/verify/email", name="app_verify_email")
     */
    public function verifyUserEmail(Request $request, SecurityService $securityService): Response
    {
        $result = $securityService->verifyUserEmail($request);

        $this->addFlash($result->getData()['messageType'] ?? SecurityConstant::flash_type_error, $result->getData()['messageFlash'] ?? SecurityConstant::error_unknown);

        return $this->redirectToRoute('app_login');
    }

    /**
     * @Route("/register/resend/confirmation-email", name="app_resend_confirmation_email")
     */
    public function resendConfirmationEmail(Request $request, SecurityService $securityService): Response
    {
        $form = $this->createForm(ResendConfirmationEmailType::class);

        if ($request->isMethod('POST')) {
            $result = $securityService->resendConfirmationEmail($request, $form);

            $this->addFlash(
                $result->getData()['messageType'] ?? SecurityConstant::flash_type_error,
                $result->getData()['messageFlash'] ?? SecurityConstant::error_unknown
            );

            return $this->redirectToRoute( 'app_login');
        }

        return $this->render('registration/resend_confirmation_email.html.twig', [
            'resend_confirmation_email' => $form->createView(),
        ]);
    }

    /**
     * @Route("/register/forgot/password", name="app_forgot_password")
     */
    public function forgotPassword(Request $request, SecurityService $securityService): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);

        if ($request->isMethod('POST')) {
            $result = $securityService->forgotPassword($request, $form);

            $this->addFlash(
                $result->getData()['messageType'] ?? SecurityConstant::flash_type_error,
                $result->getData()['messageFlash'] ?? SecurityConstant::error_unknown
            );

            return $this->redirectToRoute( 'app_login');
        }

        return $this->render('registration/forgot_password.html.twig', [
            'forgot_password' => $form->createView(),
        ]);
    }

    /**
     * @Route("/register/retrieve/password", name="app_retrieve_password")
     */
    public function retrievePassword(Request $request, SecurityService $securityService, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($request->query->get('id',''));

        if($user !== null){
            $form = $this->createForm(RetrievePasswordType::class, $user);

            if($request->isMethod('POST')){
                $result = $securityService->retrievePassword($request, $form);
                $this->addFlash($result->getData()['messageType'] ?? SecurityConstant::flash_type_error, $result->getData()['messageFlash'] ?? SecurityConstant::error_unknown);
                return $this->redirectToRoute( 'app_login');
            }
            return $this->render('registration/retrieve_password.html.twig', [
                'retrieve_password_form' => $form->createView(),
            ]);
        }

        $this->addFlash(SecurityConstant::flash_type_error, SecurityConstant::error_user_unknown);
        return $this->redirectToRoute('app_login');
    }
}
