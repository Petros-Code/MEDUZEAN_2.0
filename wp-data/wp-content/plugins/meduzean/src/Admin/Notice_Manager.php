<?php
namespace Meduzean\EanManager\Admin;

defined('ABSPATH') || exit;

class Notice_Manager {
    
    public function __construct() {
        add_action('admin_notices', [$this, 'display_notices']);
    }

    /**
     * Affiche les notices admin stockées
     */
    public function display_notices() {
        $notices = get_option('meduzean_admin_notices', []);
        
        if (empty($notices)) {
            return;
        }

        foreach ($notices as $notice) {
            $class = 'notice-' . $notice['type'];
            echo '<div class="notice ' . esc_attr($class) . ' is-dismissible">';
            echo '<p>' . esc_html($notice['message']) . '</p>';
            echo '</div>';
        }

        // Nettoyer les notices affichées
        delete_option('meduzean_admin_notices');
    }
}
