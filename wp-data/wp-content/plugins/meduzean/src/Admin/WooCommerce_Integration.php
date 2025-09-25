<?php
namespace Meduzean\EanManager\Admin;

defined('ABSPATH') || exit;

class WooCommerce_Integration {

    public function __construct() {
        // Vérifier que WooCommerce est actif
        if (function_exists('WC')) {
            $this->register_hooks();
        }
    }

    /**
     * Enregistre les hooks WooCommerce
     */
    private function register_hooks() {
        // Ajouter le champ EAN dans l'onglet "Général" du produit
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_ean_field']);
        
        // Sauvegarder le champ EAN
        add_action('woocommerce_process_product_meta', [$this, 'save_ean_field']);
        
        // Afficher l'EAN dans la liste des produits (admin)
        add_filter('manage_product_posts_columns', [$this, 'add_ean_column']);
        add_action('manage_product_posts_custom_column', [$this, 'display_ean_column'], 10, 2);
        
        // Afficher l'EAN sur la page produit frontend (optionnel)
        add_action('woocommerce_single_product_summary', [$this, 'display_ean_frontend'], 25);
    }

    /**
     * Ajoute le champ EAN dans l'onglet "Général" du produit
     */
    public function add_ean_field() {
        global $post;
        
        echo '<div class="options_group">';
        
        woocommerce_wp_text_input([
            'id' => '_ean',
            'label' => __('Code EAN', 'meduzean'),
            'description' => __('Code EAN-13 du produit (géré automatiquement par MEDUZEAN)', 'meduzean'),
            'desc_tip' => true,
            'type' => 'text',
            'custom_attributes' => [
                'pattern' => '[0-9]{13}',
                'maxlength' => '13',
                'placeholder' => '1234567890123'
            ]
        ]);
        
        echo '</div>';
        
        // Message informatif si EAN automatique
        $ean = get_post_meta($post->ID, '_ean', true);
        if (!empty($ean)) {
            echo '<div class="options_group">';
            echo '<p class="form-field" style="color: #46b450; font-style: italic;">';
            echo '<strong>' . __('ℹ️ EAN associé automatiquement par MEDUZEAN', 'meduzean') . '</strong><br>';
            echo __('Vous pouvez modifier ce code EAN si nécessaire.', 'meduzean');
            echo '</p>';
            echo '</div>';
        }
    }

    /**
     * Sauvegarde le champ EAN
     */
    public function save_ean_field($post_id) {
        if (isset($_POST['_ean'])) {
            $ean = sanitize_text_field($_POST['_ean']);
            
            // Valider l'EAN si fourni
            if (!empty($ean)) {
                // Nettoyer l'EAN (garder seulement les chiffres)
                $ean = preg_replace('/\D/', '', $ean);
                
                // Vérifier la longueur
                if (strlen($ean) === 13) {
                    update_post_meta($post_id, '_ean', $ean);
                    
                    // TODO: Mettre à jour aussi notre table EAN si nécessaire
                    $this->sync_ean_table($post_id, $ean);
                } elseif (strlen($ean) === 0) {
                    // Supprimer l'EAN
                    delete_post_meta($post_id, '_ean');
                    $this->remove_from_ean_table($post_id);
                } else {
                    // EAN invalide - ne pas sauvegarder
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Code EAN invalide (doit contenir exactement 13 chiffres)', 'meduzean') . '</p></div>';
                    });
                }
            } else {
                // Champ vide - supprimer l'EAN
                delete_post_meta($post_id, '_ean');
                $this->remove_from_ean_table($post_id);
            }
        }
    }

    /**
     * Synchronise avec notre table EAN
     */
    private function sync_ean_table($product_id, $ean) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ean_codes';
        
        // Vérifier si l'EAN existe dans notre table
        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE ean = %s", $ean),
            ARRAY_A
        );
        
        if ($existing) {
            // Mettre à jour l'association
            $wpdb->update(
                $table_name,
                [
                    'product_id' => $product_id,
                    'association_date' => current_time('mysql')
                ],
                ['ean' => $ean],
                ['%d', '%s'],
                ['%s']
            );
        }
    }

    /**
     * Retire de notre table EAN
     */
    private function remove_from_ean_table($product_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ean_codes';
        
        $wpdb->update(
            $table_name,
            [
                'product_id' => null,
                'association_date' => null
            ],
            ['product_id' => $product_id],
            ['%s', '%s'],
            ['%d']
        );
    }

    /**
     * Ajoute la colonne EAN dans la liste des produits
     */
    public function add_ean_column($columns) {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'name') {
                $new_columns['ean'] = __('EAN', 'meduzean');
            }
        }
        return $new_columns;
    }

    /**
     * Affiche l'EAN dans la colonne de la liste des produits
     */
    public function display_ean_column($column, $post_id) {
        if ($column === 'ean') {
            $ean = get_post_meta($post_id, '_ean', true);
            if (!empty($ean)) {
                echo '<code style="font-weight: bold; color: #0073aa;">' . esc_html($ean) . '</code>';
            } else {
                echo '<span style="color: #999;">—</span>';
            }
        }
    }

    /**
     * Affiche l'EAN sur la page produit frontend (optionnel)
     */
    public function display_ean_frontend() {
        global $post;
        
        $ean = get_post_meta($post->ID, '_ean', true);
        if (!empty($ean)) {
            echo '<div class="product-ean" style="margin: 10px 0;">';
            echo '<strong>' . __('Code EAN:', 'meduzean') . '</strong> ';
            echo '<span style="font-family: monospace; background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">' . esc_html($ean) . '</span>';
            echo '</div>';
        }
    }
}
