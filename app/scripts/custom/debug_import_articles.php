<?php
/**
 * Script de débogage pour vérifier le mapping et l'import des articles
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Ce script doit être exécuté en CLI.\n");
    exit(1);
}

$dataFile = '/var/www/html/python/import_data/inputs/articles.xls';
$mappingFile = '/var/www/html/python/import_data/outputs_mapping/articles_mapping_dolibarr.xlsx';

require_once __DIR__ . '/vendor/autoload.php';

echo "=================================================================\n";
echo "DEBUG: Vérification du mapping et des données\n";
echo "=================================================================\n\n";

// Charger le mapping
$mappingSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($mappingFile);
$mappingSheet = $mappingSpreadsheet->getActiveSheet();

$mapping = [];
$highestMapRow = (int) $mappingSheet->getHighestRow();
for ($row = 3; $row <= $highestMapRow; $row++) {
    $sourceLabel = trim((string) $mappingSheet->getCell('B' . $row)->getValue());
    $tableName = trim((string) $mappingSheet->getCell('D' . $row)->getValue());
    $fieldName = trim((string) $mappingSheet->getCell('E' . $row)->getValue());
    if (!$sourceLabel || !$tableName || !$fieldName) {
        continue;
    }
    $normalizedLabel = normalizeLabel($sourceLabel);
    $mapping[$normalizedLabel] = [
        'table' => strtolower($tableName),
        'field' => strtolower($fieldName),
        'source' => $sourceLabel,
    ];
}

echo "Nombre de colonnes mappées : " . count($mapping) . "\n\n";

// Charger les données
$dataReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($dataFile);
if (method_exists($dataReader, 'setReadDataOnly')) {
    $dataReader->setReadDataOnly(true);
}
$dataSpreadsheet = $dataReader->load($dataFile);
$dataSheet = $dataSpreadsheet->getActiveSheet();

$headerRow = 1;
$highestColumn = $dataSheet->getHighestColumn();
$headers = [];
foreach ($dataSheet->rangeToArray('A' . $headerRow . ':' . $highestColumn . $headerRow, null, true, false) as $row) {
    foreach ($row as $idx => $value) {
        $headers[$idx] = trim((string) $value);
    }
}

echo "=================================================================\n";
echo "DEBUG: Analyse de la première ligne de données (ligne 2)\n";
echo "=================================================================\n\n";

$row = 2; // Première ligne de données
$rowValues = $dataSheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, false);
$rowData = $rowValues[0];

$productFields = [];
$extraFields = [];

foreach ($rowData as $idx => $value) {
    $header = isset($headers[$idx]) ? (string) $headers[$idx] : '';
    $normalizedHeader = normalizeLabel($header);

    if ($normalizedHeader === '' || !isset($mapping[$normalizedHeader])) {
        continue;
    }

    $map = $mapping[$normalizedHeader];
    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1) . $row;
    $cell = $dataSheet->getCell($cellCoordinate);
    $formattedValue = formatCellValue($value, $cell);

    // Afficher les champs importants
    if (in_array($map['table'], ['llx_product']) &&
        in_array($map['field'], ['ref', 'label', 'price_ttc', 'tva_tx', 'pmp', 'cost_price', 'weight', 'stock'])) {

        echo sprintf("%-30s | Table: %-20s | Champ: %-20s | Valeur brute: %-15s | Valeur formatée: %s\n",
            $header,
            $map['table'],
            $map['field'],
            var_export($value, true),
            var_export($formattedValue, true)
        );

        if ($map['table'] === 'llx_product') {
            $productFields[$map['field']] = $formattedValue;
        }
    }
}

echo "\n=================================================================\n";
echo "RÉSUMÉ DES CHAMPS PRODUCT À IMPORTER\n";
echo "=================================================================\n\n";

foreach ($productFields as $field => $value) {
    $isEmpty = ($value === '' || $value === null);
    $status = $isEmpty ? "❌ VIDE - SERA IGNORÉ" : "✅ OK";
    echo sprintf("%-25s : %-20s %s\n", $field, var_export($value, true), $status);
}

function normalizeLabel($value): string
{
    $value = (string) $value;
    $value = str_replace(["\xC2\xA0", "\t", "\r", "\n"], ' ', $value);
    $value = str_replace(['_', '-'], ' ', $value);
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (function_exists('mb_strtolower')) {
        $value = mb_strtolower($value, 'UTF-8');
    } else {
        $value = strtolower($value);
    }
    return preg_replace('/\s+/', ' ', $value);
}

function formatCellValue($rawValue, ?\PhpOffice\PhpSpreadsheet\Cell\Cell $cell = null)
{
    if ($rawValue === null) {
        return '';
    }

    if ($cell && is_numeric($rawValue) && \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
        $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawValue);
        return $date ? $date->format('Y-m-d H:i:s') : '';
    }

    if (is_float($rawValue)) {
        return (string) $rawValue;
    }

    if (is_bool($rawValue)) {
        return $rawValue ? '1' : '0';
    }

    return trim((string) $rawValue);
}
