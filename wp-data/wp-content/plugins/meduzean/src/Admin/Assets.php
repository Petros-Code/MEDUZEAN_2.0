<?php
namespace Meduzean\EanManager\Admin;

defined('ABSPATH') || exit;

class Assets {
	public function enqueue_admin_assets( $hook_suffix ) {
		// Charger seulement sur les pages du plugin
		if ( strpos( $hook_suffix, 'meduzean-ean' ) === false ) {
			return;
		}
		wp_enqueue_style(
			'meduzean-admin',
			plugins_url( 'assets/css/admin.css', dirname( __DIR__, 1 ) . '/meduzean.php' ),
			[],
			defined('MEDUZEAN_VERSION') ? MEDUZEAN_VERSION : '1.0.0'
		);
		wp_enqueue_script(
			'meduzean-admin',
			plugins_url( 'assets/js/admin.js', dirname( __DIR__, 1 ) . '/meduzean.php' ),
			[ 'jquery' ],
			defined('MEDUZEAN_VERSION') ? MEDUZEAN_VERSION : '1.0.0',
			true
		);
	}
}


