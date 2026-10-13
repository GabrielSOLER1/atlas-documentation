<?php

namespace App\Controller;

use App\Service\Database;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class RoadmapController extends AbstractController
{
    private function content(): array { return json_decode(file_get_contents(dirname(__DIR__, 2).'/database/roadmap.json'), true, 32, JSON_THROW_ON_ERROR); }

    #[Route('/api/roadmap', methods: ['GET'])]
    public function show(Database $database): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $query = $database->connection()->prepare('SELECT roadmap_progress FROM users WHERE id = :id');
        $query->execute(['id' => $this->getUser()->toArray()['id']]);
        return $this->json($this->content() + ['completed' => json_decode($query->fetchColumn(), true, 32, JSON_THROW_ON_ERROR)]);
    }

    #[Route('/api/roadmap', methods: ['PATCH'])]
    public function update(Request $request, Database $database): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('api', $request->headers->get('X-CSRF-Token'))) { throw $this->createAccessDeniedException(); }
        $data = $request->toArray();
        $allowed = array_column($this->content()['nodes'], 'id');
        if (array_keys($data) !== ['completed'] || !is_array($data['completed']) || !array_is_list($data['completed']) || count($data['completed']) > count($allowed)) {
            return $this->json(['error' => 'Progression invalide.'], 422);
        }
        foreach ($data['completed'] as $id) {
            if (!is_string($id) || !in_array($id, $allowed, true)) { return $this->json(['error' => 'Étape inconnue.'], 422); }
        }
        $completed = array_values(array_unique($data['completed']));
        $query = $database->connection()->prepare('UPDATE users SET roadmap_progress = :progress WHERE id = :id');
        $query->execute(['progress' => json_encode($completed, JSON_THROW_ON_ERROR), 'id' => $this->getUser()->toArray()['id']]);
        return $this->json(['completed' => $completed]);
    }
}
