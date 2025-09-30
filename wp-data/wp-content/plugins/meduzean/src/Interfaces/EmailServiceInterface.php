<?php
namespace Meduzean\EanManager\Interfaces;

defined('ABSPATH') || exit;

interface EmailServiceInterface
{
    public function sendLowStockAlert(int $available, int $threshold): bool;
}
