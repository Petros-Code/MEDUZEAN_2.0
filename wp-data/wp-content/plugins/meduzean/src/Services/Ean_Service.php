<?php
namespace Meduzean\EanManager\Services;

use Meduzean\EanManager\DB\Ean_Table;
use Meduzean\EanManager\Helpers\Validator;

defined('ABSPATH') || exit;

class Ean_Service {
	/** @var Ean_Table */
	private $table;
	/** @var Email_Service */
	private $emailService;

	public function __construct( Ean_Table $table, Email_Service $emailService ) {
		$this->table = $table;
		$this->emailService = $emailService;
	}

	public function import_eans( array $eans ) {
		$imported = 0;
		$errors = [];

		foreach ($eans as $ean) {
			$ean = trim($ean);
			if (empty($ean)) {
				continue;
			}

			if (!Validator::is_valid_ean13($ean)) {
				$errors[] = sprintf(__('Code EAN invalide: %s', 'meduzean'), $ean);
				continue;
			}

			if ($this->table->ean_exists($ean)) {
				$errors[] = sprintf(__('Code EAN déjà existant: %s', 'meduzean'), $ean);
				continue;
			}

			if ($this->table->insert_ean($ean)) {
				$imported++;
			} else {
				$errors[] = sprintf(__('Erreur lors de l\'insertion: %s', 'meduzean'), $ean);
			}
		}

		return [
			'imported' => $imported,
			'errors' => $errors
		];
	}

	public function get_available_count() {
		return $this->table->count_all('available');
	}

	public function get_total_count() {
		return $this->table->count_all();
	}

	public function check_low_stock() {
		$threshold = get_option('meduzean_low_stock_threshold', 10);
		$available = $this->get_available_count();
		
		if ($available < $threshold) {
			$this->emailService->send_low_stock_alert($available, $threshold);
			return true;
		}
		
		return false;
	}

	public function assign_to_product($ean, $product_id) {
		global $wpdb;
		
		// Vérifier que le produit existe
		if (!get_post($product_id)) {
			return new \WP_Error('product_not_found', __('Produit introuvable.', 'meduzean'));
		}

		// Vérifier que l'EAN existe et est disponible
		$ean_id = $this->table->ean_exists($ean);
		if (!$ean_id) {
			return new \WP_Error('ean_not_found', __('Code EAN introuvable.', 'meduzean'));
		}

		// Vérifier que l'EAN n'est pas déjà assigné
		$existing = $wpdb->get_var($wpdb->prepare(
			"SELECT product_id FROM {$this->table->get_table_name()} WHERE ean = %s",
			$ean
		));

		if ($existing) {
			return new \WP_Error('ean_already_assigned', __('Ce code EAN est déjà assigné à un produit.', 'meduzean'));
		}

		// Assigner l'EAN au produit
		$result = $wpdb->update(
			$this->table->get_table_name(),
			[
				'product_id' => $product_id,
				'association_date' => current_time('mysql')
			],
			['ean' => $ean],
			['%d', '%s'],
			['%s']
		);

		if ($result !== false) {
			return true;
		}

		return new \WP_Error('assignment_failed', __('Erreur lors de l\'assignation du code EAN.', 'meduzean'));
	}
}


