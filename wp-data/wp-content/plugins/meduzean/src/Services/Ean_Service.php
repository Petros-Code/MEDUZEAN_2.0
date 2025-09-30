<?php
namespace Meduzean\EanManager\Services;

use Meduzean\EanManager\DB\Ean_Table;
use Meduzean\EanManager\Helpers\Validator;
use Meduzean\EanManager\Interfaces\ServiceInterface;
use Meduzean\EanManager\Interfaces\EmailServiceInterface;
use Meduzean\EanManager\Exceptions\EanException;

defined('ABSPATH') || exit;

class Ean_Service implements ServiceInterface
{
	private $table;
	private $emailService;

	public function __construct(Ean_Table $table, EmailServiceInterface $emailService) {
		$this->table = $table;
		$this->emailService = $emailService;
	}

	public function import_eans(array $eans) {
		$imported = 0;
		$errors = [];

		foreach ($eans as $ean) {
			$ean = trim($ean);
			if (empty($ean)) continue;

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

		return ['imported' => $imported, 'errors' => $errors];
	}

	public function getAvailableCount(): int
	{
		return $this->table->countAll('available');
	}

	public function getTotalCount(): int
	{
		return $this->table->countAll();
	}

	public function checkLowStock(): bool
	{
		$threshold = get_option('meduzean_low_stock_threshold', 10);
		$available = $this->getAvailableCount();
		
		if ($available < $threshold) {
			$this->emailService->sendLowStockAlert($available, $threshold);
			return true;
		}
		
		return false;
	}

	public function assign_to_product($ean, $product_id) {
		global $wpdb;
		
		if (!get_post($product_id)) {
			return new \WP_Error('product_not_found', __('Produit introuvable.', 'meduzean'));
		}

		$ean_id = $this->table->eanExists($ean);
		if (!$ean_id) {
			throw new EanException(__('Code EAN introuvable.', 'meduzean'), EanException::EAN_NOT_FOUND);
		}

		$existing = $wpdb->get_var($wpdb->prepare(
			"SELECT product_id FROM {$this->table->getTableName()} WHERE ean = %s",
			$ean
		));

		if ($existing) {
			return new \WP_Error('ean_already_assigned', __('Ce code EAN est déjà assigné à un produit.', 'meduzean'));
		}

		$result = $wpdb->update(
			$this->table->getTableName(),
			['product_id' => $product_id, 'association_date' => current_time('mysql')],
			['ean' => $ean],
			['%d', '%s'],
			['%s']
		);

		return $result !== false ? true : new \WP_Error('assignment_failed', __('Erreur lors de l\'assignation du code EAN.', 'meduzean'));
	}
}


