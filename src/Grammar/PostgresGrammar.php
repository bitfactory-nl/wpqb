<?php

namespace Expedition\Wpqb\Grammar;

use Expedition\Wpqb\Exceptions\NoQueryException;
use Expedition\Wpqb\Exceptions\UnsupportedQueryTypeException;
use Expedition\Wpqb\QueryBuilder;
use Expedition\Wpqb\QueryType;

class PostgresGrammar extends Grammar
{
    /**
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * @var array
     */
    protected $returning = [];

    /**
     * @var array
     */
    protected $withQueries = [];

    /**
     * @var array
     */
    protected $onConflict = [];

    /**
     * @var array
     */
    protected $windowFunctions = [];

    /**
     * @var bool
     */
    protected $recursive = false;

    /**
     * @var array
     */
    protected $over = [];

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function getResults(QueryBuilder $query, string $output = 'OBJECT'): array
    {
        $sql = $this->generateSql($query);
        return $this->wpdb->get_results($sql, $output);
    }

    public function execute(QueryBuilder $query): int
    {
        $sql = $this->generateSql($query);
        return $this->wpdb->query($sql);
    }

    public function generateSql(QueryBuilder $query): string
    {
        if (null === $query->getQueryType()) {
            throw new NoQueryException();
        }

        $sql = [];

        // Add WITH clause if we have CTE's
        if (!empty($this->withQueries)) {
            $sql[] = $this->generateWithClause();
        }

        $sql[] = match ($query->getQueryType()) {
            QueryType::SELECT => $this->generateSelectSql($query),
            QueryType::INSERT => $this->generateInsertSql($query),
            QueryType::UPDATE => $this->generateUpdateSql($query),
            QueryType::DELETE => $this->generateDeleteSql($query),
            default => throw new UnsupportedQueryTypeException(),
        };

        return implode(' ', $sql);
    }

    public function returning(array $columns): self
    {
        $this->returning = $columns;
        return $this;
    }

    public function with(string $name, QueryBuilder $query): self
    {
        $this->withQueries[$name] = $query;
        return $this;
    }

    public function onConflict(array $columns, array $updates = []): self
    {
        $this->onConflict = [
            'columns' => $columns,
            'updates' => $updates
        ];
        return $this;
    }

    public function array(array $values): string
    {
        return "ARRAY[" . implode(', ', array_map(function ($value) {
            return is_string($value) ? "'$value'" : $value;
        }, $values)) . "]";
    }

    public function arrayContains(string $column, array $values): string
    {
        return $column . " @> " . $this->array($values);
    }

    public function arrayOverlap(string $column, array $values): string
    {
        return $column . " && " . $this->array($values);
    }

    public function recursive(): self
    {
        $this->recursive = true;
        return $this;
    }

    protected function generateWithClause(): string
    {
        $prefix = 'WITH';
        if ($this->recursive) {
            $prefix .= ' RECURSIVE';
        }

        $ctes = [];
        foreach ($this->withQueries as $name => $query) {
            $ctes[] = $name . ' AS (' . $query->toSql() . ')';
        }
        return $prefix . ' ' . implode(', ', $ctes);
    }

    protected function generateReturningClause(): string
    {
        return !empty($this->returning) 
            ? ' RETURNING ' . implode(', ', $this->returning)
            : '';
    }

    protected function generateOnConflictClause(): string
    {
        if (empty($this->onConflict)) {
            return '';
        }

        $sql = ' ON CONFLICT (' . implode(', ', $this->onConflict['columns']) . ')';
        
        if (!empty($this->onConflict['updates'])) {
            $sql .= ' DO UPDATE SET ';
            $updates = [];
            foreach ($this->onConflict['updates'] as $column => $value) {
                $updates[] = $column . ' = ' . (is_string($value) ? '\'' . $value . '\'' : $value);
            }
            $sql .= implode(', ', $updates);
        } else {
            $sql .= ' DO NOTHING';
        }

        return $sql;
    }

    public function generateSelectSql(QueryBuilder $query): string
    {
        $sql = ['SELECT'];

        if ($query->getDistinct()) {
            $sql[] = 'DISTINCT';
        }

        // Add window functions if they exist
        $columns = $query->getColumns();
        if (!empty($this->windowFunctions)) {
            foreach ($this->windowFunctions as $alias => $function) {
                $columns[] = $function . ' AS ' . $alias;
            }
        }

        $sql[] = implode(', ', $columns);
        $sql[] = 'FROM';
        $sql[] = $query->getTable();

        foreach ($query->getJoins() as $join) {
            $sql[] = $join['type'];
            $sql[] = $join['table'];

            if ($join['firstColumn'] !== null) {
                $sql[] = 'ON';
                $sql[] = $join['firstColumn'];
                
                if ($join['operator'] !== null) {
                    $sql[] = $join['operator'];
                }
                
                if ($join['secondColumn'] !== null) {
                    $sql[] = $join['secondColumn'];
                }
            }
        }

        if (!empty($query->getWheres())) {
            $sql[] = 'WHERE';
            $whereClauses = [];

            foreach ($query->getWheres() as $where) {
                $clause = ($where === reset($query->getWheres()))
                    ? ''
                    : $where['logical'] . ' ';

                $clause .= $where['column'] . ' ' . $where['operator'] . ' ';
                $clause .= is_string($where['value'])
                    ? '\'' . $where['value'] . '\''
                    : $where['value'];

                $whereClauses[] = $clause;
            }

            $sql[] = implode(' ', $whereClauses);
        }

        if (!empty($query->getGroupBy())) {
            $sql[] = 'GROUP BY';
            $sql[] = implode(', ', $query->getGroupBy());
        }

        if (!empty($query->getHavings())) {
            $sql[] = 'HAVING';
            $havingClauses = [];

            foreach ($query->getHavings() as $having) {
                $clause = ($having === reset($query->getHavings()))
                    ? ''
                    : $having['logical'] . ' ';

                $clause .= $having['column'] . ' ' . $having['operator'] . ' ';
                $clause .= is_string($having['value'])
                    ? '\'' . $having['value'] . '\''
                    : $having['value'];

                $havingClauses[] = $clause;
            }

            $sql[] = implode(' ', $havingClauses);
        }

        if (!empty($query->getOrders())) {
            $sql[] = 'ORDER BY';
            $orderClauses = [];

            foreach ($query->getOrders() as $order) {
                $orderClauses[] = $order['column'] . ' ' . $order['direction'];
            }

            $sql[] = implode(', ', $orderClauses);
        }

        if ($query->getLimit() !== null) {
            $sql[] = 'LIMIT';
            $sql[] = $query->getLimit();
        }

        if ($query->getOffset() !== null) {
            $sql[] = 'OFFSET';
            $sql[] = $query->getOffset();
        }

        return implode(' ', $sql);
    }

    public function generateInsertSql(QueryBuilder $query): string
    {
        if (empty($query->getValues())) {
            throw new NoQueryException();
        }

        $sql = ['INSERT INTO'];
        $sql[] = $query->getTable();
        
        $columns = array_keys($query->getValues());
        $values = array_values($query->getValues());
        
        $sql[] = '(' . implode(', ', $columns) . ')';
        $sql[] = 'VALUES';
        
        $quotedValues = array_map(function ($value) {
            if (is_array($value)) {
                return 'jsonb_build_object(' . $this->buildJsonObject($value) . ')';
            }
            return is_string($value) ? '\'' . $value . '\'' : $value;
        }, $values);
        
        $sql[] = '(' . implode(', ', $quotedValues) . ')';

        // Add ON CONFLICT clause if specified
        if (!empty($this->onConflict)) {
            $sql[] = $this->generateOnConflictClause();
        }

        // Add RETURNING clause if specified
        if (!empty($this->returning)) {
            $sql[] = $this->generateReturningClause();
        }

        return implode(' ', $sql);
    }

    protected function buildJsonObject(array $data): string
    {
        $parts = [];
        foreach ($data as $key => $value) {
            $parts[] = '\'' . $key . '\'';
            if (is_array($value)) {
                $parts[] = 'jsonb_build_object(' . $this->buildJsonObject($value) . ')';
            } else {
                $parts[] = is_string($value) ? '\'' . $value . '\'' : $value;
            }
        }
        return implode(', ', $parts);
    }

    public function generateUpdateSql(QueryBuilder $query): string
    {
        if (empty($query->getSets())) {
            throw new NoQueryException();
        }

        $sql = ['UPDATE'];
        $sql[] = $query->getTable();
        $sql[] = 'SET';

        $setClauses = [];
        foreach ($query->getSets() as $column => $value) {
            $setClauses[] = $column . ' = ' . (is_string($value) ? '\'' . $value . '\'' : $value);
        }
        $sql[] = implode(', ', $setClauses);

        if (!empty($query->getWheres())) {
            $sql[] = 'WHERE';
            $whereClauses = [];

            foreach ($query->getWheres() as $where) {
                $clause = ($where === reset($query->getWheres()))
                    ? ''
                    : $where['logical'] . ' ';

                $clause .= $where['column'] . ' ' . $where['operator'] . ' ';
                $clause .= is_string($where['value'])
                    ? '\'' . $where['value'] . '\''
                    : $where['value'];

                $whereClauses[] = $clause;
            }

            $sql[] = implode(' ', $whereClauses);
        }

        return implode(' ', $sql);
    }

    public function generateDeleteSql(QueryBuilder $query): string
    {
        if (empty($query->getTable())) {
            throw new NoQueryException();
        }

        $sql = ['DELETE FROM'];
        $sql[] = $query->getTable();

        if (!empty($query->getWheres())) {
            $sql[] = 'WHERE';
            $whereClauses = [];

            foreach ($query->getWheres() as $where) {
                $clause = ($where === reset($query->getWheres()))
                    ? ''
                    : $where['logical'] . ' ';

                $clause .= $where['column'] . ' ' . $where['operator'] . ' ';
                $clause .= is_string($where['value'])
                    ? '\'' . $where['value'] . '\''
                    : $where['value'];

                $whereClauses[] = $clause;
            }

            $sql[] = implode(' ', $whereClauses);
        }

        return implode(' ', $sql);
    }

    public function fullTextSearch(string $column, string $query): string
    {
        return "to_tsvector('english', " . $column . ") @@ to_tsquery('english', '" . $query . "')";
    }

    public function jsonGet(string $column, string $path): string
    {
        return $column . "->'" . $path . "'";
    }

    public function jsonbGet(string $column, string $path): string
    {
        return $column . "->>'" . $path . "'";
    }

    public function over(string $function, array $partitionBy = [], array $orderBy = [], ?string $frame = null): string
    {
        $over = $function . ' OVER (';
        $parts = [];

        if (!empty($partitionBy)) {
            $parts[] = 'PARTITION BY ' . implode(', ', $partitionBy);
        }

        if (!empty($orderBy)) {
            $orderClauses = [];
            foreach ($orderBy as $column => $direction) {
                $orderClauses[] = $column . ' ' . $direction;
            }
            $parts[] = 'ORDER BY ' . implode(', ', $orderClauses);
        }

        if ($frame !== null) {
            $parts[] = $frame;
        }

        $over .= implode(' ', $parts) . ')';
        return $over;
    }

    public function rank(): string
    {
        return $this->over('RANK()');
    }

    public function denseRank(): string
    {
        return $this->over('DENSE_RANK()');
    }

    public function rowNumber(): string
    {
        return $this->over('ROW_NUMBER()');
    }

    public function lag(string $column, int $offset = 1): string
    {
        return $this->over("LAG($column, $offset)");
    }

    public function lead(string $column, int $offset = 1): string
    {
        return $this->over("LEAD($column, $offset)");
    }

    public function firstValue(string $column): string
    {
        return $this->over("FIRST_VALUE($column)");
    }

    public function lastValue(string $column): string
    {
        return $this->over("LAST_VALUE($column)");
    }

    public function createMaterializedView(string $name, QueryBuilder $query): string
    {
        return "CREATE MATERIALIZED VIEW $name AS " . $query->toSql();
    }

    public function refreshMaterializedView(string $name, bool $concurrently = false): string
    {
        return "REFRESH MATERIALIZED VIEW " . ($concurrently ? "CONCURRENTLY " : "") . $name;
    }

    public function gistIndex(string $table, string $column, string $name = null): string
    {
        if ($name === null) {
            $name = $table . '_' . $column . '_gist';
        }
        return "CREATE INDEX $name ON $table USING GIST ($column)";
    }

    public function gistIndexForGeometry(string $table, string $column, string $name = null): string
    {
        if ($name === null) {
            $name = $table . '_' . $column . '_geometry_gist';
        }
        return "CREATE INDEX $name ON $table USING GIST (ST_GeographyFromText($column))";
    }

    public function withinDistance(string $point1, string $point2, float $meters): string
    {
        return "ST_DWithin(ST_GeographyFromText($point1), ST_GeographyFromText($point2), $meters)";
    }
} 