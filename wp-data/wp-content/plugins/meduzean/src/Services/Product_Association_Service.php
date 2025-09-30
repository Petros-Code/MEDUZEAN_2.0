<?php
namespace Meduzean\EanManager\Services;

use Meduzean\EanManager\DB\Ean_Table;

defined('ABSPATH') || exit;

class Product_Association_Service {
    /** @var Ean_Table */
    private $ean_table;

    public function __construct() {
        $this->ean_table = new Ean_Table();
    }

    public function auto_assign_ean_to_product($product_id) {
        if (!$this->is_valid_product($product_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Meduzean] Produit invalide pour association EAN: $product_id");
            }
            return false;
        }

        if ($this->product_has_ean($product_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Meduzean] Produit $product_id a déjà un EAN");
            }
            return false;
        }

        $available_ean = $this->get_first_available_ean();
        if (!$available_ean) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Meduzean] Aucun EAN disponible pour le produit $product_id");
            }
            return false;
        }

        return $this->associate_ean_to_product($available_ean['ean'], $product_id);
    }

    private function is_valid_product($product_id) {
        $post = get_post($product_id);
        if (!$post) return false;
        return in_array($post->post_type, ['product', 'post']) && $post->post_status === 'publish';
    }

    private function product_has_ean($product_id) {
        $existing_ean = get_post_meta($product_id, '_ean', true);
        if (!empty($existing_ean)) return true;

        global $wpdb;
        $ean_table = $this->ean_table->getTableName();
        $existing = $wpdb->get_var($wpdb->prepare("SELECT ean FROM {$ean_table} WHERE product_id = %d LIMIT 1", $product_id));
        return !empty($existing);
    }

    private function get_first_available_ean() {
        global $wpdb;
        $ean_table = $this->ean_table->getTableName();
        return $wpdb->get_row("SELECT * FROM {$ean_table} WHERE product_id IS NULL ORDER BY ean_add_date ASC LIMIT 1", ARRAY_A);
    }

    private function associate_ean_to_product($ean, $product_id) {
        global $wpdb;
        $ean_table = $this->ean_table->getTableName();

        $updated = $wpdb->update(
            $ean_table,
            ['product_id' => $product_id, 'association_date' => current_time('mysql')],
            ['ean' => $ean],
            ['%d', '%s'],
            ['%s']
        );

        if ($updated === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Meduzean] Erreur mise à jour table EAN: $ean -> $product_id");
            }
            return false;
        }

        $meta_updated = update_post_meta($product_id, '_ean', $ean);
        
        if ($meta_updated === false) {
            $wpdb->update(
                $ean_table,
                ['product_id' => null, 'association_date' => null],
                ['ean' => $ean],
                ['%s', '%s'],
                ['%s']
            );
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Meduzean] Erreur mise à jour meta produit: $ean -> $product_id");
            }
            return false;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Meduzean] Association réussie: EAN $ean -> Produit $product_id");
        }

        return $ean;
    }

    public function dissociate_ean_from_product($product_id) {
        global $wpdb;
        $ean_table = $this->ean_table->getTableName();

        $ean = get_post_meta($product_id, '_ean', true);
        if (empty($ean)) return true;

        $wpdb->update(
            $ean_table,
            ['product_id' => null, 'association_date' => null],
            ['ean' => $ean],
            ['%s', '%s'],
            ['%s']
        );

        delete_post_meta($product_id, '_ean');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Meduzean] Dissociation: EAN $ean <- Produit $product_id");
        }

        return true;
    }
}
