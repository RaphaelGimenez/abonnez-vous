<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
  public function __construct(
    private UrlGeneratorInterface $urlGenerator,
  ) {}

  public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
  {
    $url = $this->urlGenerator->generate('app_hello_greet');

    return new RedirectResponse($url);
  }
}
