<?php
namespace Meduzean\EanManager\Admin\Pages;

use Meduzean\EanManager\DB\Ean_Table;
use Meduzean\EanManager\Helpers\Validator;

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
                <p><?php _e('Vous pouvez importer des codes EAN via un fichier CSV. Le fichier doit contenir une colonne "ean" avec les codes EAN (13 chiffres).', 'meduzean'); ?></p>
                <p><strong><?php _e('Format CSV attendu:', 'meduzean'); ?></strong></p>
                <pre>ean
1234567890123
9876543210987</pre>
            </div>

            <div class="card">
                <h2><?php _e('Importer un fichier CSV', 'meduzean'); ?></h2>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('meduzean_import_ean', 'meduzean_import_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ean_file"><?php _e('Fichier CSV', 'meduzean'); ?></label>
                            </th>
                            <td>
                                <input type="file" id="ean_file" name="ean_file" accept=".csv" required>
                                <p class="description"><?php _e('Sélectionnez un fichier CSV contenant les codes EAN.', 'meduzean'); ?></p>
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
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Erreur lors de l\'upload du fichier.', 'meduzean') . '</p></div>';
            });
            return;
        }

        $file = $_FILES['ean_file'];
        $file_type = wp_check_filetype($file['name'], ['csv' => 'text/csv']);
        
        if (!$file_type['ext']) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Le fichier doit être un CSV.', 'meduzean') . '</p></div>';
            });
            return;
        }

        $csv_data = array_map('str_getcsv', file($file['tmp_name']));
        if (empty($csv_data) || count($csv_data) < 2) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Le fichier CSV est vide ou invalide.', 'meduzean') . '</p></div>';
            });
            return;
        }

        // Vérifier l'en-tête
        $headers = array_map('strtolower', $csv_data[0]);
        $ean_column_index = array_search('ean', $headers);
        
        if ($ean_column_index === false) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Le fichier CSV doit contenir une colonne "ean".', 'meduzean') . '</p></div>';
            });
            return;
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

        if (!empty($errors)) {
            add_action('admin_notices', function() use ($errors) {
                echo '<div class="notice notice-warning is-dismissible"><p>' . implode('<br>', $errors) . '</p></div>';
            });
        }

        if (!empty($eans)) {
            $result = $this->import_eans($eans);
            $imported = $result['imported'];
            $errors = $result['errors'];
            
            if (!empty($errors)) {
                add_action('admin_notices', function() use ($errors) {
                    echo '<div class="notice notice-warning is-dismissible"><p>' . implode('<br>', $errors) . '</p></div>';
                });
            }
            
            if ($imported > 0) {
                add_action('admin_notices', function() use ($imported) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('%d codes EAN importés avec succès.', 'meduzean'), $imported) . '</p></div>';
                });
            }
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
            // Nettoyer le code EAN
            $ean = preg_replace('/\D/', '', $ean); // Garder seulement les chiffres
            
            if (empty($ean)) {
                continue; // Ignorer les lignes vides
            }
            
            if (!Validator::is_valid_ean13($ean)) {
                $invalid[] = $ean;
                continue;
            }
            
            if ($this->table->ean_exists($ean)) {
                $duplicates[] = $ean;
                continue;
            }
            
            if ($this->table->insert_ean($ean)) {
                $imported++;
            } else {
                $errors[] = sprintf(__('Erreur lors de l\'insertion: %s', 'meduzean'), $ean);
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
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Veuillez entrer au moins un code EAN.', 'meduzean') . '</p></div>';
                });
                return;
            }

            $manual_eans = explode("\n", $_POST['manual_eans']);
            $eans = array_filter(array_map('trim', $manual_eans));

            if (empty($eans)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Aucun code EAN valide trouvé.', 'meduzean') . '</p></div>';
                });
                return;
            }

            // Log pour debug
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Meduzean Debug] Processing ' . count($eans) . ' EAN codes: ' . implode(', ', $eans));
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

        // Afficher les erreurs (doublons, codes invalides)
        if (!empty($errors)) {
            add_action('admin_notices', function() use ($errors) {
                echo '<div class="notice notice-warning is-dismissible"><p><strong>' . __('Erreurs détectées:', 'meduzean') . '</strong><br>' . implode('<br>', $errors) . '</p></div>';
            });
        }

        // Afficher le résumé détaillé
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
            add_action('admin_notices', function() use ($summary_parts, $imported) {
                $class = ($imported > 0) ? 'notice-success' : 'notice-info';
                echo '<div class="notice ' . $class . ' is-dismissible"><p><strong>' . __('Résumé:', 'meduzean') . '</strong> ' . implode(' • ', $summary_parts) . '</p></div>';
            });
        }

            // Si aucun EAN n'a été importé et qu'il n'y a pas d'erreurs, c'est que tous étaient des doublons
            if ($imported === 0 && empty($errors) && $duplicate_count === 0 && $invalid_count === 0) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-info is-dismissible"><p>' . __('Aucun code EAN valide trouvé dans la saisie.', 'meduzean') . '</p></div>';
                });
            }

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
}
