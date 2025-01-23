<?php

use Expedition\Wpqb\Config\PostgresConfig;
use Expedition\Wpqb\Connection\PostgresConnection;
use Expedition\Wpqb\Grammar\PostgresGrammar;
use Expedition\Wpqb\QueryBuilder;

beforeEach(function () {
    $this->config = new PostgresConfig(
        'localhost',
        5432,
        'test_db',
        'test_user',
        'test_password'
    );
    
    $this->connection = new PostgresConnection($this->config);
});

it('can create a PostgreSQL connection', function () {
    expect($this->connection)->toBeInstanceOf(PostgresConnection::class);
});

it('can generate correct PostgreSQL SELECT query', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->setGrammar(new PostgresGrammar($this->connection));

    $sql = $queryBuilder->select('id', 'name')
        ->from('users')
        ->where('active', '=', true)
        ->orderBy('created_at', 'DESC')
        ->limit(10)
        ->toSql();

    expect($sql)->toBe('SELECT id, name FROM users WHERE active = true ORDER BY created_at DESC LIMIT 10');
});

it('can generate correct PostgreSQL INSERT query', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->setGrammar(new PostgresGrammar($this->connection));

    $sql = $queryBuilder->insert()
        ->into('users')
        ->values([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'active' => true
        ])
        ->toSql();

    expect($sql)->toBe(
        'INSERT INTO users (name, email, active) VALUES (\'John Doe\', \'john@example.com\', true)'
    );
});

it('can generate correct PostgreSQL UPDATE query', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->setGrammar(new PostgresGrammar($this->connection));

    $sql = $queryBuilder->update('users')
        ->set([
            'name' => 'Jane Doe',
            'active' => false
        ])
        ->where('id', '=', 1)
        ->toSql();

    expect($sql)->toBe(
        'UPDATE users SET name = \'Jane Doe\', active = false WHERE id = 1'
    );
});

it('can generate correct PostgreSQL DELETE query', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->setGrammar(new PostgresGrammar($this->connection));

    $sql = $queryBuilder->delete()
        ->from('users')
        ->where('active', '=', false)
        ->toSql();

    expect($sql)->toBe('DELETE FROM users WHERE active = false');
});

it('can handle PostgreSQL specific data types', function () {
    $queryBuilder = new QueryBuilder();
    $queryBuilder->setGrammar(new PostgresGrammar($this->connection));

    $sql = $queryBuilder->insert()
        ->into('events')
        ->values([
            'data' => json_encode(['key' => 'value']),
            'timestamp' => '2024-01-01 00:00:00',
            'is_active' => true
        ])
        ->toSql();

    expect($sql)->toBe(
        'INSERT INTO events (data, timestamp, is_active) ' .
        'VALUES (\'{"key":"value"}\', \'2024-01-01 00:00:00\', true)'
    );
}); 