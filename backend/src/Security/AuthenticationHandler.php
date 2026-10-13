<?php

namespace App\Security;

use App\Service\AuditLog;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface, AuthenticationEntryPointInterface
{
    public function __construct(private AuditLog $audit) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $request->getSession()->set('last_activity', time());
        $this->audit->record('login_success', $request, $token->getUser());
        error_log('[Atlas] Connexion réussie.');
        return new JsonResponse(['user' => $token->getUser()->toArray()]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $this->audit->record('login_failure', $request);
        error_log('[Atlas] Connexion refusée.');
        return new JsonResponse(['error' => 'Connexion refusée. Vérifiez vos identifiants ou rechargez la page.'], 401);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['error' => 'Connectez-vous pour accéder aux procédures.'], 401);
    }
}
