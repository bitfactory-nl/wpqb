<?php

namespace Expedition\Wpqb\Config;

class PostgresConfig
{
    private string $host;
    private int $port;
    private string $database;
    private string $username;
    private string $password;
    private array $options;

    public function __construct(
        string $host,
        int $port,
        string $database,
        string $username,
        string $password,
        array $options = []
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }

    public function getDsn(): string
    {
        return sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $this->host,
            $this->port,
            $this->database
        );
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
} 