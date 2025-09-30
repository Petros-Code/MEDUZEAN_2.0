<?php
namespace Meduzean\EanManager\API;

use Meduzean\EanManager\DB\Ean_Table;
use Meduzean\EanManager\Helpers\Validator;

defined('ABSPATH') || exit;

class Rest_Controller {
	/** @var mixed */
	private $service;
	/** @var Ean_Table */
	private $table;
	private $namespace = 'meduzean/v1';

	public function __construct( $service ) {
		$this->service = $service;
		$this->table = new Ean_Table();
	}

	public function register_routes() {
		// Route pour lister les EAN
		register_rest_route($this->namespace, '/eans', [
			'methods' => 'GET',
			'callback' => [$this, 'get_eans'],
			'permission_callback' => [$this, 'check_permissions'],
			'args' => [
				'page' => [
					'type' => 'integer',
					'default' => 1,
					'sanitize_callback' => 'absint'
				],
				'per_page' => [
					'type' => 'integer',
					'default' => 20,
					'sanitize_callback' => 'absint'
				],
				'availability' => [
					'type' => 'string',
					'default' => '',
					'enum' => ['', 'available', 'used'],
					'sanitize_callback' => 'sanitize_text_field'
				]
			]
		]);

		// Route pour créer des EAN
		register_rest_route($this->namespace, '/eans', [
			'methods' => 'POST',
			'callback' => [$this, 'create_eans'],
			'permission_callback' => [$this, 'check_permissions'],
			'args' => [
				'eans' => [
					'type' => 'array',
					'required' => true,
					'items' => [
						'type' => 'string',
						'pattern' => '^[0-9]{13}$'
					]
				]
			]
		]);

		// Route pour supprimer un EAN
		register_rest_route($this->namespace, '/eans/(?P<id>\d+)', [
			'methods' => 'DELETE',
			'callback' => [$this, 'delete_ean'],
			'permission_callback' => [$this, 'check_permissions'],
			'args' => [
				'id' => [
					'type' => 'integer',
					'required' => true,
					'sanitize_callback' => 'absint'
				]
			]
		]);

		// Route pour assigner un EAN à un produit
		register_rest_route($this->namespace, '/eans/assign', [
			'methods' => 'POST',
			'callback' => [$this, 'assign_ean'],
			'permission_callback' => [$this, 'check_permissions'],
			'args' => [
				'ean' => [
					'type' => 'string',
					'required' => true,
					'pattern' => '^[0-9]{13}$',
					'sanitize_callback' => 'sanitize_text_field'
				],
				'product_id' => [
					'type' => 'integer',
					'required' => true,
					'sanitize_callback' => 'absint'
				]
			]
		]);
	}

	public function check_permissions() {
		return current_user_can('manage_options');
	}

	public function get_eans($request) {
		$page = $request->get_param('page');
		$per_page = $request->get_param('per_page');
		$availability = $request->get_param('availability');

		$offset = ($page - 1) * $per_page;
		$eans = $this->table->getAll($per_page, $offset, 'ean_add_date', 'DESC', $availability);
		$total = $this->table->countAll($availability);

		return new \WP_REST_Response([
			'data' => $eans,
			'total' => $total,
			'pages' => ceil($total / $per_page),
			'current_page' => $page
		], 200);
	}

	public function create_eans($request) {
		$eans = $request->get_param('eans');
		$imported = 0;
		$errors = [];

		foreach ($eans as $ean) {
			if (!Validator::is_valid_ean13($ean)) {
				$errors[] = sprintf(__('Code EAN invalide: %s', 'meduzean'), $ean);
				continue;
			}

			if ($this->table->eanExists($ean)) {
				$errors[] = sprintf(__('Code EAN déjà existant: %s', 'meduzean'), $ean);
				continue;
			}

			if ($this->table->insertEan($ean)) {
				$imported++;
			} else {
				$errors[] = sprintf(__('Erreur lors de l\'insertion: %s', 'meduzean'), $ean);
			}
		}

		return new \WP_REST_Response([
			'imported' => $imported,
			'errors' => $errors,
			'message' => sprintf(__('%d codes EAN importés avec succès.', 'meduzean'), $imported)
		], 200);
	}

	public function delete_ean($request) {
		$id = $request->get_param('id');

		if ($this->table->deleteById($id)) {
			return new \WP_REST_Response([
				'message' => __('Code EAN supprimé avec succès.', 'meduzean')
			], 200);
		}

		return new \WP_REST_Response([
			'message' => __('Erreur lors de la suppression du code EAN.', 'meduzean')
		], 400);
	}

	public function assign_ean($request) {
		$ean = $request->get_param('ean');
		$product_id = $request->get_param('product_id');

		// Vérifier que le produit existe
		if (!get_post($product_id)) {
			return new \WP_REST_Response([
				'message' => __('Produit introuvable.', 'meduzean')
			], 404);
		}

		// Vérifier que l'EAN existe et est disponible
		$ean_id = $this->table->eanExists($ean);
		if (!$ean_id) {
			return new \WP_REST_Response([
				'message' => __('Code EAN introuvable.', 'meduzean')
			], 404);
		}

		// Vérifier que l'EAN n'est pas déjà assigné
		global $wpdb;
		$existing = $wpdb->get_var($wpdb->prepare(
			"SELECT product_id FROM {$this->table->getTableName()} WHERE ean = %s",
			$ean
		));

		if ($existing) {
			return new \WP_REST_Response([
				'message' => __('Ce code EAN est déjà assigné à un produit.', 'meduzean')
			], 400);
		}

		// Assigner l'EAN au produit
		$result = $wpdb->update(
			$this->table->getTableName(),
			[
				'product_id' => $product_id,
				'association_date' => current_time('mysql')
			],
			['ean' => $ean],
			['%d', '%s'],
			['%s']
		);

		if ($result !== false) {
			return new \WP_REST_Response([
				'message' => __('Code EAN assigné avec succès.', 'meduzean')
			], 200);
		}

		return new \WP_REST_Response([
			'message' => __('Erreur lors de l\'assignation du code EAN.', 'meduzean')
		], 500);
	}
}


