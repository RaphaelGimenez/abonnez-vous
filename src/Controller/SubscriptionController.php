<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\SubscriptionBillingPeriod;
use App\Enum\SubscriptionStatus;
use App\Repository\PlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SubscriptionController extends AbstractController
{
  #[Route('/subscription/subscribe', name: 'app_subscription')]
  public function index(PlanRepository $planRepository): Response
  {
    $plans = $planRepository->findAll();

    $userPlan = null;
    /** @var User $user */
    $user = $this->getUser();
    if ($user) {
      $subscription = $user->getSubscription();
      if ($subscription) {
        $userPlan = $subscription->getPlan();
      }
    }

    return $this->render('subscription/index.html.twig', [
      'plans' => $plans,
      'userPlan' => $userPlan,
    ]);
  }

  #[Route('/subscription/subscribe/{id}', name: 'app_subscription_subscribe', methods: ['POST'])]
  #[IsGranted('IS_AUTHENTICATED_FULLY')]
  public function subscribe(
    PlanRepository $planRepository,
    EntityManagerInterface $entityManager,
    Request $request,
    CsrfTokenManagerInterface $csrfTokenManager
  ): Response {
    /** @var User $user */
    $user = $this->getUser();
    if ($user->getSubscription()) {
      $this->addFlash('error', 'Vous avez déjà un abonnement actif.');
      return $this->redirectToRoute('app_subscription');
    }
    $planId = $request->attributes->get('id');

    // Validate CSRF token
    $token = new CsrfToken('subscribe' . $planId, $request->request->get('_token'));
    if (!$csrfTokenManager->isTokenValid($token)) {
      throw $this->createAccessDeniedException('Invalid CSRF token.');
    }

    $billingPeriod = $request->request->get('billing_period');

    // Validate billing period
    if (!SubscriptionBillingPeriod::tryFrom($billingPeriod)) {
      $this->addFlash('error', 'Invalid billing period.');
      return $this->redirectToRoute('app_subscription');
    }

    $plan = $planRepository->find($planId);
    if (!$plan) {
      $this->addFlash('error', 'Selected plan does not exist.');
      return $this->redirectToRoute('app_subscription');
    }

    // Create subscription
    $subscription = new Subscription();
    $subscription->setUser($user);
    $subscription->setPlan($plan);
    $subscription->setStatus(SubscriptionStatus::ACTIVE);
    $subscription->setBillingPeriod(SubscriptionBillingPeriod::from($billingPeriod));
    $subscription->setStartDate(new \DateTimeImmutable());
    if ($billingPeriod === 'annual') {
      $endDate = (new \DateTimeImmutable())->modify('+1 year');
    } else {
      $endDate = (new \DateTimeImmutable())->modify('+1 month');
    }
    $subscription->setEndDate($endDate);
    $subscription->setAutoRenew(true);

    $entityManager->persist($subscription);
    $entityManager->flush();

    $this->addFlash('success', 'Vous vous êtes abonné avec succès au plan ' . $plan->getName() . '.');

    return $this->redirectToRoute('app_subscription');
  }
}
