<?php

namespace App\Controller;

use App\Security\User;
use App\Service\Database;
use App\Service\AuditLog;
use App\Service\DocumentContent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class PortalController extends AbstractController
{
    #[Route('/', methods: ['GET'])]
    public function index(): Response
    {
        $file = $this->getParameter('kernel.project_dir').'/public/build/index.html';
        if (!is_file($file)) {
            return new Response('L’interface doit être construite : consultez le README.', 503);
        }
        return new Response(file_get_contents($file));
    }

    #[Route('/api/health', methods: ['GET'])]
    public function health(Database $database): JsonResponse
    {
        $database->connection()->query('SELECT 1');
        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/session', methods: ['GET'])]
    public function session(CsrfTokenManagerInterface $csrf): JsonResponse
    {
        $user = $this->getUser();
        return $this->json([
            'user' => $user instanceof User ? $user->toArray() : null,
            'loginToken' => $csrf->getToken('authenticate')->getValue(),
            'csrfToken' => $csrf->getToken('api')->getValue(),
        ]);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json(['error' => 'Connexion refusée.'], 401);
    }

    #[Route('/api/logout', methods: ['POST'])]
    public function logout(Request $request, TokenStorageInterface $tokens, AuditLog $audit): JsonResponse
    {
        $this->checkCsrf($request);
        $audit->record('logout', $request, $this->getUser());
        $tokens->setToken(null);
        $request->getSession()->invalidate();
        error_log('[Atlas] Déconnexion.');
        return $this->json(['message' => 'Vous êtes déconnecté.']);
    }

    #[Route('/api/documents', methods: ['GET'])]
    public function documents(Database $database): JsonResponse
    {
        $rows = $database->connection()->query('SELECT id, title, category, summary, updated_at FROM documents ORDER BY id')->fetchAll();
        return $this->json($rows);
    }

    #[Route('/api/documents/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function document(int $id, Database $database): JsonResponse
    {
        $statement = $database->connection()->prepare('SELECT * FROM documents WHERE id = :id');
        $statement->execute(['id' => $id]);
        $document = $statement->fetch();
        if (!$document) { throw $this->createNotFoundException('Procédure inexistante.'); }
        $document['blocks'] = $document['blocks'] ? json_decode($document['blocks'], true, 32, JSON_THROW_ON_ERROR) : null;
        return $this->json($document);
    }

    #[Route('/api/documents', methods: ['POST'])]
    public function create(Request $request, Database $database, DocumentContent $content, AuditLog $audit): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->checkCsrf($request);
        $data = $content->validate($request->toArray());
        $query = $database->connection()->prepare('INSERT INTO documents (slug, title, category, summary, content, blocks, created_by) VALUES (:slug, :title, :category, :summary, :content, :blocks, :actor) RETURNING id');
        $query->execute($data + ['slug' => 'guide-'.bin2hex(random_bytes(8)), 'actor' => $this->getUser()->toArray()['id']]);
        $id = (int) $query->fetchColumn();
        $audit->record('document_created', $request, $this->getUser(), $id);
        return $this->json(['id' => $id, 'message' => 'Votre documentation a été publiée.'], 201);
    }

    #[Route('/api/documents/{id}', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function edit(int $id, Request $request, Database $database, DocumentContent $content, AuditLog $audit): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->checkCsrf($request);
        $data = $content->validate($request->toArray());
        $statement = $database->connection()->prepare('UPDATE documents SET title = :title, category = :category, summary = :summary, content = :content, blocks = :blocks, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $statement->execute($data + ['id' => $id]);
        if ($statement->rowCount() === 0) { throw $this->createNotFoundException(); }
        $audit->record('document_updated', $request, $this->getUser(), $id);
        return $this->json(['message' => 'La procédure a été enregistrée.']);
    }

    private function checkCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('api', $request->headers->get('X-CSRF-Token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
