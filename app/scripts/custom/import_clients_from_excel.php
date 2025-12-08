<?php
/**
 * Script CLI pour importer des clients Dolibarr à partir d'un fichier Excel.
 *
 * Usage :
 *   php import_clients_from_excel.php /chemin/vers/clients.xlsx /chemin/vers/clients_mapping_dolibarr.xlsx
 *
 * Le script lit le classeur de données (premier argument) et utilise le classeur de
 * mapping (deuxième argument) pour déterminer à quelle colonne Dolibarr correspond
 * chaque colonne Excel.
 *
 * Pré-requis :
 *   - Lancer ce script en contexte Dolibarr (CLI) avec PHP CLI.
 *   - L'extension PHPSpreadsheet doit être disponible (via composer require phpoffice/phpspreadsheet).
 *   - Les extrafields référencés dans le mapping doivent exister sur les tiers.
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Ce script doit être exécuté en CLI.\n");
    exit(1);
}

if ($argc < 3) {
    fwrite(STDERR, "Usage: php import_clients_from_excel.php <fichier_donnees.xlsx> <fichier_mapping.xlsx>\n");
    exit(1);
}

$dataFile = $argv[1];
$mappingFile = $argv[2];

if (!is_readable($dataFile)) {
    fwrite(STDERR, "Fichier de données introuvable: $dataFile\n");
    exit(1);
}
if (!is_readable($mappingFile)) {
    fwrite(STDERR, "Fichier de mapping introuvable: $mappingFile\n");
    exit(1);
}

if (!defined('NOSESSION')) {
    define('NOSESSION', '1');
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', '1');
}

require_once '/var/www/html/master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

require_once __DIR__ . '/vendor/autoload.php';


/** @var DoliDB $db */
/** @var Conf $conf */

global $db, $conf, $langs;

$langs->load("companies");

$usercli = new User($db);
$defaultUserId = !empty($conf->global->MAIN_IMPORT_DEFAULT_USER) ? (int) $conf->global->MAIN_IMPORT_DEFAULT_USER : 1;
if ($usercli->fetch($defaultUserId) <= 0) {
    fwrite(STDERR, "Impossible de récupérer l'utilisateur ID $defaultUserId\n");
    exit(1);
}

$startTime = microtime(true);
echo 'Fichier données : ' . $dataFile . "\n";
echo 'Fichier mapping : ' . $mappingFile . "\n";
echo "Chargement du mapping...\n";
if (function_exists('ob_flush')) { ob_flush(); }
flush();



$mappingSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($mappingFile);
$mappingSheet = $mappingSpreadsheet->getActiveSheet();
echo 'Mapping chargé.' . "\n";
if (function_exists('ob_flush')) { ob_flush(); }
flush();

echo 'Chargement du fichier de données...' . "\n";
if (function_exists('ob_flush')) { ob_flush(); }
flush();
$dataReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($dataFile);
if (method_exists($dataReader, 'setReadDataOnly')) {
    $dataReader->setReadDataOnly(true);
}
if (method_exists($dataReader, 'setReadEmptyCells')) {
    $dataReader->setReadEmptyCells(false);
}
$dataSpreadsheet = $dataReader->load($dataFile);
$dataSheet = $dataSpreadsheet->getActiveSheet();
echo 'Fichier de données chargé.' . "\n";
if (function_exists('ob_flush')) { ob_flush(); }
flush();

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
    if ($normalizedLabel === '') {
        continue;
    }
    $mapping[$normalizedLabel] = [
        'table' => strtolower($tableName),
        'field' => strtolower($fieldName),
    ];
}

if (empty($mapping)) {
    fwrite(STDERR, "Aucun mapping valide trouvé dans $mappingFile\n");
    exit(1);
}

echo 'Nombre de colonnes mappées : ' . count($mapping) . "\n";
if (function_exists('ob_flush')) { ob_flush(); }
flush();

$headerRow = 1;
$highestColumn = $dataSheet->getHighestColumn();
$headers = [];
foreach ($dataSheet->rangeToArray('A' . $headerRow . ':' . $highestColumn . $headerRow, null, true, false) as $row) {
    foreach ($row as $idx => $value) {
        $headers[$idx] = trim((string) $value);
    }
}

if (empty($headers)) {
    fwrite(STDERR, "Impossible de lire l'entête du fichier de données.\n");
    exit(1);
}

$rowsImported = 0;
$rowsSkipped = 0;

$highestDataRow = (int) $dataSheet->getHighestDataRow();
$totalRows = max(0, $highestDataRow - $headerRow);
echo 'Nombre de lignes à analyser : ' . $totalRows . "\n";
if (function_exists('ob_flush')) { ob_flush(); }
flush();

$unknownHeaders = [];
$debugMissingIdentifiers = 0;
$maxDebugMissing = 5;

for ($row = $headerRow + 1; $row <= $highestDataRow; $row++) {
    $rowValues = $dataSheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, false);
    if (empty($rowValues) || !is_array($rowValues[0])) {
        continue;
    }
    $rowData = $rowValues[0];
    $processed = $row - $headerRow;

    $socFields = [];
    $extraFields = [];
    $ribFields = [];
    foreach ($rowData as $idx => $value) {
        $header = isset($headers[$idx]) ? (string) $headers[$idx] : '';
        $normalizedHeader = normalizeLabel($header);
        if ($normalizedHeader === '' || !isset($mapping[$normalizedHeader])) {
            if ($header !== '') {
                $unknownHeaders[$header] = true;
            }
            continue;
        }
        $map = $mapping[$normalizedHeader];
        $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1) . $row;
        $cell = $dataSheet->getCell($cellCoordinate);
        $formattedValue = formatCellValue($value, $cell);

        switch ($map['table']) {
            case 'llx_societe':
                $socFields[$map['field']] = $formattedValue;
                break;
            case 'llx_societe_extrafields':
                $extraFields['options_' . $map['field']] = $formattedValue;
                break;
            case 'llx_societe_rib':
                $ribFields[$map['field']] = $formattedValue;
                break;
        }
    }

    $codeClient = trim($socFields['code_client'] ?? '');
    $companyName = trim($socFields['nom'] ?? '');

    if ($codeClient !== '') {
        $testSoc = new Societe($db);
        $testSoc->code_client = $codeClient;
        $testSoc->name = $companyName;
        $checkCode = $testSoc->check_codeclient();
        if ($checkCode < 0 && $checkCode != -5) {
            $reason = $testSoc->error ?: 'Code client non conforme au masque Dolibarr';
            echo sprintf("Code client '%s' invalide (ligne %d): %s. Génération automatique utilisée.\n", $codeClient, $processed, $reason);
            if (function_exists('ob_flush')) { ob_flush(); }
            flush();
            unset($socFields['code_client']);
            $codeClient = '';
        }
    }

    if (empty($codeClient) && empty($companyName)) {
        $rowsSkipped++;
        if ($debugMissingIdentifiers < $maxDebugMissing) {
            echo sprintf("Ligne %d ignorée: code client et nom vides. Colonnes disponibles: %s\n", $processed, implode(', ', array_keys($socFields)));
            if (function_exists('ob_flush')) { ob_flush(); }
            flush();
            $debugMissingIdentifiers++;
        }
        logProgress($processed, $totalRows, $rowsImported, $rowsSkipped);
        continue;
    }

    if (clientExists($db, $codeClient, $companyName)) {
        $rowsSkipped++;
        logProgress($processed, $totalRows, $rowsImported, $rowsSkipped);
        continue;
    }

    $soc = new Societe($db);
    $soc->name = $companyName;
    $soc->nom = $companyName;
    $soc->client = 1;
    $soc->status = 1;

    foreach ($socFields as $field => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $soc->$field = $value;
    }

    if (!empty($extraFields)) {
        $soc->array_options = $extraFields;
    }

    $db->begin();
    $result = $soc->create($usercli);
    if ($result <= 0) {
        $db->rollback();
        $errorMsg = $soc->error ?: 'Erreur inconnue';
        if (!empty($soc->errors)) {
            $errorMsg .= ' | ' . implode(' ; ', array_unique($soc->errors));
        }
        $dbError = $db->lasterror();
        if ($dbError) {
            $errorMsg .= ' | DB: ' . $dbError;
        }
        $context = [
            'code_client' => $codeClient,
            'nom' => $companyName,
            'fields' => $socFields,
            'extrafields' => $extraFields,
            'rib' => $ribFields
        ];
        fwrite(STDERR, "Echec création client (ligne $row): " . $errorMsg . ' | Contexte: ' . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n");
        $rowsSkipped++;
        logProgress($processed, $totalRows, $rowsImported, $rowsSkipped);
        continue;
    }

    if (!empty($ribFields)) {
        $rib = new CompanyBankAccount($db);
        $rib->socid = $soc->id;
        $rib->fk_soc = $soc->id; // pour compatibilité
        foreach ($ribFields as $field => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $rib->$field = $value;
        }
        $rib->status = 1;
        $rib->type = 'ban';
        $rib->datec = dol_now();

        $ribResult = $rib->create($usercli);
        if ($ribResult <= 0) {
            fwrite(STDERR, "Avertissement: compte bancaire non créé pour le client {$companyName} (ligne $row): " . $rib->error . "\n");
        }
    }

    $db->commit();
    $rowsImported++;
    logProgress($processed, $totalRows, $rowsImported, $rowsSkipped);
}

if (!empty($unknownHeaders)) {
    echo 'Colonnes ignorées (non mappées) : ' . implode(', ', array_keys($unknownHeaders)) . "\n";
    if (function_exists('ob_flush')) { ob_flush(); }
    flush();
}

$duration = microtime(true) - $startTime;
echo sprintf("Import terminé en %.2f s. Clients créés: %d ; lignes ignorées: %d\n", $duration, $rowsImported, $rowsSkipped);
if (function_exists('ob_flush')) { ob_flush(); }
flush();

/**
 * Normalise les libellés (casse et espaces) pour faire correspondre entêtes et mapping.
 */
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

/**
 * Retourne true si un client existe déjà (code client ou nom).
 */
function clientExists(DoliDB $db, string $codeClient, string $name): bool
{
    $sqlParts = [];
    if ($codeClient !== '') {
        $sqlParts[] = "code_client = '" . $db->escape($codeClient) . "'";
    }
    if ($name !== '') {
        $sqlParts[] = "nom = '" . $db->escape($name) . "'";
    }
    if (empty($sqlParts)) {
        return false;
    }

    $sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . "societe WHERE " . implode(' OR ', $sqlParts) . ' LIMIT 1';
    $resql = $db->query($sql);
    if (!$resql) {
        fwrite(STDERR, "Erreur SQL: " . $db->lasterror() . "
");
        return false;
    }
    $exists = (bool) $db->fetch_object($resql);
    $db->free($resql);
    return $exists;
}

function logProgress(int $processed, int $total, int $imported, int $skipped): void
{
    if ($total <= 0 || $processed <= 0) {
        return;
    }

    if (($processed % 50) !== 0 && $processed !== $total) {
        return;
    }

    echo sprintf("Progression : %d/%d (créés : %d, ignorés : %d)\n", $processed, $total, $imported, $skipped);
    if (function_exists('ob_flush')) {
        ob_flush();
    }
    flush();
}

/**
 * Formatte la valeur lue dans la cellule Excel en chaîne manipulable.
 */
function formatCellValue($rawValue, ?\PhpOffice\PhpSpreadsheet\Cell\Cell $cell = null)
{
    if ($rawValue === null) {
        return '';
    }

    if ($cell && is_numeric($rawValue) && \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
        $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawValue);
        return $date ? $date->format('Y-m-d') : '';
    }

    if (is_float($rawValue)) {
        $rawValue = rtrim(rtrim((string) $rawValue, '0'), '.');
    }

    if (is_bool($rawValue)) {
        return $rawValue ? '1' : '0';
    }

    return trim((string) $rawValue);
}
