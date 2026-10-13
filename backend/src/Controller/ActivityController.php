<?php

namespace App\Controller;

use App\Service\AuditLog;
use App\Service\Database;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ActivityController extends AbstractController
{
    #[Route('/api/activity', methods: ['GET'])]
    public function index(Request $request, Database $database, AuditLog $audit): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $audit->prune();
        $days = $request->query->get('days', '7') === '30' ? 30 : 7;
        $type = $request->query->get('type', 'all');
        $allowed = ['login_success', 'login_failure', 'logout', 'user_created', 'user_updated', 'document_created', 'document_updated'];
        if ($type !== 'all' && !in_array($type, $allowed, true)) { return $this->json(['error' => 'Filtre invalide.'], 400); }
        $db = $database->connection();
        $where = "created_at >= CURRENT_TIMESTAMP - CAST(:days AS integer) * INTERVAL '1 day'";
        $query = $db->prepare("SELECT COUNT(*) FILTER (WHERE type = 'login_success') AS connections, COUNT(*) FILTER (WHERE type = 'login_failure') AS failures, COUNT(DISTINCT actor_id) FILTER (WHERE type = 'login_success') AS members FROM audit_events WHERE $where");
        $query->execute(['days' => $days]);
        $summary = array_map('intval', $query->fetch());
        $query = $db->prepare("SELECT (created_at AT TIME ZONE 'Europe/Paris')::date AS date, COUNT(*) FILTER (WHERE type = 'login_success') AS success, COUNT(*) FILTER (WHERE type = 'login_failure') AS failure FROM audit_events WHERE $where GROUP BY date ORDER BY date");
        $query->execute(['days' => $days]);
        $series = $query->fetchAll();
        $filter = $type !== 'all' ? ' AND type = :type' : '';
        $query = $db->prepare("SELECT id, type, actor_id, actor_name, actor_email, client_ip, target_id, created_at FROM audit_events WHERE $where $filter ORDER BY created_at DESC, id DESC LIMIT 200");
        $query->execute($type === 'all' ? ['days' => $days] : ['days' => $days, 'type' => $type]);
        return $this->json(['summary' => $summary, 'series' => $series, 'events' => $query->fetchAll(), 'retentionDays' => 30]);
    }
}
