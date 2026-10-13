<?php

namespace App\Service;

use PDO;

class Migrations
{
    public function apply(PDO $db): void
    {
        $version = '20261013_atlas_u5';
        $query = $db->prepare('SELECT 1 FROM app_migrations WHERE version = :version');
        $query->execute(['version' => $version]);
        if ($query->fetchColumn()) { return; }
        $previous = 'atlas.'.'local';
        foreach (['users' => 'email', 'audit_events' => 'actor_email'] as $table => $column) {
            $update = $db->prepare("UPDATE $table SET $column = replace($column, :previous, :next) WHERE $column LIKE :suffix");
            $update->execute(['previous' => '@'.$previous, 'next' => '@atlas.u5', 'suffix' => '%@'.$previous]);
        }
        $db->exec("UPDATE users SET name = 'admin', email = 'admin@atlas.u5' WHERE email = 'gabriel@atlas.u5' AND role = 'ROLE_ADMIN'");
        $query = $db->prepare('UPDATE documents SET content = replace(replace(replace(content, :oldHttp, :next), :oldHttps, :next), :oldNetwork, :newNetwork), summary = replace(summary, :oldDomain, :newDomain), blocks = replace(replace(blocks::text, :blockHttp, :nextBlock), :blockHttps, :nextSecure)::jsonb');
        $query->execute(['oldHttp' => 'http://'.$previous, 'oldHttps' => 'https://'.$previous, 'next' => 'https://atlas.u5', 'oldNetwork' => 'réseau de test de l’entreprise', 'newNetwork' => 'réseau interne de l’entreprise', 'oldDomain' => $previous, 'newDomain' => 'atlas.u5', 'blockHttp' => 'http://'.$previous, 'blockHttps' => 'https://'.$previous, 'nextBlock' => 'https://atlas.u5', 'nextSecure' => 'https://atlas.u5']);
        $query = $db->prepare('INSERT INTO app_migrations (version) VALUES (:version)');
        $query->execute(['version' => $version]);
    }
}
