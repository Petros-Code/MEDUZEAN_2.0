<?php
namespace Meduzean\EanManager\Admin;

use Meduzean\EanManager\Admin\Pages\Ean_List_Page;
use Meduzean\EanManager\Admin\Pages\Ean_Import_Page;
use Meduzean\EanManager\Admin\Pages\Settings_Page;
use Meduzean\EanManager\Core\Constants;

defined('ABSPATH') || exit;

class Admin {
	private $service;

	public function __construct($service) {
		$this->service = $service;
	}

	public function register_menus() {
		if (!current_user_can('manage_options')) {
			return;
		}
		add_menu_page(
			__('EAN Manager', Constants::TEXT_DOMAIN),
			__('EAN Manager', Constants::TEXT_DOMAIN),
			'manage_options',
			Constants::PLUGIN_SLUG,
			[$this, 'render_list_page'],
			Constants::getPluginUrl() . 'assets/icon.png'
		);
		add_submenu_page(
			Constants::PLUGIN_SLUG,
			__('Importer EAN', Constants::TEXT_DOMAIN),
			__('Importer', Constants::TEXT_DOMAIN),
			'manage_options',
			Constants::PLUGIN_SLUG . '-import',
			[$this, 'render_import_page']
		);
		add_submenu_page(
			Constants::PLUGIN_SLUG,
			__('Réglages', Constants::TEXT_DOMAIN),
			__('Réglages', Constants::TEXT_DOMAIN),
			'manage_options',
			Constants::PLUGIN_SLUG . '-settings',
			[$this, 'render_settings_page']
		);
	}

	public function register_settings() {
		add_option('meduzean_low_stock_threshold', 10);
		add_option('meduzean_notification_email', get_option('admin_email'));
		add_option('meduzean_notification_email_2', '');
		add_option('meduzean_auto_assign', 'no');
	}

	public function render_list_page() {
		(new Ean_List_Page())->render();
	}

	public function render_import_page() {
		(new Ean_Import_Page())->render();
	}

	public function render_settings_page() {
		(new Settings_Page())->render();
	}
}
