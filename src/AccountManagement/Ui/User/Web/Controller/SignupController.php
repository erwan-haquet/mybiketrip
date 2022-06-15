<?php

namespace App\AccountManagement\Ui\User\Web\Controller;

use App\AccountManagement\Application\User\Command\Signup;
use App\AccountManagement\Domain\User\Repository\UserRepositoryInterface;
use App\AccountManagement\Infrastructure\User\Security\Authenticator;
use App\AccountManagement\Ui\User\Web\Form\SignupType;
use App\Supporting\Domain\I18n\Model\Locale;
use Library\CQRS\Command\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/signup', name: 'signup')]
class SignupController extends AbstractController
{
    public function __invoke(
        Request                    $request,
        UserRepositoryInterface    $repository,
        CommandBus                 $commandBus,
        UserAuthenticatorInterface $authenticator,
        Authenticator              $formAuthenticator
    ): Response
    {
        $id = $repository->nextIdentity();
        $command = new Signup([
            'id' => $id,
            'locale' => Locale::from($request->getLocale())
        ]);
        $form = $this->createForm(SignupType::class, $command);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandBus->handle($command);

            $user = $repository->findById($id);

            $this->addFlash('success', new TranslatableMessage(
                'account_management.signup.registered_with_success',
                ['username' => $command->username]
            ));
            
            return $authenticator->authenticateUser(
                $user, 
                $formAuthenticator, 
                $request
            );
        }

        return $this->render('web/account_management/signup/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}