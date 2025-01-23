<?php
/**
 * Plugin Name: WPQB Database Manager
 * Plugin URI: https://github.com/bitfactory-nl/wpqb
 * Description: Adds multiple database support to WordPress using WPQB
 * Version: 1.0.0
 * Author: Bitfactory
 * Author URI: https://www.bitfactory.nl
 * License: MIT
 */

use Expedition\Wpqb\Config\PostgresConfig;
use Expedition\Wpqb\Connection\PostgresConnection;
use Expedition\Wpqb\Grammar\PostgresGrammar;
use Expedition\Wpqb\Grammar\MysqlGrammar;

// Voorkom directe toegang tot dit bestand
if (!defined('ABSPATH')) {
    exit;
}

// Laad de Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

class WPQB_Database_Manager {
    private static ?PostgresConnection $postgres_connection = null;
    private static ?\wpdb $mysql_connection = null;
    
    public static function init() {
        // Voeg menu item toe aan WordPress admin
        add_action('admin_menu', [self::class, 'add_admin_menu']);
        
        // Hook voor het opslaan van de instellingen
        add_action('admin_init', [self::class, 'register_settings']);

        // Hook voor het initialiseren van de juiste database verbinding
        add_action('init', [self::class, 'initialize_database_connection']);

        // Filter voor het overschrijven van de wpdb instance
        add_filter('query', [self::class, 'maybe_modify_query'], 10, 1);
    }

    public static function add_admin_menu() {
        add_options_page(
            'WPQB Database Settings',
            'WPQB Database',
            'manage_options',
            'wpqb-database',
            [self::class, 'render_settings_page']
        );
    }

    public static function register_settings() {
        register_setting('wpqb_database', 'wpqb_database_type');
        register_setting('wpqb_database', 'wpqb_postgres_host');
        register_setting('wpqb_database', 'wpqb_postgres_port');
        register_setting('wpqb_database', 'wpqb_postgres_database');
        register_setting('wpqb_database', 'wpqb_postgres_username');
        register_setting('wpqb_database', 'wpqb_postgres_password');
    }

    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>WPQB Database Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wpqb_database');
                do_settings_sections('wpqb_database');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Database Type</th>
                        <td>
                            <select name="wpqb_database_type">
                                <option value="mysql" <?php selected(get_option('wpqb_database_type', 'mysql'), 'mysql'); ?>>MySQL (WordPress Default)</option>
                                <option value="postgres" <?php selected(get_option('wpqb_database_type'), 'postgres'); ?>>PostgreSQL</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" colspan="2">
                            <h3>PostgreSQL Settings</h3>
                            <p class="description">Only needed if PostgreSQL is selected</p>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">Host</th>
                        <td>
                            <input type="text" name="wpqb_postgres_host" 
                                value="<?php echo esc_attr(get_option('wpqb_postgres_host', 'localhost')); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Port</th>
                        <td>
                            <input type="number" name="wpqb_postgres_port" 
                                value="<?php echo esc_attr(get_option('wpqb_postgres_port', '5432')); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Database</th>
                        <td>
                            <input type="text" name="wpqb_postgres_database" 
                                value="<?php echo esc_attr(get_option('wpqb_postgres_database')); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Username</th>
                        <td>
                            <input type="text" name="wpqb_postgres_username" 
                                value="<?php echo esc_attr(get_option('wpqb_postgres_username')); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Password</th>
                        <td>
                            <input type="password" name="wpqb_postgres_password" 
                                value="<?php echo esc_attr(get_option('wpqb_postgres_password')); ?>" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function initialize_database_connection() {
        global $wpdb;

        $database_type = get_option('wpqb_database_type', 'mysql');

        if ($database_type === 'postgres') {
            if (self::$postgres_connection === null) {
                $config = new PostgresConfig(
                    get_option('wpqb_postgres_host', 'localhost'),
                    (int) get_option('wpqb_postgres_port', '5432'),
                    get_option('wpqb_postgres_database'),
                    get_option('wpqb_postgres_username'),
                    get_option('wpqb_postgres_password')
                );
                
                self::$postgres_connection = new PostgresConnection($config);
                
                // Sla de originele MySQL verbinding op
                self::$mysql_connection = $wpdb;
            }
        }
    }

    public static function maybe_modify_query($query) {
        if (get_option('wpqb_database_type', 'mysql') === 'postgres') {
            // Converteer MySQL query naar PostgreSQL syntax indien nodig
            $query = self::convert_mysql_to_postgres($query);
        }
        return $query;
    }

    private static function convert_mysql_to_postgres($query) {
        // Basis MySQL naar PostgreSQL query conversies
        $replacements = [
            // Quotes
            '`' => '"',
            
            // Auto increment
            'AUTO_INCREMENT' => 'SERIAL',
            
            // Data types
            'TINYINT' => 'SMALLINT',
            'MEDIUMINT' => 'INTEGER',
            'BIGINT UNSIGNED' => 'BIGINT',
            'INT UNSIGNED' => 'INTEGER',
            'UNSIGNED' => '',
            'BINARY' => 'BYTEA',
            'DATETIME' => 'TIMESTAMP',
            
            // Functions
            'IFNULL' => 'COALESCE',
            'FROM_UNIXTIME' => 'to_timestamp',
            'UNIX_TIMESTAMP' => 'extract(epoch from',
            'FOUND_ROWS()' => 'COUNT(*) OVER()',
            
            // Syntax
            'GROUP_CONCAT' => 'string_agg',
            'LIMIT ? OFFSET ?' => 'LIMIT ? OFFSET ?',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $query
        );
    }

    public static function getConnection() {
        $database_type = get_option('wpqb_database_type', 'mysql');
        
        if ($database_type === 'postgres') {
            if (self::$postgres_connection === null) {
                self::initialize_database_connection();
            }
            return self::$postgres_connection;
        }
        
        return self::$mysql_connection ?? $GLOBALS['wpdb'];
    }

    public static function getGrammar() {
        $database_type = get_option('wpqb_database_type', 'mysql');
        $connection = self::getConnection();
        
        return $database_type === 'postgres' 
            ? new PostgresGrammar($connection)
            : new MysqlGrammar($connection);
    }
}

// Initialiseer de plugin
WPQB_Database_Manager::init(); 