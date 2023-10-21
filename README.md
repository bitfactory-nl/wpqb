# WPQB
A flexible and powerful query builder tailored for WordPress, making complex database queries simple.

![](https://img.shields.io/github/actions/workflow/status/Bowero/wpqb/tests.yml)

## Description
WP Query Builder is a modern and extensible PHP library that offers a fluent, chainable interface to build and execute WordPress database queries. Instead of writing raw SQL or wrestling with WP's default methods, use this query builder to easily compose advanced queries.

### Features
- Fluent, chainable API for easy query composition.
- Support for various query types: SELECT, UPDATE, INSERT, and more.
- Built-in safeguards and helpers to avoid SQL injection.
- Supports advanced query features like joins, ordering, and limits.
- Integrated with the global $wpdb WordPress database object for query preparation and execution.
- 80%+ code coverage, so you can be sure the plugin works reliably.
- Perfect static analysis results.

## Installation
Simply download the latest release and put it in your plugins folder, or use the plugin installer in WordPress. You're ready to start writing kick-ass queries!

## Basic usage
```php
use Bowero\Wpqb\Query;

// Select query
$results = Query::select('name')
    ->distinct()
    ->from('wp_posts')
    ->where('post_status', '=', 'publish')
    ->where('post_type', '=', 'post')
    ->limit(10)
    ->orderBy('post_date', 'DESC')
    ->get();
```

## Contribution
Help is greatly appreciated here! There's a lot that still needs to be done, as it is currently just a weekend project. You can contribute in a lot of different ways:

- Writing tests
- Writing documentation (or examples for users of the plugin)
- Writing code
- Opening issues
- Tell others!

## License
This plugin is licensed under the MIT-license. Do whatever you like with it. Go conquer the world. Or don't.