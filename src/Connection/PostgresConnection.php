<?php

namespace Expedition\Wpqb\Connection;

use Expedition\Wpqb\Config\PostgresConfig;
use PDO;
use PDOException;

class PostgresConnection
{
    private static ?PDO $connection = null;
    private PostgresConfig $config;

    public function __construct(PostgresConfig $config)
    {
        $this->config = $config;
    }

    public function connect(): PDO
    {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    $this->config->getDsn(),
                    $this->config->getUsername(),
                    $this->config->getPassword(),
                    array_merge([
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ], $this->config->getOptions())
                );
            } catch (PDOException $e) {
                // Log error en gooi exception door
                error_log('PostgreSQL Connection Error: ' . $e->getMessage());
                throw $e;
            }
        }

        return self::$connection;
    }

    public function disconnect(): void
    {
        self::$connection = null;
    }

    public function getConnection(): ?PDO
    {
        return self::$connection;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function beginTransaction(): bool
    {
        return $this->connect()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connect()->commit();
    }

    public function rollBack(): bool
    {
        return $this->connect()->rollBack();
    }
} 