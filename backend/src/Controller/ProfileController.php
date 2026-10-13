<?php

namespace App\Controller;

use App\Security\UserProvider;
use App\Service\AuditLog;
use App\Service\Avatar;
use App\Service\Database;
use PDO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/profile')]
class ProfileController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function show(): JsonResponse { return $this->json($this->getUser()->toArray()); }

    #[Route('', methods: ['PUT'])]
    public function update(Request $request, Database $database, UserProvider $provider, AuditLog $audit): JsonResponse
    {
        $this->checkCsrf($request);
        $data = $request->toArray();
        $map = ['name' => 'name', 'department' => 'department', 'jobTitle' => 'job_title', 'bio' => 'bio', 'readingSize' => 'reading_size', 'accent' => 'accent'];
        if (!$data || array_diff(array_keys($data), array_keys($map))) { return $this->json(['error' => 'Ce champ de profil ne peut pas être modifié.'], 422); }
        $current = $this->getUser()->toArray();
        $values = [];
        foreach (['name' => 100, 'department' => 80, 'jobTitle' => 80, 'bio' => 300] as $key => $limit) {
            $value = array_key_exists($key, $data) ? $data[$key] : $current[$key];
            if (!is_string($value) || mb_strlen(trim($value)) > $limit || ($key === 'name' && trim($value) === '')) {
                return $this->json(['error' => 'Vérifiez le nom et la longueur des informations du profil.'], 422);
            }
            $values[$map[$key]] = trim($value);
        }
        foreach (['readingSize' => ['standard', 'large'], 'accent' => ['indigo', 'teal', 'slate']] as $key => $allowed) {
            $value = array_key_exists($key, $data) ? $data[$key] : $current[$key];
            if (!in_array($value, $allowed, true)) { return $this->json(['error' => 'Préférence invalide.'], 422); }
            $values[$map[$key]] = $value;
        }
        $values['id'] = $current['id'];
        $query = $database->connection()->prepare('UPDATE users SET name = :name, department = :department, job_title = :job_title, bio = :bio, reading_size = :reading_size, accent = :accent WHERE id = :id');
        $query->execute($values);
        $audit->record('profile_updated', $request, $this->getUser());
        return $this->json($provider->loadUserByIdentifier($current['email'])->toArray());
    }

    #[Route('/avatar', methods: ['GET'])]
    public function avatar(Database $database): Response
    {
        $query = $database->connection()->prepare('SELECT avatar FROM users WHERE id = :id');
        $query->execute(['id' => $this->getUser()->toArray()['id']]);
        $bytes = $query->fetchColumn();
        if (!$bytes) { throw $this->createNotFoundException(); }
        if (is_resource($bytes)) { $bytes = stream_get_contents($bytes); }
        return new Response($bytes, 200, ['Content-Type' => 'image/webp', 'Content-Length' => strlen($bytes)]);
    }

    #[Route('/avatar', methods: ['PUT', 'DELETE'])]
    public function updateAvatar(Request $request, Database $database, Avatar $avatar, UserProvider $provider, AuditLog $audit): JsonResponse
    {
        $this->checkCsrf($request);
        $bytes = null;
        if ($request->isMethod('PUT')) {
            $data = $request->toArray();
            if (array_keys($data) !== ['image']) { return $this->json(['error' => 'Choisissez une image PNG, JPEG ou WebP.'], 400); }
            $bytes = $avatar->normalize($data['image']);
        }
        $user = $this->getUser()->toArray();
        $query = $database->connection()->prepare('UPDATE users SET avatar = :avatar, avatar_version = avatar_version + 1 WHERE id = :id');
        $query->bindValue('avatar', $bytes, $bytes === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
        $query->bindValue('id', $user['id'], PDO::PARAM_INT);
        $query->execute();
        $audit->record('profile_updated', $request, $this->getUser());
        return $this->json($provider->loadUserByIdentifier($user['email'])->toArray());
    }

    private function checkCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('api', $request->headers->get('X-CSRF-Token'))) { throw $this->createAccessDeniedException(); }
    }
}
