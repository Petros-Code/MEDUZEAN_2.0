<?php
namespace Meduzean\EanManager\Hooks;

use Meduzean\EanManager\Services\Product_Association_Service;

defined('ABSPATH') || exit;

class Product_Hooks {
    /** @var Product_Association_Service */
    private $association_service;

    public function __construct() {
        $this->association_service = new Product_Association_Service();
    }

    /**
     * Enregistre tous les hooks liés aux produits
     */
    public function register_hooks() {
        // Hook WordPress universel - Se déclenche pour tous les post_types
        add_action('wp_insert_post', [$this, 'on_post_created'], 10, 3);

        // Hook WooCommerce spécifique - Se déclenche après sauvegarde du produit
        if (function_exists('WC')) {
            add_action('woocommerce_process_product_meta', [$this, 'on_woocommerce_product_saved'], 10, 1);
        }

        // Hook de suppression de produit
        add_action('before_delete_post', [$this, 'on_product_deleted'], 10, 1);
    }

    /**
     * Gestionnaire pour wp_insert_post
     * Se déclenche à la création de tout post WordPress
     */
    public function on_post_created($post_id, $post, $update) {
        // NOUVELLE LOGIQUE : Association seulement à la publication
        
        // Vérifier que c'est un produit
        if (!$this->is_product_post($post)) {
            return;
        }

        // Vérifier que c'est une création (pas une mise à jour)
        if ($update) {
            // Si c'est une mise à jour, vérifier si on passe de brouillon à publié
            $old_status = get_post_meta($post_id, '_wp_old_status', true);
            if ($old_status === 'publish' || $post->post_status !== 'publish') {
                return; // Pas une première publication
            }
        }

        // Vérifier que le produit est maintenant publié
        if ($post->post_status !== 'publish') {
            return;
        }

        // Vérifier qu'il n'a pas déjà un EAN
        $existing_ean = get_post_meta($post_id, '_ean', true);
        if (!empty($existing_ean)) {
            return; // Déjà un EAN
        }

        // ASSOCIATION AUTOMATIQUE : Prendre le premier EAN disponible
        $assigned_ean = $this->association_service->auto_assign_ean_to_product($post_id);
        
        if ($assigned_ean) {
            $this->add_admin_notice(sprintf(
                __('✅ EAN %s automatiquement associé au produit "%s" lors de sa publication', 'meduzean'),
                $assigned_ean,
                $post->post_title
            ), 'success');
        } else {
            $this->add_admin_notice(sprintf(
                __('⚠️ Aucun EAN disponible pour le produit "%s" - Ajoutez des codes EAN dans EAN Manager', 'meduzean'),
                $post->post_title
            ), 'warning');
        }
    }

    /**
     * Gestionnaire pour woocommerce_process_product_meta
     * Se déclenche quand on clique "Publier" ou "Mettre à jour" dans WooCommerce
     */
    public function on_woocommerce_product_saved($product_id) {
        // Vérifier que le produit est publié
        $product = get_post($product_id);
        if (!$product || $product->post_status !== 'publish') {
            return;
        }

        // Vérifier qu'il n'a pas déjà un EAN
        $existing_ean = get_post_meta($product_id, '_ean', true);
        if (!empty($existing_ean)) {
            return; // Déjà un EAN
        }

        // ASSOCIATION AUTOMATIQUE : Prendre le premier EAN disponible
        $assigned_ean = $this->association_service->auto_assign_ean_to_product($product_id);
        
        if ($assigned_ean) {
            $this->add_admin_notice(sprintf(
                __('✅ EAN %s automatiquement associé au produit WooCommerce "%s"', 'meduzean'),
                $assigned_ean,
                $product->post_title
            ), 'success');
        } else {
            $this->add_admin_notice(sprintf(
                __('⚠️ Aucun EAN disponible pour le produit "%s" - Ajoutez des codes EAN dans EAN Manager', 'meduzean'),
                $product->post_title
            ), 'warning');
        }
    }

    /**
     * Gestionnaire de suppression de produit
     */
    public function on_product_deleted($post_id) {
        $post = get_post($post_id);
        if (!$post || !$this->is_product_post($post)) {
            return;
        }

        // Dissocier l'EAN pour le rendre à nouveau disponible
        $this->association_service->dissociate_ean_from_product($post_id);
    }

    /**
     * Vérifie si le post est un produit
     */
    private function is_product_post($post) {
        // WordPress standard avec custom post type 'product'
        // ou WooCommerce avec post_type 'product'
        return $post->post_type === 'product';
    }

    /**
     * Ajoute une notice admin
     */
    private function add_admin_notice($message, $type = 'info') {
        // Stocker la notice en option temporaire pour l'afficher au prochain chargement admin
        $notices = get_option('meduzean_admin_notices', []);
        $notices[] = [
            'message' => $message,
            'type' => $type,
            'time' => time()
        ];
        
        // Garder seulement les 10 dernières notices
        $notices = array_slice($notices, -10);
        update_option('meduzean_admin_notices', $notices);
    }
}
