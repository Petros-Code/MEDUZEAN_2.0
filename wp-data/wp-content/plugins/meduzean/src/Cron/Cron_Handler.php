<?php
namespace Meduzean\EanManager\Cron;

defined('ABSPATH') || exit;

class Cron_Handler {
	private $service;

	public function __construct($service) {
		$this->service = $service;
	}

	public function daily_check() {
		$is_low = $this->service->checkLowStock();
		
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[Meduzean Cron] Daily check completed. Low stock: ' . ($is_low ? 'Yes' : 'No'));
		}
		
		return $is_low;
	}
}
