<?php

namespace Expedition\Wpqb;

use Expedition\Wpqb\Exceptions\InvalidQueryException;
use Expedition\Wpqb\Exceptions\NoQueryException;
use Expedition\Wpqb\Exceptions\NoResultsException;
use Expedition\Wpqb\Exceptions\UnsupportedQueryTypeException;
use Expedition\Wpqb\Grammar\Grammar;
use Expedition\Wpqb\Grammar\MysqlGrammar;
use InvalidArgumentException;

class QueryBuilder
{
    /**
     * The grammar instance used to generate and execute queries.
     *
     * @var Grammar $grammar
     */
    protected Grammar $grammar;

    /**
     * The type of query being executed.
     *
     * @var QueryType|null $queryType
     */
    protected ?QueryType $queryType;

    /**
     * The table which the query is targeting.
     *
     * @var string $table
     */
    protected string $table = '';

    /**
     * Indicates if the query returns distinct results.
     *
     * @var bool $distinct
     */
    protected bool $distinct = false;

    /**
     * The maximum number of records to return.
     *
     * @var int|null $limit
     */
    protected ?int $limit = null;

    /**
     * The number of records to skip.
     *
     * @var int|null $offset
     */
    protected ?int $offset = null;

    /**
     * The columns that should be returned.
     *
     * @var array<string>
     */
    protected array $columns = ['*'];

    /**
     * The columns that should be updated.
     *
     * @var array<string|int>
     */
    protected array $sets = [];

    /**
     * The values that should be inserted.
     *
     * @var array<int|string>
     */
    protected array $values = [];

    /**
     * The where constraints for the query.
     *
     * @var array<array<int|string>>
     */
    protected array $wheres = [];

    /**
     * The groupings for the query.
     *
     * @var array<string>
     */
    protected array $groupBy = [];

    /**
     * The having constraints for the query.
     *
     * @var array<array<int|string>>
     */
    protected array $havings = [];

    /**
     * The joins for the query.
     *
     * @var array<array<?string>>
     */
    protected array $joins = [];

    /**
     * The orderings for the query.
     *
     * @var array<array<string>>
     */
    protected array $orders = [];

    /**
     * Create a new query builder instance.
     *
     * @return void
     */
    public function __construct()
    {
        global $wpdb;

        $this->grammar = new MysqlGrammar($wpdb);
    }

    /**
     * Specifies which columns should be returned by the query.
     * If no columns are specified, all columns will be returned.
     *
     * Columns may be passed as an array of strings or as multiple string
     * arguments.
     *
     * Example: `->select()`
     * Example: `->select('id', 'name')`
     * Example: `->select(['id', 'name'])`
     *
     * @param string|array<string> ...$columns
     * @return static
     */
    public function select(...$columns): static
    {
        $this->queryType = QueryType::SELECT;

        if (empty($columns)) {
            $this->columns = ['*'];
            return $this;
        }

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

    /**
     * Set whether the query should return distinct results.
     *
     * Example: `->distinct()`
     *
     * @return static
     */
    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Specifies which table to query.
     *
     * Example: `->from('wp_posts')`
     *
     * @param string $table
     * @return static
     */
    public function from(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Specifies an update query, targeting the specified table.
     *
     * Example: `->update('wp_posts')`
     *
     * @param string $table
     * @return static
     */
    public function update(string $table): static
    {
        $this->queryType = QueryType::UPDATE;
        $this->table = $table;
        return $this;
    }

    /**
     * Specifies an insert query. The table to insert into must be specified
     * using the `into` method.
     *
     * Example: `->insert()->into('wp_posts')`
     *
     * @return static
     */
    public function insert(): static
    {
        $this->queryType = QueryType::INSERT;
        return $this;
    }

    /**
     * Specifies which table to insert into. This method is an alias of the
     * `from` method, and is provided for readability.
     *
     * Example: `->insert()->into('wp_posts')`
     *
     * @param string $table
     * @return static
     */
    public function into(string $table): static
    {
        return $this->from($table);
    }

    /**
     * Specifies which values to insert. This method accepts an array of
     * key-value pairs, where the keys are the column names and the values are
     * the values to insert.
     *
     * This method may also be called with two arguments, where the first
     * argument is the column name and the second argument is the value to
     * insert.
     *
     * Example: `->values(['name' => 'John Doe', 'age' => 42])`
     * Example: `->values('name', 'John Doe')`
     *
     * @param int|string|array<string|int> ...$values
     * @return static
     */
    public function values(...$values): static
    {
        if (count($values) === 2 && !is_array($values[0]) && !is_array($values[1])) {
            $this->values[$values[0]] = $values[1];
            return $this;
        }

        if (count($values) === 1 && is_array($values[0])) {
            $values = $values[0];
        }

        /** @var array<string|int> $values */
        foreach ($values as $column => $value) {
            $this->values[$column] = $value;
        }

        return $this;
    }

    /**
     * Specifies a delete query. The table to delete from must be specified
     * using the `from` method.
     *
     * Example: `->delete()->from('wp_posts')`
     *
     * @return static
     */
    public function delete(): static
    {
        $this->queryType = QueryType::DELETE;
        return $this;
    }

    /**
     * Specifies which columns to update. This method accepts an array of
     * key-value pairs, where the keys are the column names and the values are
     * the values to update.
     *
     * This method may also be called with two arguments, where the first
     * argument is the column name and the second argument is the value to
     * update.
     *
     * Example: `->set(['name' => 'John Doe', 'age' => 42])`
     * Example: `->set('name', 'John Doe')`
     *
     * @param int|string|array<string|int> ...$args
     * @return static
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

    /**
     * Specifies which table to join, and which columns to join on.
     *
     * Example:
     * `->join('wp_postmeta', 'wp_posts.ID', '=', 'wp_postmeta.post_id')`
     *
     * @param string $table
     * @param string $firstColumn
     * @param string $operator
     * @param string $secondColumn
     * @return static
     */
    public function join(string $table, string $firstColumn, string $operator, string $secondColumn): static
    {
        return $this->addJoin('JOIN', $table, $firstColumn, $operator, $secondColumn);
    }

    /**
     * Specifies which table to inner join, and which columns to join on.
     *
     * Example:
     * `->innerJoin('wp_postmeta', 'wp_posts.ID', '=', 'wp_postmeta.post_id')`
     *
     * @param string $table
     * @param string $firstColumn
     * @param string $operator
     * @param string $secondColumn
     * @return static
     */
    public function innerJoin(string $table, string $firstColumn, string $operator, string $secondColumn): static
    {
        return $this->addJoin('INNER JOIN', $table, $firstColumn, $operator, $secondColumn);
    }

    /**
     * Specifies which table to left join, and which columns to join on.
     *
     * Example:
     * `->leftJoin('wp_postmeta', 'wp_posts.ID', '=', 'wp_postmeta.post_id')`
     *
     * @param string $table
     * @param string $firstColumn
     * @param string $operator
     * @param string $secondColumn
     * @return static
     */
    public function leftJoin(string $table, string $firstColumn, string $operator, string $secondColumn): static
    {
        return $this->addJoin('LEFT JOIN', $table, $firstColumn, $operator, $secondColumn);
    }

    /**
     * Specifies which table to right join, and which columns to join on.
     *
     * Example:
     * `->rightJoin('wp_postmeta', 'wp_posts.ID', '=', 'wp_postmeta.post_id')`
     *
     * @param string $table
     * @param string $firstColumn
     * @param string $operator
     * @param string $secondColumn
     * @return static
     */
    public function rightJoin(string $table, string $firstColumn, string $operator, string $secondColumn): static
    {
        return $this->addJoin('RIGHT JOIN', $table, $firstColumn, $operator, $secondColumn);
    }

    /**
     * Specifies which table to cross join, and which columns to join on.
     *
     * Example:
     * `->crossJoin('wp_postmeta', 'wp_posts.ID', '=', 'wp_postmeta.post_id')`
     *
     * @param string $table
     * @param string $firstColumn
     * @param string $operator
     * @param string $secondColumn
     * @return static
     */
    public function crossJoin(string $table, ?string $firstColumn = null, ?string $operator = null, ?string $secondColumn = null): static
    {
        return $this->addJoin('CROSS JOIN', $table, $firstColumn, $operator, $secondColumn);
    }

    /**
     * Specifies which table to natural join, and which columns to join on.
     *
     * Example:
     * `->naturalJoin('wp_postmeta', 'wp_posts.ID', '=', 'wp_postmeta.post_id')`
     *
     * @param string $table
     * @param string $firstColumn
     * @param string $operator
     * @param string $secondColumn
     * @return static
     */
    public function naturalJoin(string $table, ?string $firstColumn = null, ?string $operator = null, ?string $secondColumn = null): static
    {
        return $this->addJoin('NATURAL JOIN', $table, $firstColumn, $operator, $secondColumn);
    }

    /**
     * Adds the specified join to the query.
     *
     * @param string $table
     * @param string $firstColumn
     * @param string $operator
     * @param string $secondColumn
     * @return static
     */
    private function addJoin(string $type, string $table, ?string $firstColumn, ?string $operator, ?string $secondColumn): static
    {
        $this->joins[] = [
            'type'         => $type,
            'table'        => $table,
            'firstColumn'  => $firstColumn,
            'operator'     => $operator,
            'secondColumn' => $secondColumn,
        ];
        return $this;
    }

    /**
     * Sets which where constraints to apply to the query. This method uses AND
     * as the logical operator, if there are multiple where constraints. To use
     * OR as the logical operator, use the `orWhere` method instead. For
     * clarity, `andWhere` is also available as an alias of this method.
     *
     * This method can be used in multiple ways, depending on your preference:
     *
     * Example: `->where('id', 1)`
     * Example: `->where('id', '>', 1)`
     * Example: `->where(['id' => 1, 'name' => 'John Doe'])`
     * Example: `->where([[['id', 1], ['name', 'John Doe']]])`
     *
     * @param int|string|array<string|int|array<string|int>> ...$args
     * @return static
     */
    public function where(...$args): static
    {
        return $this->addWhere('AND', ...$args);
    }

    /**
     * Sets which where constraints to apply to the query. This method uses AND
     * as the logical operator, if there are multiple where constraints. To use
     * OR as the logical operator, use the `orWhere` method instead.
     *
     * Example: `->andWhere('id', 1)`
     * Example: `->andWhere('id', '>', 1)`
     * Example: `->andWhere(['id' => 1, 'name' => 'John Doe'])`
     * Example: `->andWhere([[['id', 1], ['name', 'John Doe']]])`
     *
     * @param int|string|array<string|int|array<string|int>> ...$args
     * @return static
     * @see where()
     *
     */
    public function andWhere(...$args): static
    {
        return $this->where(...$args);
    }

    /**
     * Sets which where constraints to apply to the query. This method uses OR
     * as the logical operator, if there are multiple where constraints. To use
     * AND as the logical operator, use the `where` or `andWhere` methods
     * instead.
     *
     * Example: `->orWhere('id', 1)`
     * Example: `->orWhere('id', '>', 1)`
     * Example: `->orWhere(['id' => 1, 'name' => 'John Doe'])`
     * Example: `->orWhere([[['id', 1], ['name', 'John Doe']]])`
     *
     * @param int|string|array<string|int|array<string|int>> ...$args
     * @return static
     * @see where()
     *
     */
    public function orWhere(...$args): static
    {
        return $this->addWhere('OR', ...$args);
    }

    /**
     * Sets which where constraints to apply to the query.
     *
     * @param string                                         $logicalOperator
     * @param int|string|array<string|int|array<string|int>> ...$args
     */
    private function addWhere($logicalOperator, ...$args): static
    {
        if (count($args) === 2 && !is_array($args[0]) && !is_array($args[1])) {
            $this->wheres[] = [
                'column'   => $args[0],
                'operator' => '=',
                'value'    => $args[1],
                'logical'  => $logicalOperator,
            ];
            return $this;
        }

        if (count($args) === 3 && !is_array($args[0]) && !is_array($args[1]) && !is_array($args[2])) {
            $this->wheres[] = [
                'column'   => $args[0],
                'operator' => $args[1],
                'value'    => $args[2],
                'logical'  => $logicalOperator,
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
                    'column'   => $value[0],
                    'operator' => $value[1],
                    'value'    => $value[2],
                    'logical'  => $logicalOperator,
                ];
            } else {
                $this->wheres[] = [
                    'column'   => $column,
                    'operator' => '=',
                    'value'    => $value,
                    'logical'  => $logicalOperator,
                ];
            }
        }

        return $this;
    }

    /**
     * Specifies which columns to group by. This can be called multiple times
     * to group by multiple columns.
     *
     * Example: `->groupBy('id')`
     * Example: `->groupBy('id')->groupBy('name')`
     *
     * @param string $column
     * @return static
     */
    public function groupBy(string $column): static
    {
        $this->groupBy[] = $column;
        return $this;
    }

    /**
     * Specifies which having constraints to apply to the query. This method
     * uses AND as the logical operator, if there are multiple having
     * constraints. To use OR as the logical operator, use the `orHaving`
     * method instead. For clarity, `andHaving` is also available as an alias
     * of this method.
     *
     * This method can be used in multiple ways, depending on your preference:
     *
     * Example: `->having('id', 1)`
     * Example: `->having('id', '>', 1)`
     * Example: `->having(['id' => 1, 'name' => 'John Doe'])`
     * Example: `->having([[['id', 1], ['name', 'John Doe']]])`
     *
     * @param int|string|array<string|int|array<string|int>> ...$args
     * @return static
     */
    public function having(...$args): static
    {
        return $this->addHaving('AND', ...$args);
    }

    /**
     * Specifies which having constraints to apply to the query. This method
     * uses AND as the logical operator, if there are multiple having
     * constraints. To use OR as the logical operator, use the `orHaving`
     * method instead.
     *
     * @param int|string|array<string|int|array<string|int>> ...$args
     * @return static
     * @see having()
     *
     */
    public function andHaving(...$args): static
    {
        return $this->having(...$args);
    }

    /**
     * Specifies which having constraints to apply to the query. This method
     * uses OR as the logical operator, if there are multiple having
     * constraints. To use AND as the logical operator, use the `having` or
     * `andHaving` methods instead.
     *
     * @param int|string|array<string|int|array<string|int>> ...$args
     * @return static
     * @see having()
     *
     */
    public function orHaving(...$args): static
    {
        return $this->addHaving('OR', ...$args);
    }

    /**
     * Specifies which having constraints to apply to the query.
     *
     * @param string                                         $logicalOperator
     * @param int|string|array<string|int|array<string|int>> ...$args
     *
     * @return static
     */
    public function addHaving(string $logicalOperator, ...$args): static
    {
        if (count($args) === 2 && !is_array($args[0]) && !is_array($args[1])) {
            $this->havings[] = [
                'column'   => $args[0],
                'operator' => '=',
                'value'    => $args[1],
                'logical'  => $logicalOperator,
            ];
            return $this;
        }

        if (count($args) === 3 && !is_array($args[0]) && !is_array($args[1]) && !is_array($args[2])) {
            $this->havings[] = [
                'column'   => $args[0],
                'operator' => $args[1],
                'value'    => $args[2],
                'logical'  => $logicalOperator,
            ];
            return $this;
        }

        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }

        /** @var array<string|int|array<string|int>> $args */
        foreach ($args as $column => $value) {
            if (is_array($value)) {
                $this->havings[] = [
                    'column'   => $value[0],
                    'operator' => $value[1],
                    'value'    => $value[2],
                    'logical'  => $logicalOperator,
                ];
            } else {
                $this->havings[] = [
                    'column'   => $column,
                    'operator' => '=',
                    'value'    => $value,
                    'logical'  => $logicalOperator,
                ];
            }
        }

        return $this;
    }

    /**
     * Specifies which columns to order by. This method can be called multiple
     * times to order by multiple columns.
     *
     * Example: `->orderBy('id')`
     * Example: `->orderBy('id', 'DESC')`
     * Example: `->orderBy('id')->orderBy('name')`
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = compact('column', 'direction');
        return $this;
    }

    /**
     * Specifies which columns to order by, in descending order. This method
     * can be called multiple times to order by multiple columns.
     *
     * Example: `->orderByDesc('id')`
     * Example: `->orderByDesc('id')->orderByDesc('name')`
     *
     * @param string $column
     * @return static
     * @see orderBy()
     */
    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Set the maximum number of records to return.
     *
     * @param int $limit
     * @return static
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the number of records to skip.
     *
     * @param int $offset
     * @return static
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Converts the entire query to a SQL string.
     *
     * @return string
     * @throws UnsupportedQueryTypeException
     * @throws InvalidQueryException
     */
    public function toSql(): string
    {
        try {
            return $this->grammar->generateSql($this);
        } catch (NoQueryException|UnsupportedQueryTypeException) {
            throw new InvalidQueryException();
        }
    }

    /**
     * Executes the query and returns the results as an array of objects.
     *
     * The output type can be 'ARRAY_A', 'ARRAY_N', 'OBJECT', 'OBJECT_K', where
     * the default is 'OBJECT'.
     *
     * @link https://developer.wordpress.org/reference/classes/wpdb/get_results/
     *
     * @param string $output
     * @return array<mixed>
     */
    public function get(string $output = 'OBJECT'): array
    {
        try {
            return $this->grammar->getResults($this, $output);
        } catch (NoResultsException|UnsupportedQueryTypeException) {
            return [];
        }
    }

    /**
     * Executes the query and returns the number of affected rows.
     * This method is only available for insert, update and delete queries.
     * For select queries, use the `get` method instead.
     *
     * @return int
     */
    public function execute(): int
    {
        return $this->grammar->execute($this);
    }

    /**
     * Return the columns that should be returned by the query.
     *
     * @return array<string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Return the table which the query is targeting.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Return the where constraints for the query.
     *
     * @return array<array<int|string>>
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * Return the columns that should be updated.
     *
     * @return array<string|int>
     */
    public function getSets(): array
    {
        return $this->sets;
    }

    /**
     * Return the values that should be inserted.
     *
     * @return array<int|string>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Return the groupings for the query.
     *
     * @return array<string>
     */
    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    /**
     * Return the having constraints for the query.
     *
     * @return array<array<int|string>>
     */
    public function getHavings(): array
    {
        return $this->havings;
    }

    /**
     * Return the orderings for the query.
     *
     * @return array<array<string>>
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * Return the maximum number of records to return.
     *
     * @return int|null $limit
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Return the number of records to skip.
     *
     * @return int|null $offset
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * Return the joins for the query.
     *
     * @return array<array<?string>>
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * Return whether the query returns distinct results.
     *
     * @return bool
     */
    public function getDistinct(): bool
    {
        return $this->distinct;
    }

    /**
     * Return the type of query being executed.
     *
     * @return QueryType|null
     */
    public function getQueryType(): ?QueryType
    {
        if (empty($this->queryType)) {
            return null;
        }

        return $this->queryType;
    }
}
