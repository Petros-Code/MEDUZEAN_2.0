<?php
namespace Meduzean\EanManager\Admin\Pages;

use Meduzean\EanManager\DB\Ean_Table;
use Meduzean\EanManager\Helpers\Validator;
use Meduzean\EanManager\Helpers\SimpleXLSX;

defined('ABSPATH') || exit;

class Ean_Import_Page {
    /** @var Ean_Table */
    private $table;

    public function __construct() {
        $this->table = new Ean_Table();
    }

    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Accès refusé', 'meduzean'));
        }

        // Gestion de l'upload
        $this->handle_upload();
        
        // Gestion des EAN manuels
        $this->handle_manual_eans();

        ?>
        <div class="wrap">
            <h1><?php _e('Importer des codes EAN', 'meduzean'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Instructions', 'meduzean'); ?></h2>
                <p><?php _e('Vous pouvez importer des codes EAN via un fichier CSV ou XLSX.', 'meduzean'); ?></p>
                <p><strong><?php _e('Format CSV:', 'meduzean'); ?></strong> <?php _e('Le fichier doit contenir une colonne "ean" avec les codes EAN (13 chiffres).', 'meduzean'); ?></p>
                <p><strong><?php _e('Format XLSX:', 'meduzean'); ?></strong> <?php _e('Les codes EAN doivent être dans la première colonne (A), à partir de la ligne 3.', 'meduzean'); ?></p>
                <p><strong><?php _e('Format CSV attendu:', 'meduzean'); ?></strong></p>
                <pre>ean
1234567890123
9876543210987</pre>
            </div>

            <div class="card">
                <h2><?php _e('Importer un fichier CSV ou XLSX', 'meduzean'); ?></h2>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('meduzean_import_ean', 'meduzean_import_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ean_file"><?php _e('Fichier CSV ou XLSX', 'meduzean'); ?></label>
                            </th>
                            <td>
                                <input type="file" id="ean_file" name="ean_file" accept=".csv,.xlsx" required>
                                <p class="description"><?php _e('Sélectionnez un fichier CSV ou XLSX contenant les codes EAN.', 'meduzean'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Importer les codes EAN', 'meduzean')); ?>
                </form>
            </div>

            <div class="card">
                <h2><?php _e('Ajouter des codes EAN manuellement', 'meduzean'); ?></h2>
                <form method="post" id="manual-ean-form">
                    <?php wp_nonce_field('meduzean_add_manual_ean', 'meduzean_manual_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="manual_eans"><?php _e('Codes EAN', 'meduzean'); ?></label>
                            </th>
                            <td>
                                <textarea id="manual_eans" name="manual_eans" rows="5" cols="50" placeholder="<?php _e('Un code EAN par ligne', 'meduzean'); ?>"></textarea>
                                <p class="description"><?php _e('Entrez un code EAN par ligne (13 chiffres).', 'meduzean'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Ajouter les codes EAN', 'meduzean'), 'primary', 'add_manual_eans'); ?>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#manual-ean-form').on('submit', function(e) {
                var eans = $('#manual_eans').val().split('\n').filter(function(ean) {
                    return ean.trim() !== '';
                });
                
                if (eans.length === 0) {
                    alert('<?php _e('Veuillez entrer au moins un code EAN.', 'meduzean'); ?>');
                    e.preventDefault();
                    return false;
                }
                
                // Validation basique côté client
                var invalidEans = [];
                eans.forEach(function(ean, index) {
                    var cleanEan = ean.replace(/\D/g, ''); // Garder seulement les chiffres
                    if (cleanEan.length !== 13) {
                        invalidEans.push('Ligne ' + (index + 1) + ': ' + ean);
                    }
                });
                
                if (invalidEans.length > 0) {
                    if (confirm('<?php _e('Certains codes EAN semblent invalides:', 'meduzean'); ?>\n\n' + invalidEans.join('\n') + '\n\n<?php _e('Voulez-vous continuer quand même?', 'meduzean'); ?>')) {
                        return true;
                    } else {
                        e.preventDefault();
                        return false;
                    }
                }
            });
            
            // Validation en temps réel
            $('#manual_eans').on('input', function() {
                var eans = $(this).val().split('\n');
                var validCount = 0;
                var invalidCount = 0;
                
                eans.forEach(function(ean) {
                    var cleanEan = ean.replace(/\D/g, '');
                    if (cleanEan.length === 13) {
                        validCount++;
                    } else if (cleanEan.length > 0) {
                        invalidCount++;
                    }
                });
                
                // Mettre à jour l'aide
                var helpText = '<?php _e('Entrez un code EAN par ligne (13 chiffres).', 'meduzean'); ?>';
                if (validCount > 0 || invalidCount > 0) {
                    helpText += ' <strong>' + validCount + ' <?php _e('valides', 'meduzean'); ?>';
                    if (invalidCount > 0) {
                        helpText += ', ' + invalidCount + ' <?php _e('invalides', 'meduzean'); ?>';
                    }
                    helpText += '</strong>';
                }
                
                $(this).next('.description').html(helpText);
            });
        });
        </script>
        <?php
    }

    private function handle_upload() {
        if (!isset($_POST['meduzean_import_nonce']) || !wp_verify_nonce($_POST['meduzean_import_nonce'], 'meduzean_import_ean')) {
            return;
        }

        if (!isset($_FILES['ean_file']) || $_FILES['ean_file']['error'] !== UPLOAD_ERR_OK) {
            $this->add_admin_notice('error', __('Erreur lors de l\'upload du fichier.', 'meduzean'));
            return;
        }

        $file = $_FILES['ean_file'];
        $file_type = wp_check_filetype($file['name'], [
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
        
        if (!$file_type['ext'] || !in_array($file_type['ext'], ['csv', 'xlsx'])) {
            $this->add_admin_notice('error', __('Le fichier doit être un CSV ou XLSX.', 'meduzean'));
            return;
        }

        // Traiter selon le type de fichier
        if ($file_type['ext'] === 'xlsx') {
            $eans = $this->process_xlsx_file($file['tmp_name']);
        } else {
            $eans = $this->process_csv_file($file['tmp_name']);
        }
        
        if ($eans === false) {
            return; // L'erreur a déjà été affichée dans la méthode
        }
        
        $errors = $eans['errors'];
        $eans = $eans['eans'];

        if (!empty($errors)) {
            $error_message = '<strong>' . __('Erreurs de validation:', 'meduzean') . '</strong><br>' . implode('<br>', $errors);
            $this->add_admin_notice('warning', $error_message);
        }

        if (!empty($eans)) {
            $result = $this->import_eans($eans);
            $imported = $result['imported'];
            $errors = $result['errors'];
            $duplicate_count = $result['duplicate_count'];
            $invalid_count = $result['invalid_count'];
            
            // Afficher les messages de résultat
            $this->display_import_results($imported, $errors, $duplicate_count, $invalid_count, 'csv');
        }
    }

    private function import_eans($eans) {
        $imported = 0;
        $errors = [];
        $duplicates = [];
        $invalid = [];
        
        // Vérifier que la table est accessible
        if (!$this->table) {
            throw new Exception('Ean_Table not initialized in import_eans');
        }
        
        foreach ($eans as $ean) {
            try {
                // Nettoyer le code EAN
                $ean = preg_replace('/\D/', '', $ean); // Garder seulement les chiffres
                
                if (empty($ean)) {
                    continue; // Ignorer les lignes vides
                }
                
                if (!Validator::is_valid_ean13($ean)) {
                    $invalid[] = $ean;
                    continue;
                }
                
                if ($this->table->eanExists($ean)) {
                    $duplicates[] = $ean;
                    continue;
                }
                
                if ($this->table->insertEan($ean)) {
                    $imported++;
                } else {
                    $errors[] = sprintf(__('Erreur lors de l\'insertion: %s', 'meduzean'), $ean);
                }
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[Meduzean Debug] Exception processing EAN ' . $ean . ': ' . $e->getMessage());
                }
                $errors[] = sprintf(__('Erreur lors du traitement de %s: %s', 'meduzean'), $ean, $e->getMessage());
            }
        }
        
        // Construire les messages d'erreur détaillés
        $error_messages = [];
        
        if (!empty($invalid)) {
            $error_messages[] = sprintf(
                __('%d codes EAN invalides: %s', 'meduzean'), 
                count($invalid), 
                implode(', ', $invalid)
            );
        }
        
        if (!empty($duplicates)) {
            $error_messages[] = sprintf(
                __('%d codes EAN déjà existants: %s', 'meduzean'), 
                count($duplicates), 
                implode(', ', $duplicates)
            );
        }
        
        // Ajouter les autres erreurs
        $error_messages = array_merge($error_messages, $errors);
        
        return [
            'imported' => $imported,
            'errors' => $error_messages,
            'invalid_count' => count($invalid),
            'duplicate_count' => count($duplicates)
        ];
    }

    private function handle_manual_eans() {
        try {
            if (!isset($_POST['meduzean_manual_nonce']) || !wp_verify_nonce($_POST['meduzean_manual_nonce'], 'meduzean_add_manual_ean')) {
                return;
            }

            // Vérifier que le bouton "Ajouter les codes EAN" a été cliqué
            if (!isset($_POST['add_manual_eans'])) {
                return;
            }

            if (!isset($_POST['manual_eans']) || empty(trim($_POST['manual_eans']))) {
                $this->add_admin_notice('error', __('Veuillez entrer au moins un code EAN.', 'meduzean'));
                return;
            }

            $manual_eans = explode("\n", $_POST['manual_eans']);
            $eans = array_filter(array_map('trim', $manual_eans));

            if (empty($eans)) {
                $this->add_admin_notice('error', __('Aucun code EAN valide trouvé.', 'meduzean'));
                return;
            }

            // Vérifier que la table existe
            if (!$this->table) {
                throw new Exception('Ean_Table not initialized');
            }

            $result = $this->import_eans($eans);
            $imported = $result['imported'];
            $errors = $result['errors'];
            $duplicate_count = $result['duplicate_count'];
            $invalid_count = $result['invalid_count'];

            // Afficher les messages de résultat
            $this->display_import_results($imported, $errors, $duplicate_count, $invalid_count, 'manuel');

        } catch (Exception $e) {
            // Log l'erreur pour debug
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Meduzean Error] Exception in handle_manual_eans: ' . $e->getMessage());
            }
            
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error is-dismissible"><p><strong>' . __('Erreur critique:', 'meduzean') . '</strong> ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }

    /**
     * Ajoute un message d'administration qui s'affiche immédiatement
     */
    private function add_admin_notice($type, $message, $is_dismissible = true) {
        $class = 'notice-' . $type;
        if ($is_dismissible) {
            $class .= ' is-dismissible';
        }
        
        echo '<div class="notice ' . $class . '">';
        echo '<p>' . $message . '</p>';
        echo '</div>';
    }

    /**
     * Affiche les messages de résultat d'import de manière cohérente
     */
    private function display_import_results($imported, $errors, $duplicate_count, $invalid_count, $type = 'manuel') {
        $type_label = ($type === 'csv') ? __('import CSV', 'meduzean') : __('saisie manuelle', 'meduzean');
        
        // Message principal de succès/échec
        if ($imported > 0) {
            $message = '<span class="dashicons dashicons-yes-alt" style="color: #00a32a; margin-right: 5px;"></span>';
            $message .= '<strong>' . sprintf(__('Succès ! %d codes EAN ajoutés via %s.', 'meduzean'), $imported, $type_label) . '</strong>';
            $this->add_admin_notice('success', $message);
        } else {
            $message = '<span class="dashicons dashicons-warning" style="color: #dba617; margin-right: 5px;"></span>';
            $message .= '<strong>' . sprintf(__('Aucun code EAN ajouté via %s.', 'meduzean'), $type_label) . '</strong>';
            $this->add_admin_notice('warning', $message);
        }

        // Détails des erreurs si il y en a
        if (!empty($errors)) {
            $error_message = '<strong>' . __('Détails des erreurs:', 'meduzean') . '</strong><br>';
            $error_message .= '<ul style="margin-left: 20px;">';
            foreach ($errors as $error) {
                $error_message .= '<li>' . esc_html($error) . '</li>';
            }
            $error_message .= '</ul>';
            $this->add_admin_notice('warning', $error_message);
        }

        // Résumé détaillé
        $summary_parts = [];
        if ($imported > 0) {
            $summary_parts[] = sprintf(__('%d codes EAN ajoutés avec succès', 'meduzean'), $imported);
        }
        if ($duplicate_count > 0) {
            $summary_parts[] = sprintf(__('%d codes EAN déjà existants (ignorés)', 'meduzean'), $duplicate_count);
        }
        if ($invalid_count > 0) {
            $summary_parts[] = sprintf(__('%d codes EAN invalides (ignorés)', 'meduzean'), $invalid_count);
        }

        if (!empty($summary_parts)) {
            $class = ($imported > 0) ? 'info' : 'warning';
            $summary_message = '<span class="dashicons dashicons-info" style="color: #72aee6; margin-right: 5px;"></span>';
            $summary_message .= '<strong>' . __('Résumé:', 'meduzean') . '</strong> ' . implode(' • ', $summary_parts);
            $this->add_admin_notice($class, $summary_message);
        }

        // Message spécial si aucun EAN valide trouvé
        if ($imported === 0 && empty($errors) && $duplicate_count === 0 && $invalid_count === 0) {
            $info_message = '<span class="dashicons dashicons-info" style="color: #72aee6; margin-right: 5px;"></span>';
            $info_message .= __('Aucun code EAN valide trouvé dans la saisie.', 'meduzean');
            $this->add_admin_notice('info', $info_message);
        }
    }

    /**
     * Traite un fichier CSV
     */
    private function process_csv_file($file_path) {
        $csv_data = array_map('str_getcsv', file($file_path));
        if (empty($csv_data) || count($csv_data) < 2) {
            $this->add_admin_notice('error', __('Le fichier CSV est vide ou invalide.', 'meduzean'));
            return false;
        }

        // Vérifier l'en-tête
        $headers = array_map('strtolower', $csv_data[0]);
        $ean_column_index = array_search('ean', $headers);
        
        if ($ean_column_index === false) {
            $this->add_admin_notice('error', __('Le fichier CSV doit contenir une colonne "ean".', 'meduzean'));
            return false;
        }

        // Traiter les données
        $eans = [];
        $errors = [];
        
        for ($i = 1; $i < count($csv_data); $i++) {
            $row = $csv_data[$i];
            if (isset($row[$ean_column_index])) {
                $ean = trim($row[$ean_column_index]);
                if (!empty($ean)) {
                    if (Validator::is_valid_ean13($ean)) {
                        $eans[] = $ean;
                    } else {
                        $errors[] = sprintf(__('Code EAN invalide à la ligne %d: %s', 'meduzean'), $i + 1, $ean);
                    }
                }
            }
        }

        return ['eans' => $eans, 'errors' => $errors];
    }

    /**
     * Traite un fichier XLSX
     */
    private function process_xlsx_file($file_path) {
        $xlsx = SimpleXLSX::parse($file_path);
        
        if (!$xlsx) {
            $this->add_admin_notice('error', __('Impossible de lire le fichier XLSX.', 'meduzean'));
            return false;
        }

        // Extraire les données de la première colonne à partir de la ligne 3
        $raw_eans = $xlsx->getFirstColumnFromRow3();
        
        if (empty($raw_eans)) {
            $this->add_admin_notice('error', __('Aucune donnée trouvée dans la première colonne du fichier XLSX (à partir de la ligne 3).', 'meduzean'));
            return false;
        }

        // Valider les EAN
        $eans = [];
        $errors = [];
        
        foreach ($raw_eans as $index => $ean) {
            $ean = trim($ean);
            if (!empty($ean)) {
                if (Validator::is_valid_ean13($ean)) {
                    $eans[] = $ean;
                } else {
                    $errors[] = sprintf(__('Code EAN invalide à la ligne %d: %s', 'meduzean'), $index + 3, $ean);
                }
            }
        }

        return ['eans' => $eans, 'errors' => $errors];
    }
}
