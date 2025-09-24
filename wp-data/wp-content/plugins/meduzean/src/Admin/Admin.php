<?php
namespace Meduzean\EanManager\Admin;

use Meduzean\EanManager\Admin\Pages\Ean_List_Page;
use Meduzean\EanManager\Admin\Pages\Ean_Import_Page;
use Meduzean\EanManager\Admin\Pages\Settings_Page;

defined('ABSPATH') || exit;

class Admin {
	/** @var mixed */
	private $service;

	public function __construct( $service ) {
		$this->service = $service;
	}

	public function register_menus() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		add_menu_page(
			__( 'EAN Manager', 'meduzean' ),
			__( 'EAN Manager', 'meduzean' ),
			'manage_options',
			'meduzean-ean',
			[ $this, 'render_list_page' ],
			MEDUZEAN_PLUGIN_URL . 'assets/icon.png'
		);
		add_submenu_page(
			'meduzean-ean',
			__( 'Importer EAN', 'meduzean' ),
			__( 'Importer', 'meduzean' ),
			'manage_options',
			'meduzean-ean-import',
			[ $this, 'render_import_page' ]
		);
		add_submenu_page(
			'meduzean-ean',
			__( 'Réglages', 'meduzean' ),
			__( 'Réglages', 'meduzean' ),
			'manage_options',
			'meduzean-ean-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	public function register_settings() {
		// Enregistrement des options par défaut
		add_option('meduzean_low_stock_threshold', 10);
		add_option('meduzean_notification_email', get_option('admin_email'));
		add_option('meduzean_notification_email_2', '');
		add_option('meduzean_auto_assign', 'no');
	}

	public function render_list_page() {
		$page = new Ean_List_Page();
		$page->render();
	}

	public function render_import_page() {
		$page = new Ean_Import_Page();
		$page->render();
	}

	public function render_settings_page() {
		$page = new Settings_Page();
		$page->render();
	}
}


