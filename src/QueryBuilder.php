<?php

namespace Expedition\Wpqb;

use Expedition\Wpqb\Exceptions\NoQueryException;
use Expedition\Wpqb\Exceptions\UnsupportedQueryTypeException;
use Expedition\Wpqb\Grammar\Grammar;
use Expedition\Wpqb\Grammar\MysqlGrammar;
use InvalidArgumentException;

class QueryBuilder
{
    protected Grammar $grammar;
    protected ?QueryType $queryType;

    protected string $table = '';
    protected bool $distinct = false;
    protected ?int $limit = null;
    protected ?int $offset = null;

    /** @var array<string> */
    protected array $columns = ['*'];

    /** @var array<string|int> */
    protected array $sets = [];

    /** @var array<int|string> */
    protected array $values = [];

    /** @var array<array<int|string>> */
    protected array $wheres = [];

    /** @var array<string> */
    protected array $groupBy = [];

    /** @var array<array<int|string>> */
    protected array $havings = [];

    /** @var array<array<?string>> */
    protected array $joins = [];

    /** @var array<array<string>> */
    protected array $orders = [];

    public function __construct()
    {
        global $wpdb;

        $this->grammar = new MysqlGrammar($wpdb);
    }

    /**
     * @param string|array<string> ...$columns
     */
    public function select(...$columns): static
    {
        $this->queryType = QueryType::SELECT;

        $flatColumns = [];

        if (count($columns) === 1 && is_array($columns[0])) {
            $columns = $columns[0];
        }

        foreach ($columns as $column) {
            if (!is_string($column)) {
                throw new InvalidArgumentException('All columns should be of type string.');
            }
            $flatColumns[] = $column;
        }

        $this->columns = $flatColumns;

        return $this;
    }

    public function update(string $table): static
    {
        $this->queryType = QueryType::UPDATE;
        $this->table = $table;
        return $this;
    }

    public function insert(): static
    {
        $this->queryType = QueryType::INSERT;
        return $this;
    }

    public function into(string $table): static
    {
        return $this->from($table);
    }

    /**
     * @param array<string|int> $values
     */
    public function values(array $values): static
    {
        $this->values = $values;
        return $this;
    }

    public function delete(): static
    {
        $this->queryType = QueryType::DELETE;
        return $this;
    }

    /**
     * @param int|string|array<string|int> ...$args
     */
    public function set(...$args): static
    {
        if (count($args) === 2 && !is_array($args[0]) && !is_array($args[1])) {
            $this->sets[$args[0]] = $args[1];
            return $this;
        }

        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }

        /** @var array<string|int> $args */
        foreach ($args as $column => $value) {
            $this->sets[$column] = $value;
        }

        return $this;
    }

    public function execute(): int
    {
        return $this->grammar->execute($this);
    }

    public function from(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @param int|string|array<string|int|array<string|int>> ...$args
     */
    public function where(...$args): static
    {
        if (count($args) === 2 && !is_array($args[0]) && !is_array($args[1])) {
            $this->wheres[] = [
                'column' => $args[0],
                'operator' => '=',
                'value' => $args[1],
            ];
            return $this;
        }

        if (count($args) === 3 && !is_array($args[0]) && !is_array($args[1]) && !is_array($args[2])) {
            $this->wheres[] = [
                'column' => $args[0],
                'operator' => $args[1],
                'value' => $args[2],
            ];
            return $this;
        }

        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }

        /** @var array<string|int|array<string|int>> $args */
        foreach ($args as $column => $value) {
            if (is_array($value)) {
                $this->wheres[] = [
                    'column' => $value[0],
                    'operator' => $value[1],
                    'value' => $value[2],
                ];
            } else {
                $this->wheres[] = [
                    'column' => $column,
                    'operator' => '=',
                    'value' => $value,
                ];
            }
        }

        return $this;
    }

    /**
     * @throws NoQueryException
     * @throws UnsupportedQueryTypeException
     */
    public function toSql(): string
    {
        return $this->grammar->generateSql($this);
    }

    /**
     * @return array<mixed>
     * @throws UnsupportedQueryTypeException
     */
    public function get(): array
    {
        try {
            return $this->grammar->getResults($this);
        } catch (Exceptions\NoResultsException) {
            return [];
        }
    }

    /**
     * @return array<string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return array<array<int|string>>
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * @return array<string|int>
     */
    public function getSets(): array
    {
        return $this->sets;
    }

    /**
     * @return array<int|string>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return array<string>
     */
    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    /**
     * @return array<array<int|string>>
     */
    public function getHavings(): array
    {
        return $this->havings;
    }

    /**
     * @return array<array<string>>
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * @return array<array<?string>>
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    public function getDistinct(): bool
    {
        return $this->distinct;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function addJoin(string $type, string $table, ?string $firstColumn, ?string $operator, ?string $secondColumn): static
    {
        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'firstColumn' => $firstColumn,
            'operator' => $operator,
            'secondColumn' => $secondColumn,
        ];
        return $this;
    }

    public function join(string $table, string $firstColumn, string $operator, string $secondColumn): static
    {
        return $this->addJoin('JOIN', $table, $firstColumn, $operator, $secondColumn);
    }

    public function innerJoin(string $table, string $firstColumn, string $operator, string $secondColumn): static
    {
        return $this->addJoin('INNER JOIN', $table, $firstColumn, $operator, $secondColumn);
    }

    public function leftJoin(string $table, string $firstColumn, string $operator, string $secondColumn): static
    {
        return $this->addJoin('LEFT JOIN', $table, $firstColumn, $operator, $secondColumn);
    }

    public function rightJoin(string $table, string $firstColumn, string $operator, string $secondColumn): static
    {
        return $this->addJoin('RIGHT JOIN', $table, $firstColumn, $operator, $secondColumn);
    }

    public function crossJoin(string $table, ?string $firstColumn = null, ?string $operator = null, ?string $secondColumn = null): static
    {
        return $this->addJoin('CROSS JOIN', $table, $firstColumn, $operator, $secondColumn);
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = compact('column', 'direction');
        return $this;
    }

    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'DESC');
    }

    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    public function groupBy(string $column): static
    {
        $this->groupBy[] = $column;
        return $this;
    }

    public function having(string $column, string $operator, int|string $value): static
    {
        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];
        return $this;
    }

    public function getQueryType(): ?QueryType
    {
        if(empty($this->queryType)) {
            return null;
        }

        return $this->queryType;
    }
}
