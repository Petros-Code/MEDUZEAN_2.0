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
        // Aucun hook - plus d'intégration avec les produits
    }
}