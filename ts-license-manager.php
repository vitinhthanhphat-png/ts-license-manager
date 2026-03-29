<?php
/**
 * Plugin Name: TS License Manager
 * Plugin URI:  https://techsharevn.com
 * Description: RSA-2048 License Key Manager with Quasar admin UI. Generate keys, issue licenses, and manage domains.
 * Version:     1.0.1
 * Author:      TechShareVN
 * Author URI:  https://techsharevn.com
 * Text Domain: ts-license-manager
 * Domain Path: /languages
 * Requires PHP: 8.0
 * Requires at least: 5.9
 * License:     GPL-2.0-or-later
 */

defined('ABSPATH') || exit;

// Plugin constants
define('TSLM_VERSION', '1.0.0');
define('TSLM_PLUGIN_FILE', __FILE__);
define('TSLM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TSLM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TSLM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoload classes
spl_autoload_register(function (string $class): void {
    $prefix = 'TSLM\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = TSLM_PLUGIN_DIR . 'includes/class-' . strtolower(str_replace(['\\', '_'], ['-', '-'], $relative)) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Plugin activation
 */
function tslm_activate(): void {
    require_once TSLM_PLUGIN_DIR . 'includes/class-database.php';
    TSLM\Database::install();

    // Create protected keys directory
    $upload_dir = wp_upload_dir();
    $keys_dir = $upload_dir['basedir'] . '/ts-license-keys';
    if (!is_dir($keys_dir)) {
        wp_mkdir_p($keys_dir);
    }

    // Protect directory with .htaccess
    $htaccess = $keys_dir . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Order deny,allow\nDeny from all\n");
    }

    // Also add index.php
    $index = $keys_dir . '/index.php';
    if (!file_exists($index)) {
        file_put_contents($index, '<?php // Silence is golden.');
    }

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'tslm_activate');

/**
 * Plugin deactivation
 */
function tslm_deactivate(): void {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'tslm_deactivate');

/**
 * Initialize plugin
 */
function tslm_init(): void {
    // Check PHP version
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('TS License Manager requires PHP 8.0+. Current: ' . PHP_VERSION, 'ts-license-manager');
            echo '</p></div>';
        });
        return;
    }

    // Check OpenSSL
    if (!extension_loaded('openssl')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('TS License Manager requires PHP OpenSSL extension.', 'ts-license-manager');
            echo '</p></div>';
        });
        return;
    }

    // Initialize components
    new TSLM\Admin_Menu();

    // REST API — must init on rest_api_init
    add_action('rest_api_init', function () {
        $api = new TSLM\Rest_Api();
        $api->register_routes();
    });

    // OTA Update via GitHub
    new TSLM\GitHub_Updater('vitinhthanhphat-png', 'ts-license-manager', TSLM_PLUGIN_FILE);
}
add_action('plugins_loaded', 'tslm_init');
