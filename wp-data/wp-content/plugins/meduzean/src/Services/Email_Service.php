<?php
namespace Meduzean\EanManager\Services;

defined('ABSPATH') || exit;

class Email_Service {
	public function send_low_stock_alert( $availableCount, $threshold ) {
		$email = get_option('meduzean_notification_email', get_option('admin_email'));
		
		if (!is_email($email)) {
			return false;
		}

		$subject = sprintf(__('[%s] Alerte: Stock EAN bas', 'meduzean'), get_bloginfo('name'));
		
		$message = sprintf(
			__('Bonjour,

Le stock de codes EAN disponibles est maintenant en dessous du seuil configuré.

Codes EAN disponibles: %d
Seuil d\'alerte: %d

Veuillez importer de nouveaux codes EAN pour éviter les ruptures de stock.

Cordialement,
Le système EAN Manager', 'meduzean'),
			$availableCount,
			$threshold
		);

		$headers = [
			'Content-Type: text/plain; charset=UTF-8',
			'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
		];

		return wp_mail($email, $subject, $message, $headers);
	}
}


