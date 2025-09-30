<?php
namespace Meduzean\EanManager\Helpers;

use Meduzean\EanManager\Services\Product_Association_Service;

defined('ABSPATH') || exit;

/**
 * Helper pour l'association d'EAN aux produits
 * 
 * Cette classe fournit des méthodes statiques pour associer des codes EAN
 * à des produits de manière simple, sans dépendre de WooCommerce.
 * 
 * @package Meduzean\EanManager\Helpers
 */
class Product_Association_Helper {

    /**
     * Associe un EAN disponible à un produit
     * 
     * @param int $product_id ID du produit WordPress
     * @return string|false L'EAN associé ou false si aucun EAN disponible
     */
    public static function associate_ean_to_product($product_id) {
        // Vérifier que l'ID du produit est valide
        if (!is_numeric($product_id) || $product_id <= 0) {
            return false;
        }

        // Vérifier que le produit existe
        $product = get_post($product_id);
        if (!$product) {
            return false;
        }

        // Vérifier qu'il n'a pas déjà un EAN
        $existing_ean = get_post_meta($product_id, '_ean', true);
        if (!empty($existing_ean)) {
            return $existing_ean; // Retourner l'EAN existant
        }

        // Utiliser le service d'association existant
        $association_service = new Product_Association_Service();
        $assigned_ean = $association_service->auto_assign_ean_to_product($product_id);
        
        return $assigned_ean ?: false;
    }

    /**
     * Dissocie l'EAN d'un produit
     * 
     * @param int $product_id ID du produit WordPress
     * @return bool True si la dissociation a réussi
     */
    public static function dissociate_ean_from_product($product_id) {
        // Vérifier que l'ID du produit est valide
        if (!is_numeric($product_id) || $product_id <= 0) {
            return false;
        }

        // Vérifier que le produit existe
        $product = get_post($product_id);
        if (!$product) {
            return false;
        }

        // Utiliser le service d'association existant
        $association_service = new Product_Association_Service();
        return $association_service->dissociate_ean_from_product($product_id);
    }

    /**
     * Récupère l'EAN associé à un produit
     * 
     * @param int $product_id ID du produit WordPress
     * @return string|false L'EAN associé ou false si aucun EAN
     */
    public static function get_product_ean($product_id) {
        // Vérifier que l'ID du produit est valide
        if (!is_numeric($product_id) || $product_id <= 0) {
            return false;
        }

        // Vérifier que le produit existe
        $product = get_post($product_id);
        if (!$product) {
            return false;
        }

        // Récupérer l'EAN depuis les meta
        $ean = get_post_meta($product_id, '_ean', true);
        return !empty($ean) ? $ean : false;
    }

    /**
     * Vérifie si un produit a un EAN associé
     * 
     * @param int $product_id ID du produit WordPress
     * @return bool True si le produit a un EAN
     */
    public static function has_ean($product_id) {
        return self::get_product_ean($product_id) !== false;
    }

    /**
     * Associe un EAN spécifique à un produit
     * 
     * @param int $product_id ID du produit WordPress
     * @param string $ean Code EAN à associer
     * @return bool True si l'association a réussi
     */
    public static function assign_specific_ean($product_id, $ean) {
        // Vérifier que l'ID du produit est valide
        if (!is_numeric($product_id) || $product_id <= 0) {
            return false;
        }

        // Vérifier que le produit existe
        $product = get_post($product_id);
        if (!$product) {
            return false;
        }

        // Valider l'EAN (13 chiffres)
        $ean = preg_replace('/\D/', '', $ean);
        if (strlen($ean) !== 13) {
            return false;
        }

        // Sauvegarder l'EAN dans les meta
        $result = update_post_meta($product_id, '_ean', $ean);
        
        if ($result) {
            // Mettre à jour la table EAN si nécessaire
            self::sync_ean_table($product_id, $ean);
        }
        
        return $result !== false;
    }

    /**
     * Synchronise avec la table EAN interne
     * 
     * @param int $product_id ID du produit
     * @param string $ean Code EAN
     * @return bool True si la synchronisation a réussi
     */
    private static function sync_ean_table($product_id, $ean) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ean_codes';
        
        // Vérifier si l'EAN existe dans notre table
        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE ean = %s", $ean),
            ARRAY_A
        );
        
        if ($existing) {
            // Mettre à jour l'association
            return $wpdb->update(
                $table_name,
                [
                    'product_id' => $product_id,
                    'association_date' => current_time('mysql')
                ],
                ['ean' => $ean],
                ['%d', '%s'],
                ['%s']
            ) !== false;
        }
        
        return false;
    }
}
