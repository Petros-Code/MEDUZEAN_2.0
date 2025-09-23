<?php
/**
 * Plugin Name: Meduzean EAN Manager
 * Description: Gestion des codes EAN (import, assignation, alertes cron).
 * Version: 1.0.2
 * Author: Petros-Code
 * Text Domain: meduzean
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

// === Constantes ===
if (!defined('MEDUZEAN_VERSION')) {
    define('MEDUZEAN_VERSION', '1.0.0');
}
if (!defined('MEDUZEAN_PLUGIN_FILE')) {
    define('MEDUZEAN_PLUGIN_FILE', __FILE__);
}
if (!defined('MEDUZEAN_PLUGIN_DIR')) {
    define('MEDUZEAN_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('MEDUZEAN_PLUGIN_URL')) {
    define('MEDUZEAN_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('MEDUZEAN_DB_VERSION_OPTION')) {
    define('MEDUZEAN_DB_VERSION_OPTION', 'meduzean_db_version');
}

// === Autoloader PSR-4 simple ===
spl_autoload_register(function ($class) {
    $prefix   = 'Meduzean\\EanManager\\';
    $base_dir = MEDUZEAN_PLUGIN_DIR . 'src/';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative_class = substr($class, strlen($prefix));
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Meduzean Autoload] Trying to load $class from $file");
    }

    if (file_exists($file)) {
        require_once $file;
    }
});

// === Hooks d’activation/désactivation ===
function meduzean_activate() {
    // Création / mise à jour de la table
    $table = new \Meduzean\EanManager\DB\Ean_Table();
    $table->create_or_update_table();

    // Planification du cron
    if (!wp_next_scheduled('meduzean_ean_manager_daily_check')) {
        wp_schedule_event(time(), 'daily', 'meduzean_ean_manager_daily_check');
    }
}
register_activation_hook(MEDUZEAN_PLUGIN_FILE, 'meduzean_activate');

function meduzean_deactivate() {
    wp_clear_scheduled_hook('meduzean_ean_manager_daily_check');
}
register_deactivation_hook(MEDUZEAN_PLUGIN_FILE, 'meduzean_deactivate');

// === Chargement du plugin ===
add_action('plugins_loaded', function () {
    load_plugin_textdomain('meduzean', false, dirname(plugin_basename(MEDUZEAN_PLUGIN_FILE)) . '/languages');

    $plugin = \Meduzean\EanManager\Core\Plugin::instance();
    $plugin->register_hooks();
});
