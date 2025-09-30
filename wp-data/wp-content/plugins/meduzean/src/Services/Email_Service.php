<?php
namespace Meduzean\EanManager\Services;

use Meduzean\EanManager\Interfaces\EmailServiceInterface;

defined('ABSPATH') || exit;

class Email_Service implements EmailServiceInterface
{
	public function sendLowStockAlert(int $availableCount, int $threshold): bool
	{
		$email = get_option('meduzean_notification_email', get_option('admin_email'));
		$email2 = get_option('meduzean_notification_email_2', '');
		
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

		// Envoyer au premier email
		$result1 = wp_mail($email, $subject, $message, $headers);
		
		// Envoyer au deuxième email si configuré et valide
		$result2 = true;
		if (!empty($email2) && is_email($email2)) {
			$result2 = wp_mail($email2, $subject, $message, $headers);
		}

		return $result1 && $result2;
	}
}


