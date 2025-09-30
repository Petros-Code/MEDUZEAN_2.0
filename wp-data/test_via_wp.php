<?php
/**
 * Test via WordPress - Version plus robuste
 * 
 * Ce fichier doit être placé dans le dossier racine de WordPress
 * et exécuté via : http://localhost:8080/test_via_wp.php
 */

// Charger WordPress
require_once('wp-load.php');

use Meduzean\EanManager\Helpers\Product_Association_Helper;

echo "<h2>⚡ Test EAN via WordPress</h2>";

// Vérifier que le plugin est actif
if (!class_exists('Meduzean\EanManager\Helpers\Product_Association_Helper')) {
    echo "❌ Erreur : Le plugin MEDUZEAN n'est pas actif ou la classe n'est pas trouvée";
    exit;
}

echo "✅ Plugin MEDUZEAN détecté<br><br>";

// Créer un produit de test
echo "📦 Création d'un produit de test...<br>";
$product_id = wp_insert_post([
    'post_title' => 'Test EAN Docker - ' . date('Y-m-d H:i:s'),
    'post_status' => 'publish',
    'post_type' => 'product'
]);

if (!$product_id || is_wp_error($product_id)) {
    echo "❌ Erreur : Impossible de créer le produit de test<br>";
    if (is_wp_error($product_id)) {
        echo "Détails : " . $product_id->get_error_message();
    }
    exit;
}

echo "✅ Produit créé avec l'ID : $product_id<br><br>";

// Test d'association
echo "🔗 Test d'association d'un EAN...<br>";
$ean = Product_Association_Helper::associate_ean_to_product($product_id);

if ($ean) {
    echo "✅ SUCCÈS ! EAN associé : <strong style='color: green; font-size: 18px;'>$ean</strong><br>";
    
    // Vérifications supplémentaires
    $meta_ean = get_post_meta($product_id, '_ean', true);
    echo "💾 EAN dans les meta : $meta_ean<br>";
    
    $retrieved_ean = Product_Association_Helper::get_product_ean($product_id);
    echo "🔍 EAN récupéré via get_product_ean() : $retrieved_ean<br>";
    
    $has_ean = Product_Association_Helper::has_ean($product_id);
    echo "❓ Le produit a un EAN : " . ($has_ean ? 'Oui' : 'Non') . "<br>";
    
} else {
    echo "❌ ÉCHEC : Aucun EAN disponible<br>";
    echo "💡 Vérifiez qu'il y a des codes EAN dans la base de données MEDUZEAN<br>";
    
    // Afficher des infos de debug
    echo "<br><strong>Debug :</strong><br>";
    echo "- Plugin actif : " . (is_plugin_active('meduzean/meduzean.php') ? 'Oui' : 'Non') . "<br>";
    echo "- Classe trouvée : " . (class_exists('Meduzean\EanManager\Services\Product_Association_Service') ? 'Oui' : 'Non') . "<br>";
}

// Nettoyage
echo "<br>🧹 Nettoyage...<br>";
$deleted = wp_delete_post($product_id, true);
if ($deleted) {
    echo "✅ Produit de test supprimé<br>";
} else {
    echo "⚠️ Impossible de supprimer le produit (ID: $product_id)<br>";
}

echo "<br><strong>Test terminé !</strong>";
?>
