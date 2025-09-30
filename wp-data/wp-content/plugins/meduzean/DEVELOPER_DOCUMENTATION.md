# MEDUZEAN EAN Manager - Documentation Développeur

## Vue d'ensemble

**MEDUZEAN EAN Manager** est un plugin WordPress autonome pour la gestion des codes EAN (European Article Number). Le module a été entièrement refactorisé selon les standards PSR-4 et PSR-12 avec une architecture orientée objet complète.

## Architecture

### Structure des dossiers (PSR-4)

```
src/
├── Core/                  # Classes principales
│   ├── Constants.php      # Constantes du plugin
│   ├── Bootstrap.php      # Activation/désactivation
│   ├── Plugin.php         # Classe principale
│   └── Autoloader.php     # Autoloader PSR-4
├── Admin/                 # Interface d'administration
│   ├── Admin.php          # Gestion des menus
│   ├── Assets.php         # CSS/JS
│   ├── Notice_Manager.php # Notifications
│   └── Pages/             # Pages admin
│       ├── Ean_List_Page.php
│       ├── Ean_Import_Page.php
│       └── Settings_Page.php
├── API/                   # API REST
│   └── Rest_Controller.php
├── Cron/                  # Tâches planifiées
│   └── Cron_Handler.php
├── DB/                    # Base de données
│   └── Ean_Table.php
├── Services/              # Logique métier
│   ├── Ean_Service.php
│   ├── Email_Service.php
│   └── Product_Association_Service.php
├── Helpers/               # Utilitaires
│   ├── Product_Association_Helper.php
│   └── Validator.php
├── Interfaces/            # Contrats
│   ├── RepositoryInterface.php
│   ├── ServiceInterface.php
│   └── EmailServiceInterface.php
└── Exceptions/            # Gestion d'erreurs
    └── EanException.php
```

## Classes principales

### Core\Plugin
**Singleton** - Point d'entrée principal du plugin
```php
public static function instance(): self
public function registerHooks(): void
```

### DB\Ean_Table
**Repository** - Gestion de la base de données
```php
public function createOrUpdateTable(): void
public function insertEan(string $ean): int|false
public function eanExists(string $ean): int|false
public function getAll(int $limit, int $offset, string $orderby, string $order, string $availability): array
public function countAll(string $availability): int
public function deleteById(int $id): bool
public function getTableName(): string
```

### Services\Ean_Service
**Service principal** - Logique métier des EAN
```php
public function importEans(array $eans): array
public function getAvailableCount(): int
public function getTotalCount(): int
public function checkLowStock(): bool
public function assignToProduct(string $ean, int $product_id): bool|\WP_Error
```

### Services\Email_Service
**Service email** - Notifications
```php
public function sendLowStockAlert(int $availableCount, int $threshold): bool
```

## Interfaces

### RepositoryInterface
```php
public function getTableName(): string
public function createOrUpdateTable(): void
public function insertEan(string $ean): int|false
public function eanExists(string $ean): int|false
public function getAll(int $limit, int $offset, string $orderby, string $order, string $availability): array
public function countAll(string $availability): int
public function deleteById(int $id): bool
public function dropTable(): void
```

### ServiceInterface
```php
public function importEans(array $eans): array
public function getAvailableCount(): int
public function getTotalCount(): int
public function checkLowStock(): bool
```

### EmailServiceInterface
```php
public function sendLowStockAlert(int $availableCount, int $threshold): bool
```

## API REST

### Endpoints disponibles

#### GET /wp-json/meduzean/v1/eans
Récupère la liste des EAN
**Paramètres :**
- `page` (int) : Numéro de page
- `per_page` (int) : Nombre d'éléments par page
- `availability` (string) : Filtre de disponibilité

#### POST /wp-json/meduzean/v1/eans
Crée de nouveaux EAN
**Body :**
```json
{
  "eans": ["1234567890123", "9876543210987"]
}
```

#### DELETE /wp-json/meduzean/v1/eans/{id}
Supprime un EAN par ID

#### POST /wp-json/meduzean/v1/assign
Assigne un EAN à un produit
**Body :**
```json
{
  "ean": "1234567890123",
  "product_id": 123
}
```

## Hooks WordPress

### Actions
```php
add_action('admin_menu', [$admin, 'register_menus']);
add_action('admin_init', [$admin, 'register_settings']);
add_action('admin_enqueue_scripts', [$assets, 'enqueue_admin_assets']);
add_action('rest_api_init', [$rest, 'register_routes']);
add_action('meduzean_ean_manager_daily_check', [$cron, 'daily_check']);
```

### Filtres
```php
add_filter('plugin_row_meta', 'customize_plugin_meta');
```

## Base de données

### Table `wp_ean_codes`
```sql
CREATE TABLE wp_ean_codes (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    ean varchar(13) NOT NULL,
    ean_add_date datetime DEFAULT CURRENT_TIMESTAMP,
    product_id bigint(20) unsigned DEFAULT NULL,
    association_date datetime DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY ean (ean),
    KEY product_id (product_id)
);
```

## Configuration

### Options WordPress
- `meduzean_low_stock_threshold` : Seuil d'alerte stock bas
- `meduzean_email_recipient` : Destinataire des alertes
- `meduzean_db_version` : Version de la base de données

### Constantes
```php
class Constants {
    public const VERSION = '1.2.0 Refacto';
    public const TEXT_DOMAIN = 'meduzean';
    public const PLUGIN_SLUG = 'meduzean-ean';
    public const DB_VERSION_OPTION = 'meduzean_db_version';
    public const NAMESPACE = 'Meduzean\\EanManager\\';
}
```

## Cron Jobs

### Tâche quotidienne
```php
wp_schedule_event(time(), 'daily', 'meduzean_ean_manager_daily_check');
```

**Fonction :** Vérification du stock bas et envoi d'alertes email

## Gestion d'erreurs

### EanException
```php
class EanException extends Exception {
    public const EAN_NOT_FOUND = 1;
    public const EAN_ALREADY_ASSIGNED = 2;
    public const EAN_INVALID = 3;
}
```

## Intégration externe

### Product_Association_Helper
Classe utilitaire pour l'intégration avec d'autres plateformes :

```php
// Associer un EAN à un produit
$ean = Product_Association_Helper::associate_ean_to_product($product_id);

// Récupérer l'EAN d'un produit
$ean = Product_Association_Helper::get_product_ean($product_id);

// Vérifier si un produit a un EAN
$has_ean = Product_Association_Helper::has_ean($product_id);

// Assigner un EAN spécifique
$result = Product_Association_Helper::assign_specific_ean($product_id, $ean);

// Dissocier un EAN
$result = Product_Association_Helper::dissociate_ean_from_product($product_id);
```

## Standards respectés

- **PSR-4** : Autoloading des classes
- **PSR-12** : Style de code
- **WordPress Coding Standards** : Intégration WordPress
- **OOP** : Architecture orientée objet complète

## Dépendances

- WordPress 5.0+
- PHP 7.4+
- Extensions : `zip` (pour XLSX), `curl` (pour API)

## Installation

1. Copier le dossier dans `/wp-content/plugins/`
2. Activer le plugin via l'admin WordPress
3. La table sera créée automatiquement
4. Configurer les paramètres dans `Codes EAN > Paramètres`

## Développement

### Tests
```php
// Fichier de test : src/Helpers/test_via_wp.php
require_once('wp-load.php');
use Meduzean\EanManager\Helpers\Product_Association_Helper;

// Test d'association
$product_id = 123;
$ean = Product_Association_Helper::associate_ean_to_product($product_id);
```

### Debug
```php
// Activer les logs
error_log('[Meduzean Debug] Message de debug');
```

## Changelog

### Version 1.3.0 Doc
- Refactorisation complète OOP
- Respect des standards PSR-4 et PSR-12
- Découplage de WooCommerce
- Ajout d'interfaces et d'exceptions
- Amélioration de l'API REST
- Optimisation des performances
