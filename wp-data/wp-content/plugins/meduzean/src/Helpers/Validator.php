<?php
namespace Meduzean\EanManager\Helpers;

defined('ABSPATH') || exit;

class Validator {
	public static function is_valid_ean13( $ean ) {
		$ean = preg_replace( '/\D/', '', (string) $ean );
		if ( strlen( $ean ) !== 13 ) {
			return false;
		}
		$sum = 0;
		for ( $i = 0; $i < 12; $i++ ) {
			$digit = (int) $ean[$i];
			$sum += ( $i % 2 === 0 ) ? $digit : $digit * 3;
		}
		$check = (10 - ($sum % 10)) % 10;
		return $check === (int) $ean[12];
	}
}


