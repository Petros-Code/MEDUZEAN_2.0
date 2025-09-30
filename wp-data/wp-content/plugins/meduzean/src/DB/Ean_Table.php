<?php
namespace Meduzean\EanManager\DB;

defined('ABSPATH') || exit;

class Ean_Table {
    protected $table_name;
    protected $charset_collate;

    public function __construct() {
        global $wpdb;
        $this->table_name     = $wpdb->prefix . 'ean_codes';
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    public function get_table_name() {
        return $this->table_name;
    }

    public function create_or_update_table() {
        global $wpdb;

        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ean varchar(13) NOT NULL,
            ean_add_date datetime DEFAULT CURRENT_TIMESTAMP,
            product_id bigint(20) unsigned DEFAULT NULL,
            association_date datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY ean (ean),
            KEY product_id (product_id)
        ) {$this->charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        update_option(MEDUZEAN_DB_VERSION_OPTION, MEDUZEAN_VERSION);
    }

    public function insert_ean($ean) {
        global $wpdb;
        $res = $wpdb->insert($this->table_name, ['ean' => $ean], ['%s']);
        return $res === false ? false : (int) $wpdb->insert_id;
    }

    public function ean_exists($ean) {
        global $wpdb;
        $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$this->table_name} WHERE ean = %s LIMIT 1", $ean));
        return $id ? intval($id) : false;
    }

    public function get_all($limit = 20, $offset = 0, $orderby = 'ean_add_date', $order = 'DESC', $availability = '') {
        global $wpdb;

        $allowed_orderby = ['ean_add_date', 'association_date'];
        $orderby = in_array($orderby, $allowed_orderby, true) ? $orderby : 'ean_add_date';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $where = '1=1';
        if ($availability === 'available') {
            $where .= ' AND product_id IS NULL';
        } elseif ($availability === 'used') {
            $where .= ' AND product_id IS NOT NULL';
        }

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
            $limit,
            $offset
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function count_all($availability = '') {
        global $wpdb;

        $where = '1=1';
        if ($availability === 'available') {
            $where .= ' AND product_id IS NULL';
        } elseif ($availability === 'used') {
            $where .= ' AND product_id IS NOT NULL';
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE {$where}");
    }

    public function delete_by_id($id) {
        global $wpdb;
        $res = $wpdb->delete($this->table_name, ['id' => intval($id)], ['%d']);
        return $res === false ? false : ($res > 0);
    }

    public function drop_table() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
    }
}
