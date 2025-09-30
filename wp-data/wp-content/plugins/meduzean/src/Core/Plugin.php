<?php
namespace Meduzean\EanManager\Core;

use Meduzean\EanManager\Admin\Admin;
use Meduzean\EanManager\Admin\Assets;
use Meduzean\EanManager\Admin\Notice_Manager;
use Meduzean\EanManager\API\Rest_Controller;
use Meduzean\EanManager\Cron\Cron_Handler;
use Meduzean\EanManager\DB\Ean_Table;
use Meduzean\EanManager\Services\Ean_Service;
use Meduzean\EanManager\Services\Email_Service;

defined('ABSPATH') || exit;

class Plugin {
    private static $instance;
    private $table;
    private $service;
    private $admin;
    private $assets;
    private $rest;
    private $cron;
    private $notice_manager;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->table = new Ean_Table();
        $this->service = new Ean_Service($this->table, new Email_Service());
        $this->admin = new Admin($this->service);
        $this->assets = new Assets();
        $this->rest = new Rest_Controller($this->service);
        $this->cron = new Cron_Handler($this->service);
        $this->notice_manager = new Notice_Manager();
    }

    public function register_hooks() {
        add_action('admin_menu', [$this->admin, 'register_menus']);
        add_action('admin_init', [$this->admin, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this->assets, 'enqueue_admin_assets']);
        add_action('admin_enqueue_scripts', [$this->assets, 'enqueue_menu_icon_styles']);
        add_action('rest_api_init', [$this->rest, 'register_routes']);
        add_action('meduzean_ean_manager_daily_check', [$this->cron, 'daily_check']);
    }
}
