<?php
/**
 * SimpleXLSX - Simple XLSX File Reader
 * Version adaptée pour WordPress
 * Source: github.com/shuchkin/simplexlsx
 */

defined('ABSPATH') || exit;

class SimpleXLSX {
    private $sheets = [];
    private $error = '';

    public static function parse($filename) {
        $xlsx = new self();
        if ($xlsx->parseFile($filename)) {
            return $xlsx;
        }
        return false;
    }

    public function parseFile($filename) {
        if (!file_exists($filename)) {
            $this->error = 'File not found';
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($filename) !== TRUE) {
            $this->error = 'Cannot open XLSX file';
            return false;
        }

        // Lire les strings partagées
        $sharedStrings = [];
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedStringsXML) {
                $xml = simplexml_load_string($sharedStringsXML);
                if ($xml) {
                    foreach ($xml->si as $si) {
                        $sharedStrings[] = (string)$si->t;
                    }
                }
            }
        }

        // Lire la première feuille
        $worksheetXML = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$worksheetXML) {
            $this->error = 'Cannot read worksheet';
            $zip->close();
            return false;
        }

        $zip->close();

        // Parser le XML de la feuille
        $xml = simplexml_load_string($worksheetXML);
        if (!$xml) {
            $this->error = 'Invalid worksheet XML';
            return false;
        }

        $rows = [];
        if (isset($xml->sheetData->row)) {
            foreach ($xml->sheetData->row as $row) {
                $rowData = [];
                if (isset($row->c)) {
                    foreach ($row->c as $cell) {
                        $value = '';
                        if (isset($cell->v)) {
                            if (isset($cell['t']) && (string)$cell['t'] === 's') {
                                // String partagée
                                $index = (int)$cell->v;
                                $value = isset($sharedStrings[$index]) ? $sharedStrings[$index] : '';
                            } else {
                                $value = (string)$cell->v;
                            }
                        }
                        $rowData[] = $value;
                    }
                }
                $rows[] = $rowData;
            }
        }

        $this->sheets[] = $rows;
        return true;
    }

    public function rows($sheetIndex = 0) {
        return isset($this->sheets[$sheetIndex]) ? $this->sheets[$sheetIndex] : [];
    }

    public function error() {
        return $this->error;
    }

    /**
     * Extrait la première colonne à partir de la ligne 3
     * Spécialement pour notre cas d'usage EAN
     */
    public function getFirstColumnFromRow3() {
        $rows = $this->rows();
        $data = [];
        
        // Commencer à partir de la ligne 3 (index 2)
        for ($i = 2; $i < count($rows); $i++) {
            if (isset($rows[$i][0]) && !empty(trim($rows[$i][0]))) {
                $data[] = trim($rows[$i][0]);
            } else {
                // Arrêter à la première cellule vide
                break;
            }
        }
        
        return $data;
    }
}
