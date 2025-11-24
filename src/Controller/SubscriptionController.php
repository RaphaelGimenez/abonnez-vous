<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\SubscriptionBillingPeriod;
use App\Enum\SubscriptionStatus;
use App\Exception\AlreadySubscribedException;
use App\Exception\InvalidSubscriptionStatusException;
use App\Repository\PlanRepository;
use App\Service\SubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SubscriptionController extends AbstractController
{
  public function __construct(private SubscriptionService $subscriptionService) {}

  #[Route('/subscription/subscribe', name: 'app_subscription')]
  public function index(PlanRepository $planRepository): Response
  {
    $plans = $planRepository->findAll();

    /** @var User|null $user */
    $user = $this->getUser();
    $subscription = $user?->getSubscription();
    $userPlan = $subscription?->getPlan();

    return $this->render('subscription/index.html.twig', [
      'plans' => $plans,
      'userPlan' => $userPlan,
      'userSubscription' => $subscription,
      'manageUrl' => $this->generateUrl('app_subscription_manage'),
    ]);
  }

  #[Route('/subscription/manage', name: 'app_subscription_manage')]
  #[IsGranted('IS_AUTHENTICATED_FULLY')]
  public function manage(): Response
  {
    /** @var User $user */
    $user = $this->getUser();
    $subscription = $user->getSubscription();

    if (!$subscription) {
      $this->addFlash('error', 'Vous n\'avez pas d\'abonnement actif à gérer.');
      return $this->redirectToRoute('app_subscription');
    }

    return $this->render('subscription/manage.html.twig', [
      'subscription' => $subscription,
    ]);
  }


  #[Route('/subscription/subscribe/plan/{id}', name: 'app_subscription_subscribe', methods: ['POST'])]
  #[IsGranted('IS_AUTHENTICATED_FULLY')]
  public function subscribe(
    PlanRepository $planRepository,
    Request $request,
    CsrfTokenManagerInterface $csrfTokenManager
  ): Response {
    /** @var User $user */
    $user = $this->getUser();
    $planId = $request->attributes->get('id');

    // Validate CSRF token
    $token = new CsrfToken('subscribe' . $planId, $request->request->get('_token'));
    if (!$csrfTokenManager->isTokenValid($token)) {
      throw $this->createAccessDeniedException('Invalid CSRF token.');
    }

    $billingPeriod = SubscriptionBillingPeriod::tryFrom($request->request->get('billing_period'));

    // Validate billing period
    if (!$billingPeriod) {
      $this->addFlash('error', 'Invalid billing period.');
      return $this->redirectToRoute('app_subscription');
    }

    $plan = $planRepository->find($planId);
    if (!$plan) {
      $this->addFlash('error', 'Selected plan does not exist.');
      return $this->redirectToRoute('app_subscription');
    }

    try {
      $this->subscriptionService->createSubscription($user, $plan, $billingPeriod);
      $this->addFlash('success', 'Vous vous êtes abonné avec succès au plan ' . $plan->getName() . '.');
    } catch (AlreadySubscribedException $e) {
      $this->addFlash('error', $e->getMessage());
    }

    return $this->redirectToRoute('app_subscription');
  }

  #[Route('/subscription/cancel', name: 'app_subscription_cancel', methods: ['POST'])]
  #[IsGranted('IS_AUTHENTICATED_FULLY')]
  public function cancel(
    Request $request,
    CsrfTokenManagerInterface $csrfTokenManager
  ): Response {
    /** @var User $user */
    $user = $this->getUser();
    $subscription = $user->getSubscription();

    if (!$subscription) {
      $this->addFlash('error', 'Vous n\'avez pas d\'abonnement actif à annuler.');
      return $this->redirectToRoute('app_subscription_manage');
    }

    // Validate CSRF token
    $token = new CsrfToken('cancel_subscription', $request->request->get('_token'));
    if (!$csrfTokenManager->isTokenValid($token)) {
      throw $this->createAccessDeniedException('Invalid CSRF token.');
    }

    try {
      $this->subscriptionService->cancelSubscription($subscription);
      $this->addFlash('success', 'Votre abonnement a été annulé avec succès.');
    } catch (InvalidSubscriptionStatusException $e) {
      $this->addFlash('error', $e->getMessage());
    }

    return $this->redirectToRoute('app_subscription_manage');
  }

  #[Route('/subscription/resume', name: 'app_subscription_resume', methods: ['POST'])]
  #[IsGranted('IS_AUTHENTICATED_FULLY')]
  public function resume(
    Request $request,
    CsrfTokenManagerInterface $csrfTokenManager
  ): Response {
    /** @var User $user */
    $user = $this->getUser();
    $subscription = $user->getSubscription();

    if (!$subscription) {
      $this->addFlash('error', 'Vous n\'avez pas d\'abonnement à réactiver.');
      return $this->redirectToRoute('app_subscription_manage');
    }

    // Validate CSRF token
    $token = new CsrfToken('resume_subscription', $request->request->get('_token'));
    if (!$csrfTokenManager->isTokenValid($token)) {
      throw $this->createAccessDeniedException('Invalid CSRF token.');
    }

    try {
      $this->subscriptionService->resumeSubscription($subscription);
      $this->addFlash('success', 'Votre abonnement a été repris avec succès.');
    } catch (InvalidSubscriptionStatusException $e) {
      $this->addFlash('error', $e->getMessage());
    }

    return $this->redirectToRoute('app_subscription_manage');
  }
}
