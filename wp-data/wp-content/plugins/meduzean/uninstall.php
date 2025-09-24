<?php
/**
 * Uninstall cleanup for Meduzean EAN Manager
 */

// Exit if accessed directly or if not uninstall context
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Ensure constants if needed
if ( ! defined( 'MEDUZEAN_DB_VERSION_OPTION' ) ) {
	define( 'MEDUZEAN_DB_VERSION_OPTION', 'meduzean_db_version' );
}

// Clear scheduled cron event
if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
	wp_clear_scheduled_hook( 'meduzean_ean_manager_daily_check' );
}

// Drop custom table safely
global $wpdb;
$table_name = $wpdb->prefix . 'ean_codes';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// Delete options
delete_option( MEDUZEAN_DB_VERSION_OPTION );
delete_option( 'meduzean_low_stock_threshold' );
delete_option( 'meduzean_notification_email' );
delete_option( 'meduzean_notification_email_2' );
delete_option( 'meduzean_auto_assign' );


