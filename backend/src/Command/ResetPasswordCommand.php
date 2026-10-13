<?php

namespace App\Command;

use App\Security\User;
use App\Service\Database;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:reset-password', description: 'Changes an existing account password from the server console.')]
class ResetPasswordCommand extends Command
{
    public function __construct(private Database $database, private UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Account email.');
        $this->addOption('stdin', null, InputOption::VALUE_NONE, 'Read one password line from standard input.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = $this->database->connection();
        $query = $db->prepare('SELECT id, email, name, role FROM users WHERE email = :email');
        $query->execute(['email' => strtolower(trim($input->getArgument('email')))]);
        $account = $query->fetch();
        if (!$account) { $output->writeln('<error>Account not found.</error>'); return Command::FAILURE; }

        if ($input->getOption('stdin')) {
            $password = rtrim(fgets(STDIN, 256) ?: '', "\r\n");
        } else {
            if (!$input->isInteractive()) { $output->writeln('<error>Use an interactive terminal or --stdin.</error>'); return Command::FAILURE; }
            $question = (new Question('New password (hidden): '))->setHidden(true)->setHiddenFallback(false);
            $password = $this->getHelper('question')->ask($input, $output, $question);
            $confirmation = (new Question('Confirm password (hidden): '))->setHidden(true)->setHiddenFallback(false);
            if ($password !== $this->getHelper('question')->ask($input, $output, $confirmation)) {
                $output->writeln('<error>Passwords do not match.</error>');
                return Command::FAILURE;
            }
        }
        if (!is_string($password) || mb_strlen($password) < 12 || strlen($password) > 72 || trim($password) === '') {
            $output->writeln('<error>Use at least 12 characters and at most 72 bytes.</error>');
            return Command::FAILURE;
        }
        $user = new User($account['email'], '', $account['name'], $account['role'], (int) $account['id']);
        $update = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
        $update->execute(['password' => $this->hasher->hashPassword($user, $password), 'id' => $account['id']]);
        error_log(sprintf('[Atlas] Account %d password changed through the server console.', $account['id']));
        $output->writeln('Password updated. Previous sessions will require a new login.');
        return Command::SUCCESS;
    }
}
