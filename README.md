# WPQB - WordPress Query Builder

A powerful and flexible query builder for WordPress that makes complex database queries simple. WPQB supports both MySQL and PostgreSQL, offering a fluent, chainable interface for building and executing WordPress database queries.

![](https://img.shields.io/github/actions/workflow/status/bitfactory-nl/wpqb/tests.yml)
![Test Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)
![Static Analysis](https://img.shields.io/badge/phpstan-level%20max-brightgreen.svg)

## Installation

You can install WPQB via Composer:

```bash
composer require expedition/wpqb
```

Or download the latest release and place it in your plugins folder.

## Basic Usage

### Initializing the Query Builder

```php
use Expedition\Wpqb\Query;

// SELECT query
$results = Query::select(['id', 'post_title'])
    ->from('wp_posts')
    ->where('post_status', '=', 'publish')
    ->where('post_type', '=', 'post')
    ->limit(10)
    ->orderBy('post_date', 'DESC')
    ->get();

// INSERT query
$id = Query::insert()
    ->into('wp_posts')
    ->values([
        'post_title' => 'My new post',
        'post_status' => 'publish',
        'post_type' => 'post'
    ])
    ->execute();

// UPDATE query
Query::update('wp_posts')
    ->set([
        'post_status' => 'draft'
    ])
    ->where('ID', '=', 123)
    ->execute();

// DELETE query
Query::delete()
    ->from('wp_posts')
    ->where('post_status', '=', 'trash')
    ->execute();
```

## Advanced Features

### PostgreSQL Specific Features

#### 1. RETURNING Clause
```php
$result = Query::insert()
    ->into('users')
    ->values([
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ])
    ->returning(['id', 'created_at'])
    ->execute();
```

#### 2. UPSERT (INSERT ON CONFLICT)
```php
Query::insert()
    ->into('users')
    ->values([
        'email' => 'john@example.com',
        'name' => 'John Doe'
    ])
    ->onConflict(['email'], [
        'name' => 'John Doe Updated'
    ])
    ->execute();
```

#### 3. JSON Operations
```php
$users = Query::select()
    ->from('users')
    ->where(Query::raw("metadata->>'preferences'"), '=', 'dark_mode')
    ->get();

Query::insert()
    ->into('users')
    ->values([
        'name' => 'John',
        'metadata' => [
            'preferences' => [
                'theme' => 'dark',
                'notifications' => true
            ]
        ]
    ])
    ->execute();
```

#### 4. Array Operations
```php
// Array contains check
$posts = Query::select()
    ->from('posts')
    ->whereRaw(Query::grammar()->arrayContains('tags', ['php', 'wordpress']))
    ->get();

// Array overlap check
$posts = Query::select()
    ->from('posts')
    ->whereRaw(Query::grammar()->arrayOverlap('categories', ['tech', 'programming']))
    ->get();
```

#### 5. Window Functions
```php
$salaries = Query::select([
    'department_id',
    'employee_name',
    'salary',
    Query::raw(Query::grammar()->rank(), 'salary_rank'),
    Query::raw(Query::grammar()->lag('salary'), 'previous_salary')
])
->from('employees')
->orderBy('department_id')
->orderBy('salary', 'DESC')
->get();
```

#### 6. Recursive Queries
```php
// Fetch hierarchical menu structure
$menu = Query::recursive()
    ->with('menu_tree', function($q) {
        return $q->select(['id', 'parent_id', 'name', 'level'])
            ->from('menu_items')
            ->where('parent_id', 'IS', null);
    })
    ->select(['mt.*'])
    ->from('menu_tree as mt')
    ->orderBy('level')
    ->get();
```

#### 7. Materialized Views
```php
// Create view
Query::grammar()->createMaterializedView('monthly_stats', 
    Query::select(['department_id', 'AVG(salary) as avg_salary'])
        ->from('employees')
        ->groupBy(['department_id'])
);

// Refresh view
Query::grammar()->refreshMaterializedView('monthly_stats', true);
```

#### 8. Geographic Queries
```php
// Create GiST index
Query::grammar()->gistIndexForGeometry('locations', 'coordinates');

// Find locations within radius
$nearby = Query::select(['name', 'location'])
    ->from('stores')
    ->whereRaw(
        Query::grammar()->withinDistance(
            'location', 
            "'POINT(4.8952 52.3702)'", 
            5000
        )
    )
    ->get();
```

### Full-text Search
```php
$posts = Query::select()
    ->from('posts')
    ->whereRaw(
        Query::grammar()->fullTextSearch('content', 'wordpress & plugin')
    )
    ->get();
```

## Complex Query Examples

### Joins with Subqueries
```php
$posts = Query::select(['p.*', 'c.comment_count'])
    ->from('wp_posts as p')
    ->leftJoin(
        Query::select(['post_id', 'COUNT(*) as comment_count'])
            ->from('wp_comments')
            ->groupBy('post_id')
            ->as('c'),
        'p.ID',
        '=',
        'c.post_id'
    )
    ->where('p.post_status', '=', 'publish')
    ->get();
```

### Advanced Aggregations
```php
$stats = Query::select([
    'post_type',
    Query::raw('COUNT(*) as total_posts'),
    Query::raw('AVG(CHAR_LENGTH(post_content)) as avg_content_length'),
    Query::raw('MAX(post_date) as latest_post')
])
->from('wp_posts')
->where('post_status', '=', 'publish')
->groupBy('post_type')
->having(Query::raw('COUNT(*)'), '>', 10)
->orderBy(Query::raw('COUNT(*)'), 'DESC')
->get();
```

## Why WPQB?

- **WordPress Integration**: Deeply integrated with WordPress, maintaining all hooks, filters, and caching mechanisms
- **Type Safety**: Prevents SQL injection through prepared statements
- **Flexibility**: Supports both MySQL and PostgreSQL with database-specific features
- **Performance**: Optimized for use with `$wpdb`
- **Modern**: Uses modern PHP features and follows PSR standards
- **Well Tested**: >80% code coverage with automated tests

## Performance Tips

1. **Use Indexes Wisely**
   - Always index columns used in WHERE, JOIN, and ORDER BY clauses
   - Consider composite indexes for frequently combined columns

2. **Optimize Queries**
   - Select only needed columns instead of using `SELECT *`
   - Use `LIMIT` and `OFFSET` for pagination
   - Consider using `EXPLAIN` to analyze query performance

3. **Caching**
   - WPQB works seamlessly with WordPress object cache
   - Use materialized views for complex, frequently-accessed data

## Contributing

Contributions are welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute.

## Testing

WPQB maintains 100% test coverage across all features. Our test suite includes:

### Core Features
- Query building and execution
- Exception handling
- SQL generation
- WordPress integration

### PostgreSQL Specific Features
- RETURNING clause
- UPSERT operations (DO UPDATE and DO NOTHING)
- JSON operations and nested structures
- Array operations and comparisons
- Window functions with all options
- Recursive queries
- Materialized views
- GiST indexes and spatial queries
- Full-text search

### Test Coverage Details
```
--------------------------------
Feature              Coverage
--------------------------------
Core Query Builder   100%
MySQL Grammar        100%
PostgreSQL Grammar   100%
Exceptions          100%
WordPress Bridge    100%
--------------------------------
Total Coverage      100%
```

### Running Tests

```bash
# Run the full test suite
composer test

# Run with coverage report
composer test:coverage

# Run static analysis
composer phpstan

# Run code style checks
composer phprcfix
```

### Continuous Integration
All pull requests are automatically tested against:
- PHP 7.4, 8.0, 8.1, and 8.2
- MySQL 5.7 and 8.0
- PostgreSQL 12, 13, and 14
- Latest WordPress version

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.
