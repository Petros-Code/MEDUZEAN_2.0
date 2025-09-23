<?php
namespace Meduzean\EanManager\Cron;

defined('ABSPATH') || exit;

class Cron_Handler {
	/** @var mixed */
	private $service;

	public function __construct( $service ) {
		$this->service = $service;
	}

	public function daily_check() {
		// Vérifier le stock et envoyer une alerte si nécessaire
		$is_low = $this->service->check_low_stock();
		
		// Log pour debug
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[Meduzean Cron] Daily check completed. Low stock: ' . ($is_low ? 'Yes' : 'No'));
		}
		
		return $is_low;
	}
}


