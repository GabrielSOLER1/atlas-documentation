<?php

namespace App\Controller;

use App\Security\User;
use App\Service\Database;
use App\Service\AuditLog;
use PDOException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(Database $database): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->json($database->connection()->query(
            'SELECT id, email, name, role, active, created_at FROM users ORDER BY created_at DESC, id DESC'
        )->fetchAll());
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, Database $database, UserPasswordHasherInterface $hasher, AuditLog $audit): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->checkCsrf($request);
        $data = $request->toArray();
        $name = is_string($data['name'] ?? null) ? trim($data['name']) : '';
        $email = is_string($data['email'] ?? null) ? strtolower(trim($data['email'])) : '';
        $password = $data['password'] ?? null;
        $role = $data['role'] ?? null;
        if ($name === '' || mb_strlen($name) > 100) {
            return $this->json(['error' => 'Le nom doit contenir entre 1 et 100 caractères.'], 422);
        }
        if (strlen($email) > 180 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Saisissez une adresse e-mail valide.'], 422);
        }
        if (!is_string($password) || mb_strlen($password) < 12 || trim($password) === '') {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins 12 caractères.'], 422);
        }
        if (strlen($password) > 72) {
            return $this->json(['error' => 'Le mot de passe est trop long. Choisissez une valeur plus courte.'], 422);
        }
        if (!in_array($role, ['ROLE_USER', 'ROLE_ADMIN'], true)) {
            return $this->json(['error' => 'Choisissez un rôle valide.'], 422);
        }
        $statement = $database->connection()->prepare(
            'INSERT INTO users (email, password, name, role) VALUES (:email, :password, :name, :role) RETURNING id, email, name, role, active, created_at'
        );
        try {
            $statement->execute([
                'email' => $email, 'password' => $hasher->hashPassword(new User($email, '', $name, $role), $password),
                'name' => $name, 'role' => $role,
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23505') { throw $exception; }
            return $this->json(['error' => 'Un compte utilise déjà cette adresse e-mail.'], 409);
        }
        $user = $statement->fetch();
        error_log(sprintf('[Atlas] Compte %d créé par le compte %d.', $user['id'], $this->getUser()->toArray()['id']));
        $audit->record('user_created', $request, $this->getUser(), (int) $user['id']);
        return $this->json($user, 201);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request, Database $database, AuditLog $audit): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->checkCsrf($request);
        $data = $request->toArray();
        if (!$data || array_diff(array_keys($data), ['role', 'active'])) {
            return $this->json(['error' => 'Seuls le rôle et le statut du compte peuvent être modifiés.'], 422);
        }
        if (array_key_exists('role', $data) && !in_array($data['role'], ['ROLE_USER', 'ROLE_ADMIN'], true)) {
            return $this->json(['error' => 'Choisissez un rôle valide.'], 422);
        }
        if (array_key_exists('active', $data) && !is_bool($data['active'])) {
            return $this->json(['error' => 'Le statut du compte est invalide.'], 422);
        }
        $db = $database->connection();
        $db->beginTransaction();
        try {
            // Lock accounts before checking administrator availability.
            $users = $db->query('SELECT id, role, active FROM users ORDER BY id FOR UPDATE')->fetchAll();
            $target = null;
            $adminCount = 0;
            foreach ($users as $user) {
                if ((int) $user['id'] === $id) { $target = $user; }
                if ($user['role'] === 'ROLE_ADMIN' && $user['active']) { ++$adminCount; }
            }
            if (!$target) { throw $this->createNotFoundException('Compte inexistant.'); }
            $role = $data['role'] ?? $target['role'];
            $active = $data['active'] ?? $target['active'];
            if ($id === $this->getUser()->toArray()['id'] && (!$active || $role !== 'ROLE_ADMIN')) {
                $db->rollBack();
                return $this->json(['error' => 'Vous ne pouvez pas désactiver votre propre compte ni retirer votre rôle administrateur.'], 409);
            }
            if ($target['role'] === 'ROLE_ADMIN' && $target['active'] && (!$active || $role !== 'ROLE_ADMIN') && $adminCount <= 1) {
                $db->rollBack();
                return $this->json(['error' => 'Au moins un administrateur actif doit être conservé.'], 409);
            }
            $statement = $db->prepare('UPDATE users SET role = :role, active = :active WHERE id = :id RETURNING id, email, name, role, active, created_at');
            $statement->execute(['id' => $id, 'role' => $role, 'active' => $active ? 'true' : 'false']);
            $updated = $statement->fetch();
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $exception;
        }
        error_log(sprintf('[Atlas] Compte %d mis à jour par le compte %d (rôle %s, actif %s).', $id, $this->getUser()->toArray()['id'], $role, $active ? 'oui' : 'non'));
        $audit->record('user_updated', $request, $this->getUser(), $id);
        return $this->json($updated);
    }

    private function checkCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('api', $request->headers->get('X-CSRF-Token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
