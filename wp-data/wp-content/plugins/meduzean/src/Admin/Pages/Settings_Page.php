<?php
namespace Meduzean\EanManager\Admin\Pages;

defined('ABSPATH') || exit;

class Settings_Page {
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Accès refusé', 'meduzean'));
        }

        // Gestion de la sauvegarde
        $this->handle_save();
        
        // Gestion du test d'alerte
        $this->handle_test_alert();

        // Récupération des options
        $threshold = get_option('meduzean_low_stock_threshold', 10);
        $email = get_option('meduzean_notification_email', get_option('admin_email'));
        $auto_assign = get_option('meduzean_auto_assign', 'no');

        ?>
        <div class="wrap">
            <h1><?php _e('Réglages EAN Manager', 'meduzean'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('meduzean_save_settings', 'meduzean_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="low_stock_threshold"><?php _e('Seuil d\'alerte stock bas', 'meduzean'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="low_stock_threshold" name="low_stock_threshold" 
                                   value="<?php echo esc_attr($threshold); ?>" min="1" max="1000">
                            <p class="description"><?php _e('Nombre minimum de codes EAN disponibles avant déclenchement d\'une alerte.', 'meduzean'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="notification_email"><?php _e('Email de notification', 'meduzean'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="notification_email" name="notification_email" 
                                   value="<?php echo esc_attr($email); ?>" class="regular-text">
                            <p class="description"><?php _e('Adresse email pour recevoir les alertes de stock bas.', 'meduzean'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="auto_assign"><?php _e('Assignation automatique', 'meduzean'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="auto_assign" value="yes" <?php checked($auto_assign, 'yes'); ?>>
                                    <?php _e('Oui', 'meduzean'); ?>
                                </label><br>
                                <label>
                                    <input type="radio" name="auto_assign" value="no" <?php checked($auto_assign, 'no'); ?>>
                                    <?php _e('Non', 'meduzean'); ?>
                                </label>
                            </fieldset>
                            <p class="description"><?php _e('Permettre l\'assignation automatique des codes EAN aux produits.', 'meduzean'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Sauvegarder les réglages', 'meduzean')); ?>
            </form>

            <div class="card" style="margin-top: 20px;">
                <h2><?php _e('Statistiques', 'meduzean'); ?></h2>
                <?php $this->render_stats(); ?>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2><?php _e('Test d\'alerte', 'meduzean'); ?></h2>
                <p><?php _e('Testez l\'envoi d\'un email d\'alerte pour vérifier la configuration.', 'meduzean'); ?></p>
                <form method="post" style="display: inline-block;">
                    <?php wp_nonce_field('meduzean_test_alert', 'meduzean_test_nonce'); ?>
                    <?php submit_button(__('Envoyer un test d\'alerte', 'meduzean'), 'secondary', 'test_alert'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    private function handle_save() {
        if (!isset($_POST['meduzean_settings_nonce']) || !wp_verify_nonce($_POST['meduzean_settings_nonce'], 'meduzean_save_settings')) {
            return;
        }

        $threshold = intval($_POST['low_stock_threshold']);
        $email = sanitize_email($_POST['notification_email']);
        $auto_assign = sanitize_text_field($_POST['auto_assign']);

        if ($threshold < 1) {
            $threshold = 1;
        }

        if (!is_email($email)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Adresse email invalide.', 'meduzean') . '</p></div>';
            });
            return;
        }

        update_option('meduzean_low_stock_threshold', $threshold);
        update_option('meduzean_notification_email', $email);
        update_option('meduzean_auto_assign', $auto_assign);

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Réglages sauvegardés avec succès.', 'meduzean') . '</p></div>';
        });
    }

    private function render_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ean_codes';
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        $available = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE product_id IS NULL");
        $used = $total - $available;
        
        $threshold = get_option('meduzean_low_stock_threshold', 10);
        $is_low = $available < $threshold;
        
        ?>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong><?php _e('Total des codes EAN:', 'meduzean'); ?></strong></td>
                    <td><?php echo number_format($total); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Codes EAN disponibles:', 'meduzean'); ?></strong></td>
                    <td>
                        <?php echo number_format($available); ?>
                        <?php if ($is_low): ?>
                            <span class="dashicons dashicons-warning" style="color: #d63638;" title="<?php _e('Stock bas!', 'meduzean'); ?>"></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Codes EAN utilisés:', 'meduzean'); ?></strong></td>
                    <td><?php echo number_format($used); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Seuil d\'alerte:', 'meduzean'); ?></strong></td>
                    <td><?php echo number_format($threshold); ?></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    private function handle_test_alert() {
        if (!isset($_POST['meduzean_test_nonce']) || !wp_verify_nonce($_POST['meduzean_test_nonce'], 'meduzean_test_alert')) {
            return;
        }

        if (!isset($_POST['test_alert'])) {
            return;
        }

        // Utiliser le service email pour envoyer un test
        $email_service = new \Meduzean\EanManager\Services\Email_Service();
        $available_count = $this->get_available_count();
        $threshold = get_option('meduzean_low_stock_threshold', 10);

        $result = $email_service->send_low_stock_alert($available_count, $threshold);

        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Email de test envoyé avec succès !', 'meduzean') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Erreur lors de l\'envoi de l\'email de test.', 'meduzean') . '</p></div>';
            });
        }
    }

    private function get_available_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ean_codes';
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE product_id IS NULL");
    }
}
