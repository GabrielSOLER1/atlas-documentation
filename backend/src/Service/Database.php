<?php

namespace App\Service;

use PDO;

class Database
{
    private ?PDO $connection = null;

    public function __construct(
        private string $host,
        private int $port,
        private string $name,
        private string $user,
        private string $password,
    ) {}

    public function connection(): PDO
    {
        return $this->connection ??= new PDO(
            "pgsql:host={$this->host};port={$this->port};dbname={$this->name};connect_timeout=5",
            $this->user,
            $this->password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false],
        );
    }
}
