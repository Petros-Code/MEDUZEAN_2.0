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
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'ean_add_date';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

        // Récupération des données
        $eans = $this->table->getAll($per_page, $offset, $orderby, $order, $availability);
        $total = $this->table->countAll($availability);
        $total_all_eans = $this->table->countAll(); // Total sans filtre

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
                        
                        <!-- Filtre par statut -->
                        <select name="availability">
                            <option value=""><?php _e('Tous les EAN', 'meduzean'); ?></option>
                            <option value="available" <?php selected($availability, 'available'); ?>><?php _e('Disponibles', 'meduzean'); ?></option>
                            <option value="used" <?php selected($availability, 'used'); ?>><?php _e('Utilisés', 'meduzean'); ?></option>
                        </select>
                        
                        <!-- Tri par -->
                        <select name="orderby">
                            <option value="ean_add_date" <?php selected($orderby, 'ean_add_date'); ?>><?php _e('Date d\'ajout', 'meduzean'); ?></option>
                            <option value="association_date" <?php selected($orderby, 'association_date'); ?>><?php _e('Date d\'association', 'meduzean'); ?></option>
                        </select>
                        
                        <!-- Direction du tri -->
                        <select name="order">
                            <option value="DESC" <?php selected($order, 'DESC'); ?>><?php _e('↓ Décroissant', 'meduzean'); ?></option>
                            <option value="ASC" <?php selected($order, 'ASC'); ?>><?php _e('↑ Croissant', 'meduzean'); ?></option>
                        </select>
                        
                        <input type="submit" class="button" value="<?php _e('Appliquer', 'meduzean'); ?>">
                    </form>
                </div>
                
                <!-- Statistiques -->
                <div class="alignright actions">
                    <span class="displaying-num">
                        <?php
                        if ($availability === 'available') {
                            printf(__('%d EAN disponibles sur %d total', 'meduzean'), $total, $total_all_eans);
                        } elseif ($availability === 'used') {
                            printf(__('%d EAN utilisés sur %d total', 'meduzean'), $total, $total_all_eans);
                        } else {
                            printf(__('%d EAN au total', 'meduzean'), $total_all_eans);
                        }
                        ?>
                    </span>
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
                        <th scope="col" class="manage-column <?php echo $orderby === 'ean_add_date' ? 'sorted ' . strtolower($order) : 'sortable desc'; ?>">
                            <a href="<?php echo $this->get_sort_url('ean_add_date', $orderby, $order, $availability); ?>">
                                <?php _e('Date d\'ajout', 'meduzean'); ?>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column"><?php _e('Produit associé', 'meduzean'); ?></th>
                        <th scope="col" class="manage-column <?php echo $orderby === 'association_date' ? 'sorted ' . strtolower($order) : 'sortable desc'; ?>">
                            <a href="<?php echo $this->get_sort_url('association_date', $orderby, $order, $availability); ?>">
                                <?php _e('Date d\'association', 'meduzean'); ?>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                                </span>
                            </a>
                        </th>
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
                                       class="button button-small">
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
                    <div class="alignleft actions">
                        <span class="displaying-num">
                            <?php
                            $start = ($page - 1) * $per_page + 1;
                            $end = min($page * $per_page, $total);
                            if ($availability === 'available') {
                                printf(__('Affichage de %1$d à %2$d sur %3$d EAN disponibles (%4$d total)', 'meduzean'), $start, $end, $total, $total_all_eans);
                            } elseif ($availability === 'used') {
                                printf(__('Affichage de %1$d à %2$d sur %3$d EAN utilisés (%4$d total)', 'meduzean'), $start, $end, $total, $total_all_eans);
                            } else {
                                printf(__('Affichage de %1$d à %2$d sur %3$d EAN', 'meduzean'), $start, $end, $total);
                            }
                            ?>
                        </span>
                    </div>
                    <div class="tablenav-pages">
                        <?php
                        $pagination_args = [
                            'base' => add_query_arg([
                                'availability' => $availability,
                                'orderby' => $orderby,
                                'order' => $order,
                                'paged' => '%#%'
                            ]),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => ceil($total / $per_page),
                            'current' => $page
                        ];
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Statistiques sans pagination -->
                <div class="tablenav bottom">
                    <div class="alignleft actions">
                        <span class="displaying-num">
                            <?php
                            if ($total > 0) {
                                if ($availability === 'available') {
                                    printf(__('%d EAN disponibles sur %d total', 'meduzean'), $total, $total_all_eans);
                                } elseif ($availability === 'used') {
                                    printf(__('%d EAN utilisés sur %d total', 'meduzean'), $total, $total_all_eans);
                                } else {
                                    printf(__('%d EAN au total', 'meduzean'), $total_all_eans);
                                }
                            }
                            ?>
                        </span>
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
            if ($this->table->deleteById($ean_id)) {
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

    /**
     * Génère l'URL pour le tri des colonnes
     */
    private function get_sort_url($column, $current_orderby, $current_order, $availability) {
        // Si on clique sur la même colonne, inverser l'ordre
        if ($current_orderby === $column) {
            $new_order = ($current_order === 'ASC') ? 'DESC' : 'ASC';
        } else {
            // Par défaut, trier par date décroissant
            $new_order = 'DESC';
        }

        $args = [
            'page' => 'meduzean-ean',
            'orderby' => $column,
            'order' => $new_order
        ];

        // Préserver le filtre de disponibilité s'il existe
        if (!empty($availability)) {
            $args['availability'] = $availability;
        }

        return admin_url('admin.php?' . http_build_query($args));
    }
}
