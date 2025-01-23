<?php

use Expedition\Wpqb\Grammar\PostgresGrammar;
use Expedition\Wpqb\QueryBuilder;
use Expedition\Wpqb\Exceptions\NoQueryException;
use Expedition\Wpqb\Exceptions\UnsupportedQueryTypeException;

beforeEach(function () {
    $wpdb = Mockery::mock('stdObject');
    $wpdb->shouldReceive('prepare')->andReturnArg(0);
    $wpdb->shouldReceive('get_results')->andReturn([]);
    $wpdb->shouldReceive('query')->andReturn(1);
    $this->grammar = new PostgresGrammar($wpdb);
});

it('throws exception when no query type is set', function () {
    $query = new QueryBuilder();
    expect(fn() => $this->grammar->generateSql($query))
        ->toThrow(NoQueryException::class);
});

it('throws exception when unsupported query type is used', function () {
    $query = new QueryBuilder();
    $query->setQueryType('INVALID');
    expect(fn() => $this->grammar->generateSql($query))
        ->toThrow(UnsupportedQueryTypeException::class);
});

it('can generate INSERT with RETURNING clause', function () {
    $query = new QueryBuilder();
    $sql = $this->grammar
        ->returning(['id', 'created_at'])
        ->generateInsertSql($query->insert()
            ->into('users')
            ->values([
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]));

    expect($sql)->toBe(
        "INSERT INTO users (name, email) VALUES ('John Doe', 'john@example.com') RETURNING id, created_at"
    );
});

it('throws exception when INSERT has no values', function () {
    $query = new QueryBuilder();
    expect(fn() => $this->grammar->generateInsertSql($query->insert()->into('users')))
        ->toThrow(NoQueryException::class);
});

it('can generate UPSERT query with DO NOTHING', function () {
    $query = new QueryBuilder();
    $sql = $this->grammar
        ->onConflict(['email'])
        ->generateInsertSql($query->insert()
            ->into('users')
            ->values([
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]));

    expect($sql)->toBe(
        "INSERT INTO users (name, email) VALUES ('John Doe', 'john@example.com') ON CONFLICT (email) DO NOTHING"
    );
});

it('can generate UPSERT query with DO UPDATE', function () {
    $query = new QueryBuilder();
    $sql = $this->grammar
        ->onConflict(['email'], ['name' => 'John Doe Updated', 'updated_at' => 'NOW()'])
        ->generateInsertSql($query->insert()
            ->into('users')
            ->values([
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]));

    expect($sql)->toBe(
        "INSERT INTO users (name, email) VALUES ('John Doe', 'john@example.com') " .
        "ON CONFLICT (email) DO UPDATE SET name = 'John Doe Updated', updated_at = NOW()"
    );
});

it('can handle array operations', function () {
    $arrayLiteral = $this->grammar->array(['red', 'green', 'blue']);
    expect($arrayLiteral)->toBe("ARRAY['red', 'green', 'blue']");

    $arrayWithNumbers = $this->grammar->array([1, 2, 3]);
    expect($arrayWithNumbers)->toBe("ARRAY[1, 2, 3]");

    $containsCheck = $this->grammar->arrayContains('colors', ['red', 'blue']);
    expect($containsCheck)->toBe("colors @> ARRAY['red', 'blue']");

    $overlapCheck = $this->grammar->arrayOverlap('tags', ['php', 'postgresql']);
    expect($overlapCheck)->toBe("tags && ARRAY['php', 'postgresql']");
});

it('can generate recursive queries', function () {
    $recursiveQuery = (new QueryBuilder())
        ->select(['id', 'parent_id', 'name', 'level'])
        ->from('categories')
        ->where('parent_id', 'IS', null);

    $query = new QueryBuilder();
    $sql = $this->grammar
        ->recursive()
        ->with('category_tree', $recursiveQuery)
        ->generateSql($query->select(['ct.*'])
            ->from('category_tree as ct'));

    expect($sql)->toBe(
        "WITH RECURSIVE category_tree AS (SELECT id, parent_id, name, level FROM categories WHERE parent_id IS null) " .
        "SELECT ct.* FROM category_tree as ct"
    );
});

it('can generate window functions with all options', function () {
    $rank = $this->grammar->over(
        'RANK()', 
        ['department_id'], 
        ['salary' => 'DESC'],
        'ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW'
    );
    expect($rank)->toBe(
        'RANK() OVER (PARTITION BY department_id ORDER BY salary DESC ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)'
    );

    $rowNumber = $this->grammar->rowNumber();
    expect($rowNumber)->toBe('ROW_NUMBER() OVER ()');

    $lag = $this->grammar->lag('salary', 1);
    expect($lag)->toBe('LAG(salary, 1) OVER ()');

    $lead = $this->grammar->lead('salary', 1);
    expect($lead)->toBe('LEAD(salary, 1) OVER ()');

    $firstValue = $this->grammar->firstValue('salary');
    expect($firstValue)->toBe('FIRST_VALUE(salary) OVER ()');

    $lastValue = $this->grammar->lastValue('salary');
    expect($lastValue)->toBe('LAST_VALUE(salary) OVER ()');

    $denseRank = $this->grammar->denseRank();
    expect($denseRank)->toBe('DENSE_RANK() OVER ()');
});

it('can handle materialized views', function () {
    $viewQuery = (new QueryBuilder())
        ->select(['department_id', 'AVG(salary) as avg_salary'])
        ->from('employees')
        ->groupBy(['department_id']);

    $createView = $this->grammar->createMaterializedView('dept_stats', $viewQuery);
    expect($createView)->toBe(
        "CREATE MATERIALIZED VIEW dept_stats AS " .
        "SELECT department_id, AVG(salary) as avg_salary FROM employees GROUP BY department_id"
    );

    $refreshView = $this->grammar->refreshMaterializedView('dept_stats', true);
    expect($refreshView)->toBe("REFRESH MATERIALIZED VIEW CONCURRENTLY dept_stats");

    $refreshViewNonConcurrent = $this->grammar->refreshMaterializedView('dept_stats', false);
    expect($refreshViewNonConcurrent)->toBe("REFRESH MATERIALIZED VIEW dept_stats");
});

it('can generate GiST indexes with custom names', function () {
    $gistIndex = $this->grammar->gistIndex('locations', 'coordinates', 'custom_index_name');
    expect($gistIndex)->toBe("CREATE INDEX custom_index_name ON locations USING GIST (coordinates)");

    $geometryIndex = $this->grammar->gistIndexForGeometry('locations', 'point', 'custom_geo_index');
    expect($geometryIndex)->toBe(
        "CREATE INDEX custom_geo_index ON locations USING GIST (ST_GeographyFromText(point))"
    );
});

it('can generate spatial queries', function () {
    $distanceCheck = $this->grammar->withinDistance('point1', 'point2', 1000);
    expect($distanceCheck)->toBe(
        "ST_DWithin(ST_GeographyFromText(point1), ST_GeographyFromText(point2), 1000)"
    );
});

it('can handle JSON operations', function () {
    $query = new QueryBuilder();
    $sql = $this->grammar->generateInsertSql($query->insert()
        ->into('users')
        ->values([
            'name' => 'John',
            'metadata' => [
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => true,
                    'filters' => ['draft', 'published']
                ]
            ]
        ]));

    expect($sql)->toBe(
        "INSERT INTO users (name, metadata) VALUES ('John', " .
        "jsonb_build_object('preferences', jsonb_build_object('theme', 'dark', 'notifications', true, " .
        "'filters', ARRAY['draft', 'published'])))"
    );

    $jsonPath = $this->grammar->jsonGet('data', 'info');
    $jsonbPath = $this->grammar->jsonbGet('data', 'info');

    expect($jsonPath)->toBe("data->'info'")
        ->and($jsonbPath)->toBe("data->>'info'");
});

it('can generate full-text search with different configurations', function () {
    $searchCondition = $this->grammar->fullTextSearch('description', 'search & terms');
    expect($searchCondition)->toBe(
        "to_tsvector('english', description) @@ to_tsquery('english', 'search & terms')"
    );
}); 