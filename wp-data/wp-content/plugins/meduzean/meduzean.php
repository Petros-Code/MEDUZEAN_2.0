<?php
/**
 * Plugin Name: Meduzean EAN Manager
 * Description: Gestion des codes EAN (import, assignation, alertes cron).
 * Version: 1.2.0 Refacto
 * Author: Petros-Code
 * Text Domain: meduzean
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

use Meduzean\EanManager\Core\Constants;
use Meduzean\EanManager\Core\Bootstrap;
use Meduzean\EanManager\Core\Plugin;
use Meduzean\EanManager\DB\Ean_Table;

// === Autoloader simple et robuste ===
spl_autoload_register(function ($class) {
    $prefix = 'Meduzean\\EanManager\\';
    $base_dir = plugin_dir_path(__FILE__) . 'src/';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative_class = substr($class, strlen($prefix));
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// === Initialisation des constantes ===
Constants::setPluginFile(__FILE__);

// === Initialisation du bootstrap ===
$bootstrap = new Bootstrap(new Ean_Table());

// === Hooks d'activation/désactivation ===
register_activation_hook(__FILE__, [$bootstrap, 'activate']);
register_deactivation_hook(__FILE__, [$bootstrap, 'deactivate']);

// === Chargement du plugin ===
add_action('plugins_loaded', function () {
    load_plugin_textdomain(
        Constants::TEXT_DOMAIN,
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );

    $plugin = Plugin::instance();
    $plugin->registerHooks();
});

// === Personnalisation de l'icône du plugin ===
add_filter('plugin_row_meta', function($plugin_meta, $plugin_file) {
    if (plugin_basename(__FILE__) === $plugin_file) {
        $plugin_meta[] = '<a href="' . admin_url('admin.php?page=' . Constants::PLUGIN_SLUG) . '">' . 
            __('Gérer les EAN', Constants::TEXT_DOMAIN) . '</a>';
    }
    return $plugin_meta;
}, 10, 2);
