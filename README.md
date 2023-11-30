# WPQB
A flexible and powerful query builder tailored for WordPress, making complex database queries simple.

![](https://img.shields.io/github/actions/workflow/status/bitfactory-nl/wpqb/tests.yml)

## Description
WP Query Builder is a modern and extensible PHP library that offers a fluent, chainable interface to build and execute WordPress database queries. Instead of writing raw SQL or wrestling with WP's default methods, use this query builder to easily compose advanced queries.

If you like WPQB, please consider starring it.

### Features
- Fluent, chainable API for easy query composition.
- Support for various query types: SELECT, UPDATE, INSERT, and more. (under development)
- Built-in safeguards and helpers to avoid SQL injection.
- Supports advanced query features like joins, ordering, and limits.
- Integrated with the global $wpdb WordPress database object for query preparation and execution.
- 80%+ code coverage, so you can be sure the plugin works reliably.
- Perfect static analysis results.

## Installation
Simply download the latest release and put it in your plugins folder, or use the plugin installer in WordPress. You're ready to start writing kick-ass queries!

## Basic usage
```php
use Expedition\Wpqb\Query;

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

## Why WPQB instead of other plugins?
There are other PHP packages out there that help you with query building such as `doctrine/dbal`. It's a brilliant package, it truly is. For WordPress, there are a few cons however:

- Because they don't deeply integrate with WordPress, they won't trigger all hooks, filters and caching mechanisms.
- You miss out on optimisations that have been done for `$wpdb`.
- A lot of extra overhead has to be imported, especially if it's an entire ORM.

These drawbacks are tackled by using this plugin.

## Contribution
Help is greatly appreciated here! There's a lot that still needs to be done, as it is currently just a weekend project. You can contribute in a lot of different ways:

- Writing tests
- Writing documentation (or examples for users of the plugin)
- Writing code
- Opening issues
- Tell others!

## Tools
- PHPStan: Run PHPStan with `composer run-script phpstan`
- PHP RC Fixer: Run the PHP RC Fixer with `composer run-script phprcfix`
- PEST: Run PEST with `composer run-script test`

## License
This plugin is licensed under the MIT-license. Do whatever you like with it. Go conquer the world. Or don't.
