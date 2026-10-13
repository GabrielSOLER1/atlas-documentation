<?php

namespace App\Command;

use App\Security\User;
use App\Service\Database;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:init', description: 'Initializes the schema and first administrator without overwriting existing data.')]
class InitializeCommand extends Command
{
    public function __construct(private Database $database, private UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = $this->database->connection();
        $db->beginTransaction();
        try {
            $db->exec(file_get_contents(dirname(__DIR__, 2).'/database/schema.sql'));
            if ((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
                $email = getenv('BOOTSTRAP_ADMIN_EMAIL') ?: 'gabriel@atlas.local';
                $name = getenv('BOOTSTRAP_ADMIN_NAME') ?: 'Gabriel Soler';
                $password = getenv('BOOTSTRAP_ADMIN_PASSWORD');
                if (!$password || strlen($password) < 12 || strlen($password) > 72 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Configure the initial administrator in .env before starting Atlas.');
                }
                $insert = $db->prepare('INSERT INTO users (email, password, name, role) VALUES (:email, :password, :name, :role)');
                $insert->execute(['email' => strtolower($email), 'password' => $this->hasher->hashPassword(new User($email, '', $name, 'ROLE_ADMIN'), $password), 'name' => $name, 'role' => 'ROLE_ADMIN']);
            }
            $insert = $db->prepare('INSERT INTO documents (slug, title, category, summary, content) VALUES (:slug, :title, :category, :summary, :content) ON CONFLICT (slug) DO NOTHING');
            foreach (require dirname(__DIR__, 2).'/database/documents.php' as $document) { $insert->execute($document); }
            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
        $output->writeln('Atlas est initialisé. Les données déjà présentes ont été conservées.');
        return Command::SUCCESS;
    }
}
