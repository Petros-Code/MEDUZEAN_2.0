<?php
namespace Meduzean\EanManager\Core;

use Meduzean\EanManager\DB\Ean_Table;
use Meduzean\EanManager\Interfaces\RepositoryInterface;

defined('ABSPATH') || exit;

class Bootstrap
{
    private RepositoryInterface $repository;
    
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
    
    public function activate(): void
    {
        $this->repository->createOrUpdateTable();
        $this->scheduleCron();
    }
    
    public function deactivate(): void
    {
        $this->clearScheduledCron();
    }
    
    private function scheduleCron(): void
    {
        if (!wp_next_scheduled('meduzean_ean_manager_daily_check')) {
            wp_schedule_event(time(), 'daily', 'meduzean_ean_manager_daily_check');
        }
    }
    
    private function clearScheduledCron(): void
    {
        wp_clear_scheduled_hook('meduzean_ean_manager_daily_check');
    }
}
