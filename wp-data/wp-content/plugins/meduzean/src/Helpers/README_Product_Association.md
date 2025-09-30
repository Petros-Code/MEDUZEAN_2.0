# Product Association Helper

## Description
Cette classe fournit des méthodes statiques pour associer des codes EAN aux produits de manière simple, sans dépendre de WooCommerce.

## Utilisation

### 1. Association automatique d'un EAN
```php
use Meduzean\EanManager\Helpers\Product_Association_Helper;

// Associer automatiquement le premier EAN disponible
$ean = Product_Association_Helper::associate_ean_to_product($product_id);

if ($ean) {
    echo "EAN associé : " . $ean;
} else {
    echo "Aucun EAN disponible";
}
```

### 2. Vérifier si un produit a un EAN
```php
if (Product_Association_Helper::has_ean($product_id)) {
    echo "Le produit a un EAN";
} else {
    echo "Le produit n'a pas d'EAN";
}
```

### 3. Récupérer l'EAN d'un produit
```php
$ean = Product_Association_Helper::get_product_ean($product_id);
if ($ean) {
    echo "EAN du produit : " . $ean;
}
```

### 4. Associer un EAN spécifique
```php
$success = Product_Association_Helper::assign_specific_ean($product_id, '1234567890123');
if ($success) {
    echo "EAN assigné avec succès";
}
```

### 5. Dissocier un EAN
```php
$success = Product_Association_Helper::dissociate_ean_from_product($product_id);
if ($success) {
    echo "EAN dissocié avec succès";
}
```

## Méta Keys utilisées
- `_ean` : Code EAN-13 du produit

## Notes importantes
- Tous les EAN doivent faire exactement 13 chiffres
- Les méthodes valident automatiquement l'existence du produit
- La synchronisation avec la table EAN interne est automatique
- Compatible avec n'importe quelle plateforme (pas seulement WooCommerce)
