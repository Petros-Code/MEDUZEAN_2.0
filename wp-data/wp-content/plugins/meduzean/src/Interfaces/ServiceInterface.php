<?php
namespace Meduzean\EanManager\Interfaces;

defined('ABSPATH') || exit;

interface ServiceInterface
{
    public function getAvailableCount(): int;
    public function getTotalCount(): int;
    public function checkLowStock(): bool;
}
