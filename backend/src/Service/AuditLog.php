<?php

namespace App\Service;

use App\Security\User;
use Symfony\Component\HttpFoundation\Request;

class AuditLog
{
    public function __construct(private Database $database) {}

    public function prune(): void
    {
        $this->database->connection()->exec("DELETE FROM audit_events WHERE created_at < CURRENT_TIMESTAMP - INTERVAL '30 days'");
    }

    public function attemptKey(Request $request): string
    {
        $email = $request->request->get('email', '');
        return hash('sha256', ($request->getClientIp() ?? '').'|'.strtolower(trim(is_string($email) ? $email : '')));
    }

    public function record(string $type, Request $request, ?User $actor = null, ?int $targetId = null): void
    {
        $this->prune();
        $user = $actor?->toArray();
        $candidate = $request->request->get('email', '');
        $email = $user['email'] ?? (is_string($candidate) && strlen($candidate) <= 180 && filter_var($candidate, FILTER_VALIDATE_EMAIL) ? strtolower($candidate) : null);
        $ip = $request->getClientIp();
        $query = $this->database->connection()->prepare('INSERT INTO audit_events (type, actor_id, actor_name, actor_email, client_ip, attempt_key, target_id) VALUES (:type, :actor, :name, :email, :ip, :key, :target)');
        $query->execute([
            'type' => $type, 'actor' => $user['id'] ?? null, 'name' => $user['name'] ?? null,
            'email' => $email, 'ip' => filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null,
            'key' => str_starts_with($type, 'login_') ? $this->attemptKey($request) : null, 'target' => $targetId,
        ]);
    }

    public function throttled(Request $request): bool
    {
        $query = $this->database->connection()->prepare("SELECT COUNT(*) FROM audit_events WHERE type = 'login_failure' AND attempt_key = :key AND created_at > CURRENT_TIMESTAMP - INTERVAL '15 minutes'");
        $query->execute(['key' => $this->attemptKey($request)]);
        return (int) $query->fetchColumn() >= 5;
    }
}
