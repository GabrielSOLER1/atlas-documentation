<?php

namespace App\EventSubscriber;

use App\Service\AuditLog;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SecuritySubscriber implements EventSubscriberInterface
{
    public function __construct(private bool $enforceHttps, private string $publicUrl, private AuditLog $audit, private TokenStorageInterface $tokens) {}

    public static function getSubscribedEvents(): array
    {
        return ['kernel.request' => ['request', 128], 'kernel.controller' => 'controller', 'kernel.response' => ['response', -256]];
    }

    public function request(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) { return; }
        $request = $event->getRequest();
        if ($this->enforceHttps && !$request->isSecure()) {
            if (!preg_match('#^https://[a-zA-Z0-9.:-]+/?$#', $this->publicUrl)) { throw new \LogicException('Invalid HTTPS origin.'); }
            $event->setResponse(new RedirectResponse(rtrim($this->publicUrl, '/').$request->getRequestUri(), 308));
            return;
        }
        if ($request->getPathInfo() === '/api/login' && $request->isMethod('POST') && $this->audit->throttled($request)) {
            $event->setResponse(new JsonResponse(['error' => 'Trop de tentatives. Réessayez dans 15 minutes.'], 429, ['Retry-After' => '900']));
        }
    }

    public function controller(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) { return; }
        $request = $event->getRequest();
        if (!$this->tokens->getToken()?->getUser()) { return; }
        $session = $request->getSession();
        $previous = $session->get('last_activity', time());
        if (time() - $previous > 1800) {
            $this->tokens->setToken(null);
            $session->invalidate();
            $response = str_starts_with($request->getPathInfo(), '/api/')
                ? new JsonResponse(['error' => 'Votre session a expiré. Connectez-vous à nouveau.'], 401)
                : new RedirectResponse('/#/connexion', 303);
            $event->setController(static fn () => $response);
            return;
        }
        $session->set('last_activity', time());
    }

    public function response(ResponseEvent $event): void
    {
        $headers = $event->getResponse()->headers;
        $headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; font-src 'self'; connect-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'no-referrer');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $headers->set('Cache-Control', 'no-store');
        if ($event->getRequest()->isSecure()) { $headers->set('Strict-Transport-Security', 'max-age=15552000'); }
    }
}
