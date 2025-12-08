<?php
/* Copyright (C) 2024 Utopios
 *
 * Licence payante - Tous droits réservés
 * L'utilisation de ce module nécessite un accord commercial avec Utopios
 *
 * Pour plus d'informations, contactez: contact@utopios.ma
 */

/**
 * \file    class/importbase.class.php
 * \ingroup importdata
 * \brief   Classe de base pour tous les imports
 */

/**
 * Classe ImportBase
 */
abstract class ImportBase
{
	/** @var DoliDB Database handler */
	public $db;

	/** @var string Error message */
	public $error;

	/** @var array Error messages */
	public $errors = array();

	/** @var array Import statistics */
	public $stats = array(
		'total' => 0,
		'success' => 0,
		'errors' => 0,
		'skipped' => 0
	);

	/** @var array Detailed import log */
	public $import_log = array();

	/** @var array Mapping des colonnes */
	public $column_mapping = array();

	/** @var bool Mode simulation */
	public $simulation_mode = false;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Lit un fichier CSV/Excel et retourne les données
	 *
	 * @param string $filepath Chemin du fichier
	 * @param string $delimiter Délimiteur CSV (par défaut ;)
	 * @param string $encoding Encodage du fichier
	 * @return array|int Données du fichier ou <0 si erreur
	 */
	public function readFile($filepath, $delimiter = ';', $encoding = 'UTF-8')
	{
		$extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

		if ($extension == 'csv') {
			return $this->readCSV($filepath, $delimiter, $encoding);
		} elseif (in_array($extension, array('xls', 'xlsx'))) {
			return $this->readExcel($filepath);
		} else {
			$this->error = "Format de fichier non supporté: $extension";
			return -1;
		}
	}

	/**
	 * Lit un fichier CSV
	 *
	 * @param string $filepath Chemin du fichier
	 * @param string $delimiter Délimiteur
	 * @param string $encoding Encodage
	 * @return array|int Données ou <0 si erreur
	 */
	protected function readCSV($filepath, $delimiter = ';', $encoding = 'UTF-8')
	{
		if (!file_exists($filepath)) {
			$this->error = "Fichier non trouvé: $filepath";
			return -1;
		}

		$data = array();
		$handle = fopen($filepath, 'r');

		if ($handle === false) {
			$this->error = "Impossible d'ouvrir le fichier";
			return -1;
		}

		$header = fgetcsv($handle, 0, $delimiter);

		if ($encoding != 'UTF-8') {
			$header = array_map(function($val) use ($encoding) {
				return mb_convert_encoding($val, 'UTF-8', $encoding);
			}, $header);
		}

		while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
			if ($encoding != 'UTF-8') {
				$row = array_map(function($val) use ($encoding) {
					return mb_convert_encoding($val, 'UTF-8', $encoding);
				}, $row);
			}

			$data[] = array_combine($header, $row);
		}

		fclose($handle);

		return $data;
	}

	/**
	 * Lit un fichier Excel (nécessite PHPSpreadsheet si disponible)
	 *
	 * @param string $filepath Chemin du fichier
	 * @return array|int Données ou <0 si erreur
	 */
	protected function readExcel($filepath)
	{
		// Vérifier si PHPSpreadsheet est disponible
		if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
			$this->error = "PHPSpreadsheet n'est pas installé. Utilisez un fichier CSV.";
			return -1;
		}

		try {
			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
			$worksheet = $spreadsheet->getActiveSheet();
			$data = array();

			$header = array();
			$rows = $worksheet->toArray();

			if (count($rows) > 0) {
				$header = $rows[0];

				for ($i = 1; $i < count($rows); $i++) {
					$row = $rows[$i];
					$data[] = array_combine($header, $row);
				}
			}

			return $data;
		} catch (Exception $e) {
			$this->error = "Erreur lors de la lecture du fichier Excel: " . $e->getMessage();
			return -1;
		}
	}

	/**
	 * Récupère les extrafields pour un élément
	 *
	 * @param string $elementtype Type d'élément (societe, product, etc.)
	 * @return array Liste des extrafields
	 */
	public function getExtraFields($elementtype)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label($elementtype);

		return $extrafields->attributes[$elementtype];
	}

	/**
	 * Valide une ligne de données
	 *
	 * @param array $row Ligne de données
	 * @param array $required_fields Champs requis
	 * @return bool True si valide
	 */
	protected function validateRow($row, $required_fields = array())
	{
		foreach ($required_fields as $field) {
			if (empty($row[$field])) {
				$this->errors[] = "Champ requis manquant: $field";
				return false;
			}
		}

		return true;
	}

	/**
	 * Enregistre un log d'import
	 *
	 * @param string $type Type de message (success, error, warning, info)
	 * @param string $message Message
	 * @param array $data Données associées
	 */
	protected function log($type, $message, $data = array())
	{
		$this->import_log[] = array(
			'timestamp' => date('Y-m-d H:i:s'),
			'type' => $type,
			'message' => $message,
			'data' => $data
		);
	}

	/**
	 * Génère un rapport d'import
	 *
	 * @return string Rapport au format HTML
	 */
	public function generateReport()
	{
		$html = '<div class="importdata-report">';
		$html .= '<h3>Rapport d\'import</h3>';

		$html .= '<table class="border centpercent">';
		$html .= '<tr><td width="30%">Total de lignes</td><td><strong>' . $this->stats['total'] . '</strong></td></tr>';
		$html .= '<tr><td>Importées avec succès</td><td class="success"><strong>' . $this->stats['success'] . '</strong></td></tr>';
		$html .= '<tr><td>Erreurs</td><td class="error"><strong>' . $this->stats['errors'] . '</strong></td></tr>';
		$html .= '<tr><td>Ignorées</td><td><strong>' . $this->stats['skipped'] . '</strong></td></tr>';
		$html .= '</table>';

		if (count($this->import_log) > 0) {
			$html .= '<br><h4>Détails</h4>';
			$html .= '<div class="import-log">';

			foreach ($this->import_log as $log) {
				$class = 'log-' . $log['type'];
				$html .= '<div class="' . $class . '">';
				$html .= '<span class="timestamp">[' . $log['timestamp'] . ']</span> ';
				$html .= '<span class="message">' . htmlspecialchars($log['message']) . '</span>';
				$html .= '</div>';
			}

			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Méthode abstraite à implémenter pour chaque type d'import
	 *
	 * @param array $data Données à importer
	 * @return int >0 si OK, <0 si KO
	 */
	abstract public function import($data);

	/**
	 * Télécharge un template d'import
	 *
	 * @param string $filename Nom du fichier template
	 * @return bool True si OK
	 */
	public function downloadTemplate($filename)
	{
		$filepath = dol_buildpath('/importdata/templates/' . $filename, 0);

		if (file_exists($filepath)) {
			header('Content-Type: text/csv; charset=UTF-8');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Length: ' . filesize($filepath));
			readfile($filepath);
			exit;
		}

		return false;
	}
}
