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
	
	public function enqueue_menu_icon_styles() {
		// Charger le CSS pour l'icône du menu sur toutes les pages admin
		wp_add_inline_style('wp-admin', '
			#adminmenu .toplevel_page_meduzean-ean .wp-menu-image img {
				width: 20px;
				height: 20px;
				padding: 0;
			}
			#adminmenu .toplevel_page_meduzean-ean .wp-menu-image {
				background-size: 20px 20px;
				background-position: center;
				background-repeat: no-repeat;
			}
		');
	}
}


