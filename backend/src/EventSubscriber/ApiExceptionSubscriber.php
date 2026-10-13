<?php

namespace App\EventSubscriber;

use PDOException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['kernel.exception' => ['onException', -64]];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        if ($exception instanceof AccessDeniedException) { $status = 403; }
        if ($exception instanceof PDOException) { $status = 503; }
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            $file = dirname(__DIR__, 2).'/public/errors/404.html';
            $html = file_get_contents($file);
            if ($status !== 404) { $html = str_replace(['404', 'Cette page ne fait pas partie du guide.', 'L’adresse est introuvable ou le contenu n’est plus disponible.'], [(string) $status, 'Le portail est temporairement indisponible.', 'Réessayez plus tard ou contactez votre service informatique.'], $html); }
            $event->setResponse(new \Symfony\Component\HttpFoundation\Response($html, $status));
            return;
        }
        $messages = [
            400 => 'Les données envoyées sont invalides.',
            403 => 'Vous ne disposez pas des droits nécessaires ou la session doit être actualisée.',
            404 => 'Cette procédure ou cette adresse n’existe pas.',
            405 => 'Cette action n’est pas disponible.',
            409 => 'Cette action entre en conflit avec l’état actuel du compte.',
            413 => 'Le contenu envoyé est trop volumineux.',
            429 => 'Trop de tentatives. Réessayez plus tard.',
            503 => 'La base de données est indisponible. Réessayez plus tard.',
        ];
        error_log(sprintf('[Atlas] HTTP %d — %s — %s', $status, $event->getRequest()->getPathInfo(), $exception->getMessage()));
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];
        $event->setResponse(new JsonResponse(['error' => $messages[$status] ?? 'Une erreur interne est survenue.'], $status, $headers));
    }
}
