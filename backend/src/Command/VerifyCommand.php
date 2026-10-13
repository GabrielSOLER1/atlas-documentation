<?php

namespace App\Command;

use App\Service\Database;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:verify', description: 'Vérifie les procédures, les comptes sauvegardés et la présence d’un administrateur actif, sans modifier la base.')]
class VerifyCommand extends Command
{
    public function __construct(private Database $database)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $documents = $this->database->connection()->query('SELECT slug, title, content FROM documents')->fetchAll();
        $slugs = [];
        foreach ($documents as $document) {
            if (trim($document['title']) === '' || trim($document['content']) === '') { throw new \RuntimeException('Procédure vide.'); }
            $slugs[] = $document['slug'];
        }
        foreach (['signaler-un-incident', 'consulter-la-documentation'] as $slug) {
            if (!in_array($slug, $slugs, true)) { throw new \RuntimeException('Procédure absente : '.$slug); }
        }
        $users = $this->database->connection()->query('SELECT password, role, active FROM users')->fetchAll();
        if (count($users) < 1) { throw new \RuntimeException('Des comptes attendus sont absents.'); }
        $activeAdmins = 0;
        foreach ($users as $user) {
            if (password_get_info($user['password'])['algoName'] === 'unknown') {
                throw new \RuntimeException('Un mot de passe sauvegardé est invalide.');
            }
            if ($user['active'] && $user['role'] === 'ROLE_ADMIN') { ++$activeAdmins; }
        }
        if ($activeAdmins === 0) { throw new \RuntimeException('Aucun administrateur actif.'); }
        $output->writeln('Vérification réussie : procédures présentes, comptes sauvegardés et administrateur actif.');
        return Command::SUCCESS;
    }
}
