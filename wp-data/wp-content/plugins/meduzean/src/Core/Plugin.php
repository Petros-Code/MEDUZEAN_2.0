<?php
namespace Meduzean\EanManager\Core;

use Meduzean\EanManager\Admin\Admin;
use Meduzean\EanManager\Admin\Assets;
use Meduzean\EanManager\API\Rest_Controller;
use Meduzean\EanManager\Cron\Cron_Handler;
use Meduzean\EanManager\DB\Ean_Table;
use Meduzean\EanManager\Services\Ean_Service;
use Meduzean\EanManager\Services\Email_Service;

defined( 'ABSPATH' ) || exit;

class Plugin {
    /**
     * @var Plugin
     */
    private static $instance;

    /** @var Admin */
    public $admin;

    /** @var Assets */
    public $assets;

    /** @var Rest_Controller */
    public $rest;

    /** @var Cron_Handler */
    public $cron;

    /** @var Ean_Table */
    public $table;

    /** @var Ean_Service */
    public $service;

    /** @var Email_Service */
    public $email;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // initialize components
        $this->table = new Ean_Table();
        $this->email = new Email_Service();
        $this->service = new Ean_Service( $this->table, $this->email );
        $this->cron = new Cron_Handler( $this->service );
        $this->admin = new Admin( $this->service );
        $this->assets = new Assets();
        $this->rest = new Rest_Controller( $this->service );
    }

    public function register_hooks() {
        // register admin hooks
        add_action( 'admin_menu', [ $this->admin, 'register_menus' ] );
        add_action( 'admin_init', [ $this->admin, 'register_settings' ] );

        // assets
        add_action( 'admin_enqueue_scripts', [ $this->assets, 'enqueue_admin_assets' ] );

        // rest
        add_action( 'rest_api_init', [ $this->rest, 'register_routes' ] );

        // cron action hook (called by wp-cron)
        add_action( 'meduzean_ean_manager_daily_check', [ $this->cron, 'daily_check' ] );
    }
}
