<?php
/**
 * Test rapide de Product_Association_Helper
 * 
 * Version simplifiée pour tester rapidement la fonction principale
 */

// Vérifier que nous sommes dans WordPress
if (!defined('ABSPATH')) {
    // Essayer plusieurs chemins possibles pour wp-load.php
    $wp_load_paths = [
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php',
        dirname(__FILE__) . '/../../../wp-load.php',
        dirname(__FILE__) . '/../../../../wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('❌ Erreur : Impossible de charger WordPress. Vérifiez le chemin vers wp-load.php');
    }
}

use Meduzean\EanManager\Helpers\Product_Association_Helper;

echo "<h2>⚡ Test Rapide - Association EAN</h2>";

// Créer un produit de test simple
$product_id = wp_insert_post([
    'post_title' => 'Test EAN - ' . time(),
    'post_status' => 'publish',
    'post_type' => 'product'
]);

if (!$product_id) {
    echo "❌ Erreur : Impossible de créer le produit de test";
    exit;
}

echo "📦 Produit créé (ID: $product_id)<br><br>";

// Test principal : Association d'un EAN
echo "🔗 Test d'association d'un EAN...<br>";
$ean = Product_Association_Helper::associate_ean_to_product($product_id);

if ($ean) {
    echo "✅ SUCCÈS ! EAN associé : <strong>$ean</strong><br>";
    
    // Vérifier que l'EAN est bien sauvegardé
    $saved_ean = get_post_meta($product_id, '_ean', true);
    echo "💾 EAN sauvegardé dans les meta : $saved_ean<br>";
    
    // Vérifier avec notre fonction
    $retrieved_ean = Product_Association_Helper::get_product_ean($product_id);
    echo "🔍 EAN récupéré via get_product_ean() : $retrieved_ean<br>";
    
} else {
    echo "❌ ÉCHEC : Aucun EAN disponible<br>";
    echo "💡 Vérifiez qu'il y a des codes EAN dans MEDUZEAN<br>";
}

// Nettoyage
wp_delete_post($product_id, true);
echo "<br>🧹 Produit de test supprimé";
?>
