<?php

namespace App\Command;

use App\Service\Database;
use App\Service\Migrations;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:migrate', description: 'Upgrades an existing database without seeding accounts or documents.')]
class MigrateCommand extends Command
{
    public function __construct(private Database $database, private Migrations $migrations) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = $this->database->connection();
        $db->beginTransaction();
        try {
            $db->exec(file_get_contents(dirname(__DIR__, 2).'/database/schema.sql'));
            $this->migrations->apply($db);
            $db->commit();
        } catch (\Throwable $error) { $db->rollBack(); throw $error; }
        $output->writeln('Database migrations applied. Existing content preserved.');
        return Command::SUCCESS;
    }
}
