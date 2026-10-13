<?php

namespace App\Controller;

use App\Service\Database;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class FavoriteController extends AbstractController
{
    #[Route('/api/documents/{id}/favorite', requirements: ['id' => '\d+'], methods: ['PUT', 'DELETE'])]
    public function update(int $id, Request $request, Database $database): JsonResponse
    {
        if (!$this->isCsrfTokenValid('api', $request->headers->get('X-CSRF-Token'))) { throw $this->createAccessDeniedException(); }
        $db = $database->connection();
        $query = $db->prepare('SELECT 1 FROM documents WHERE id = :id');
        $query->execute(['id' => $id]);
        if (!$query->fetchColumn()) { throw $this->createNotFoundException(); }
        $saved = $request->isMethod('PUT');
        $sql = $saved ? 'INSERT INTO document_favorites (user_id, document_id) VALUES (:user, :document) ON CONFLICT DO NOTHING' : 'DELETE FROM document_favorites WHERE user_id = :user AND document_id = :document';
        $query = $db->prepare($sql);
        $query->execute(['user' => $this->getUser()->toArray()['id'], 'document' => $id]);
        return $this->json(['favorite' => $saved]);
    }
}
