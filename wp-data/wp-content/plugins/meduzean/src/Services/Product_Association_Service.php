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

    /**
     * Associe automatiquement un EAN au produit nouvellement créé
     * 
     * @param int $product_id ID du produit WordPress/WooCommerce
     * @return bool|string False si échec, EAN associé si succès
     */
    public function auto_assign_ean_to_product($product_id) {
        // Vérifier que le produit existe
        if (!$this->is_valid_product($product_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Meduzean] Produit invalide pour association EAN: $product_id");
            }
            return false;
        }

        // Vérifier qu'il n'a pas déjà un EAN
        if ($this->product_has_ean($product_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Meduzean] Produit $product_id a déjà un EAN");
            }
            return false;
        }

        // Récupérer le premier EAN disponible
        $available_ean = $this->get_first_available_ean();
        if (!$available_ean) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Meduzean] Aucun EAN disponible pour le produit $product_id");
            }
            return false;
        }

        // Effectuer l'association bidirectionnelle
        return $this->associate_ean_to_product($available_ean['ean'], $product_id);
    }

    /**
     * Vérifie si c'est un produit valide (WordPress post ou WooCommerce product)
     */
    private function is_valid_product($product_id) {
        $post = get_post($product_id);
        if (!$post) {
            return false;
        }

        // Accepter les produits WordPress standard et WooCommerce
        return in_array($post->post_type, ['product', 'post']) && $post->post_status === 'publish';
    }

    /**
     * Vérifie si le produit a déjà un EAN
     */
    private function product_has_ean($product_id) {
        // Vérifier dans les métadonnées du produit
        $existing_ean = get_post_meta($product_id, '_ean', true);
        if (!empty($existing_ean)) {
            return true;
        }

        // Vérifier dans notre table EAN
        global $wpdb;
        $ean_table = $this->ean_table->get_table_name();
        $existing = $wpdb->get_var(
            $wpdb->prepare("SELECT ean FROM {$ean_table} WHERE product_id = %d LIMIT 1", $product_id)
        );

        return !empty($existing);
    }

    /**
     * Récupère le premier EAN disponible
     */
    private function get_first_available_ean() {
        global $wpdb;
        $ean_table = $this->ean_table->get_table_name();
        
        $ean = $wpdb->get_row(
            "SELECT * FROM {$ean_table} WHERE product_id IS NULL ORDER BY ean_add_date ASC LIMIT 1",
            ARRAY_A
        );

        return $ean;
    }

    /**
     * Effectue l'association bidirectionnelle EAN ↔ Produit
     */
    private function associate_ean_to_product($ean, $product_id) {
        global $wpdb;
        $ean_table = $this->ean_table->get_table_name();

        // 1. Mettre à jour la table EAN
        $updated = $wpdb->update(
            $ean_table,
            [
                'product_id' => $product_id,
                'association_date' => current_time('mysql')
            ],
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

        // 2. Ajouter l'EAN dans les métadonnées du produit
        $meta_updated = update_post_meta($product_id, '_ean', $ean);
        
        if ($meta_updated === false) {
            // Rollback de la table EAN en cas d'erreur
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

        // 3. Log de succès
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Meduzean] Association réussie: EAN $ean -> Produit $product_id");
        }

        return $ean;
    }

    /**
     * Dissocie un EAN d'un produit (utile pour les suppressions)
     */
    public function dissociate_ean_from_product($product_id) {
        global $wpdb;
        $ean_table = $this->ean_table->get_table_name();

        // Récupérer l'EAN associé
        $ean = get_post_meta($product_id, '_ean', true);
        if (empty($ean)) {
            return true; // Pas d'EAN à dissocier
        }

        // 1. Nettoyer la table EAN
        $wpdb->update(
            $ean_table,
            ['product_id' => null, 'association_date' => null],
            ['ean' => $ean],
            ['%s', '%s'],
            ['%s']
        );

        // 2. Supprimer la métadonnée
        delete_post_meta($product_id, '_ean');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Meduzean] Dissociation: EAN $ean <- Produit $product_id");
        }

        return true;
    }
}
