<?php
namespace Meduzean\EanManager\Admin\Pages;

use Meduzean\EanManager\DB\Ean_Table;
use Meduzean\EanManager\Helpers\Validator;

defined('ABSPATH') || exit;

class Ean_List_Page {
    /** @var Ean_Table */
    private $table;

    public function __construct() {
        $this->table = new Ean_Table();
    }

    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Accès refusé', 'meduzean'));
        }

        // Gestion des actions
        $this->handle_actions();

        // Récupération des paramètres
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        $availability = isset($_GET['availability']) ? sanitize_text_field($_GET['availability']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Récupération des données
        $eans = $this->table->get_all($per_page, $offset, 'ean_add_date', 'DESC', $availability);
        $total = $this->table->count_all($availability);

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Codes EAN', 'meduzean'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=meduzean-ean-import'); ?>" class="page-title-action">
                <?php _e('Importer EAN', 'meduzean'); ?>
            </a>
            <hr class="wp-header-end">

            <!-- Filtres -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get" style="display: inline-block;">
                        <input type="hidden" name="page" value="meduzean-ean">
                        <select name="availability">
                            <option value=""><?php _e('Tous les EAN', 'meduzean'); ?></option>
                            <option value="available" <?php selected($availability, 'available'); ?>><?php _e('Disponibles', 'meduzean'); ?></option>
                            <option value="used" <?php selected($availability, 'used'); ?>><?php _e('Utilisés', 'meduzean'); ?></option>
                        </select>
                        <input type="submit" class="button" value="<?php _e('Filtrer', 'meduzean'); ?>">
                    </form>
                </div>
            </div>

            <!-- Tableau des EAN -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </th>
                        <th scope="col" class="manage-column"><?php _e('EAN', 'meduzean'); ?></th>
                        <th scope="col" class="manage-column"><?php _e('Date d\'ajout', 'meduzean'); ?></th>
                        <th scope="col" class="manage-column"><?php _e('Produit associé', 'meduzean'); ?></th>
                        <th scope="col" class="manage-column"><?php _e('Date d\'association', 'meduzean'); ?></th>
                        <th scope="col" class="manage-column"><?php _e('Actions', 'meduzean'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eans)): ?>
                        <tr>
                            <td colspan="6" class="no-items"><?php _e('Aucun code EAN trouvé.', 'meduzean'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($eans as $ean): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="ean_ids[]" value="<?php echo esc_attr($ean['id']); ?>">
                                </th>
                                <td><code><?php echo esc_html($ean['ean']); ?></code></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ean['ean_add_date']))); ?></td>
                                <td>
                                    <?php if ($ean['product_id']): ?>
                                        <a href="<?php echo get_edit_post_link($ean['product_id']); ?>" target="_blank">
                                            <?php echo esc_html(get_the_title($ean['product_id'])); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="description"><?php _e('Non associé', 'meduzean'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($ean['association_date']): ?>
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ean['association_date']))); ?>
                                    <?php else: ?>
                                        <span class="description">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=meduzean-ean&action=delete&ean_id=' . $ean['id']), 'delete_ean_' . $ean['id']); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce code EAN ?', 'meduzean'); ?>')">
                                        <?php _e('Supprimer', 'meduzean'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total > $per_page): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        $pagination = paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => ceil($total / $per_page),
                            'current' => $page
                        ]);
                        echo $pagination;
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#cb-select-all-1').on('change', function() {
                $('input[name="ean_ids[]"]').prop('checked', this.checked);
            });
        });
        </script>
        <?php
    }

    private function handle_actions() {
        if (!isset($_GET['action']) || !isset($_GET['ean_id'])) {
            return;
        }

        $action = sanitize_text_field($_GET['action']);
        $ean_id = intval($_GET['ean_id']);

        if ($action === 'delete' && wp_verify_nonce($_GET['_wpnonce'], 'delete_ean_' . $ean_id)) {
            if ($this->table->delete_by_id($ean_id)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Code EAN supprimé avec succès.', 'meduzean') . '</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Erreur lors de la suppression du code EAN.', 'meduzean') . '</p></div>';
                });
            }
        }
    }
}
