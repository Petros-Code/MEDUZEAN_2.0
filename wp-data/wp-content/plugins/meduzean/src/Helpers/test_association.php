<?php
/**
 * Fichier de test pour Product_Association_Helper
 * 
 * Ce fichier peut être exécuté depuis l'administration WordPress
 * ou via une page temporaire pour tester les fonctions d'association.
 */

// Vérifier que nous sommes dans WordPress
if (!defined('ABSPATH')) {
    // Si exécuté directement, inclure WordPress
    require_once('../../../wp-load.php');
}

use Meduzean\EanManager\Helpers\Product_Association_Helper;

echo "<h2>🧪 Test de Product_Association_Helper</h2>";

// Test 1: Créer un produit de test
echo "<h3>1. Création d'un produit de test</h3>";
$test_product = wp_insert_post([
    'post_title' => 'Produit Test EAN - ' . date('Y-m-d H:i:s'),
    'post_content' => 'Produit créé pour tester l\'association EAN',
    'post_status' => 'publish',
    'post_type' => 'product'
]);

if ($test_product) {
    echo "✅ Produit créé avec l'ID : " . $test_product . "<br>";
} else {
    echo "❌ Erreur lors de la création du produit<br>";
    exit;
}

// Test 2: Vérifier qu'il n'a pas d'EAN au début
echo "<h3>2. Vérification initiale</h3>";
$initial_ean = Product_Association_Helper::get_product_ean($test_product);
if ($initial_ean === false) {
    echo "✅ Le produit n'a pas d'EAN (comme attendu)<br>";
} else {
    echo "⚠️ Le produit a déjà un EAN : " . $initial_ean . "<br>";
}

// Test 3: Vérifier has_ean
echo "<h3>3. Test de has_ean()</h3>";
$has_ean = Product_Association_Helper::has_ean($test_product);
echo "Le produit a un EAN : " . ($has_ean ? 'Oui' : 'Non') . "<br>";

// Test 4: Association automatique d'un EAN
echo "<h3>4. Association automatique d'un EAN</h3>";
$assigned_ean = Product_Association_Helper::associate_ean_to_product($test_product);

if ($assigned_ean) {
    echo "✅ EAN associé avec succès : " . $assigned_ean . "<br>";
} else {
    echo "❌ Aucun EAN disponible pour l'association<br>";
    echo "💡 Vérifiez qu'il y a des codes EAN dans la base de données MEDUZEAN<br>";
}

// Test 5: Vérifier l'association
echo "<h3>5. Vérification de l'association</h3>";
$retrieved_ean = Product_Association_Helper::get_product_ean($test_product);
if ($retrieved_ean) {
    echo "✅ EAN récupéré : " . $retrieved_ean . "<br>";
} else {
    echo "❌ Impossible de récupérer l'EAN<br>";
}

// Test 6: Test de has_ean après association
echo "<h3>6. Test de has_ean() après association</h3>";
$has_ean_after = Product_Association_Helper::has_ean($test_product);
echo "Le produit a un EAN : " . ($has_ean_after ? 'Oui' : 'Non') . "<br>";

// Test 7: Test d'association d'un EAN spécifique
echo "<h3>7. Test d'association d'un EAN spécifique</h3>";
$specific_ean = '1234567890123';
$assign_success = Product_Association_Helper::assign_specific_ean($test_product, $specific_ean);
if ($assign_success) {
    echo "✅ EAN spécifique assigné : " . $specific_ean . "<br>";
} else {
    echo "❌ Erreur lors de l'assignation de l'EAN spécifique<br>";
}

// Test 8: Vérifier l'EAN spécifique
echo "<h3>8. Vérification de l'EAN spécifique</h3>";
$specific_retrieved = Product_Association_Helper::get_product_ean($test_product);
echo "EAN actuel : " . $specific_retrieved . "<br>";

// Test 9: Test de dissociation
echo "<h3>9. Test de dissociation</h3>";
$dissociate_success = Product_Association_Helper::dissociate_ean_from_product($test_product);
if ($dissociate_success) {
    echo "✅ EAN dissocié avec succès<br>";
} else {
    echo "❌ Erreur lors de la dissociation<br>";
}

// Test 10: Vérification finale
echo "<h3>10. Vérification finale</h3>";
$final_ean = Product_Association_Helper::get_product_ean($test_product);
if ($final_ean === false) {
    echo "✅ L'EAN a été correctement dissocié<br>";
} else {
    echo "⚠️ L'EAN est toujours présent : " . $final_ean . "<br>";
}

// Test 11: Test avec des paramètres invalides
echo "<h3>11. Test avec des paramètres invalides</h3>";
$invalid_tests = [
    'ID négatif' => Product_Association_Helper::associate_ean_to_product(-1),
    'ID zéro' => Product_Association_Helper::associate_ean_to_product(0),
    'ID inexistant' => Product_Association_Helper::associate_ean_to_product(999999),
    'ID non numérique' => Product_Association_Helper::associate_ean_to_product('abc')
];

foreach ($invalid_tests as $test_name => $result) {
    echo $test_name . " : " . ($result === false ? "✅ Correctement rejeté" : "❌ Erreur") . "<br>";
}

// Nettoyage
echo "<h3>🧹 Nettoyage</h3>";
$cleanup = wp_delete_post($test_product, true);
if ($cleanup) {
    echo "✅ Produit de test supprimé<br>";
} else {
    echo "⚠️ Impossible de supprimer le produit de test (ID: " . $test_product . ")<br>";
}

echo "<h3>✅ Tests terminés !</h3>";
echo "<p><strong>Note :</strong> Pour que l'association automatique fonctionne, assurez-vous qu'il y a des codes EAN disponibles dans la base de données MEDUZEAN.</p>";
?>
