<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
	#[Route(path: '/login', name: 'app_login')]
	public function login(AuthenticationUtils $authenticationUtils): Response
	{
		// get the login error if there is one
		$error = $authenticationUtils->getLastAuthenticationError();

		// last username entered by the user
		$lastUsername = $authenticationUtils->getLastUsername();

		return $this->render('security/login.html.twig', [
			'last_username' => $lastUsername,
			'error' => $error,
		]);
	}

	#[Route('/register', name: 'app_register')]
	public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
	{
		// redirect to subscription management if user is already logged in
		if ($this->getUser()) {
			/** @var User $user */
			$user = $this->getUser();

			return $this->redirectToRoute('app_subscription_manage', ['id' => $user->getId()]);
		}

		$user = new User();
		$form = $this->createForm(RegistrationFormType::class, $user);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			/** @var string $plainPassword */
			$plainPassword = $form->get('plainPassword')->getData();
			/** @var string $email */
			$email = $form->get('email')->getData();

			$userRepository = $entityManager->getRepository(User::class);
			$existingUser = $userRepository->findOneByEmail($email);

			// check if user with the same email already exists
			if ($existingUser) {
				$this->addFlash('error', 'Une erreur est survenue lors de l\'inscription.');
				return $this->redirectToRoute('app_register');
			}

			// hash password
			$user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

			$entityManager->persist($user);
			$entityManager->flush();

			return $this->redirectToRoute('app_subscription');
		}

		return $this->render('registration/register.html.twig', [
			'registrationForm' => $form,
		]);
	}

	#[Route(path: '/logout', name: 'app_logout')]
	public function logout(): void
	{
		throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
	}
}
