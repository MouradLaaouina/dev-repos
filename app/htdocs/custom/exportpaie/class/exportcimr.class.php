<?php
/* Copyright (C) 2024 Utopios
 *
 * Licence payante - Tous droits réservés
 * L'utilisation de ce module nécessite un accord commercial avec Utopios
 *
 * Pour plus d'informations, contactez: contact@utopios.ma
 */

/**
 * \file    class/exportcimr.class.php
 * \ingroup exportpaie
 * \brief   Classe pour l'export des déclarations CIMR
 */

/**
 * Classe ExportCIMR
 */
class ExportCIMR
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var string Numéro d'adhérent CIMR
	 */
	public $num_adherent;

	/**
	 * @var int Trimestre (1, 2, 3, 4)
	 */
	public $trimestre;

	/**
	 * @var int Année
	 */
	public $annee;

	/**
	 * @var array Données des salariés
	 */
	public $salaries_data = array();

	/**
	 * @var array Régimes CIMR
	 */
	public $regimes = array(
		'AL_KAMIL' => 'Al Kamil',
		'AL_MOUNASSIB' => 'Al Mounassib',
		'TCNSS' => 'TCNSS'
	);

	/**
	 * @var array Catégories par régime
	 */
	public $categories = array(
		// Al Kamil - régime normal
		'AL_KAMIL' => array(
			'normal' => array('min' => 0, 'max' => 34),
			'55ans' => array('min' => 35, 'max' => 39),
			'50ans' => array('min' => 50, 'max' => 70)
		),
		// Al Mounassib - PME
		'AL_MOUNASSIB' => array(
			'normal' => array('min' => 71, 'max' => 80),
			'55ans' => array('min' => 85, 'max' => 89),
			'50ans' => array('min' => 81, 'max' => 84)
		),
		// TCNSS - 2 tranches
		'TCNSS' => array(
			'tranche1' => array('min' => 0, 'max' => 9),
			'tranche2' => array('min' => 90, 'max' => 99),
			'55ans_t1' => array('min' => 35, 'max' => 39),
			'55ans_t2' => array('min' => 45, 'max' => 49),
			'50ans_t1' => array('min' => 50, 'max' => 59),
			'50ans_t2' => array('min' => 60, 'max' => 69)
		)
	);

	/**
	 * @var array Taux de cotisation disponibles
	 */
	public $taux_cotisation = array(
		'0300', '0375', '0450', '0525', '0600',
		'0700', '0750', '0800', '0850', '0900',
		'0950', '1000', '1100', '1200'
	);

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
	 * Charge les données de paie pour un trimestre
	 *
	 * @param int $annee Année
	 * @param int $trimestre Trimestre (1-4)
	 * @return int >0 si OK, <0 si KO
	 */
	public function loadPaieData($annee, $trimestre)
	{
		$this->annee = $annee;
		$this->trimestre = $trimestre;

		// Déterminer les mois du trimestre
		$mois = $this->getTrimestreMois($trimestre);

		// Récupération des données de paie pour le trimestre
		$sql = "SELECT DISTINCT";
		$sql .= " u.rowid,";
		$sql .= " u.lastname,";
		$sql .= " u.firstname,";
		$sql .= " u.email,";
		$sql .= " u.birth as date_naissance,";
		$sql .= " u.gender as sexe,";
		$sql .= " u.user_mobile as gsm,";
		$sql .= " ue.paiedolibarrcnss as num_cnss,";
		$sql .= " ue.paiedolibarrcin as num_cnie,";
		$sql .= " ue.paiedolibarrcimr as num_cimr,";
		$sql .= " ue.paiedolibarrnbrenfants as nbr_enfants,";
		$sql .= " ue.paiedolibarrsituation_f as situation_familiale,";
		$sql .= " ue.paiedolibarrmatricule as num_interne,";
		$sql .= " SUM(p.salairebrut) as total_salaire_trimestre";
		$sql .= " FROM " . MAIN_DB_PREFIX . "user as u";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user_extrafields as ue ON u.rowid = ue.fk_object";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "paiedolibarr_paies as p ON u.rowid = p.fk_user";
		$sql .= " WHERE (";

		$conditions = array();
		foreach ($mois as $mois_num) {
			$conditions[] = "(YEAR(p.period) = " . intval($annee) . " AND MONTH(p.period) = " . intval($mois_num) . ")";
		}
		$sql .= implode(" OR ", $conditions);
		$sql .= ")";
		$sql .= " AND u.statut = 1";
		$sql .= " GROUP BY u.rowid";
		$sql .= " ORDER BY u.lastname, u.firstname";

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->salaries_data = array();
			while ($obj = $this->db->fetch_object($resql)) {
				$this->salaries_data[] = array(
					'user_id' => $obj->rowid,
					'matricule_cimr' => $obj->num_cimr ? $obj->num_cimr : '',
					'matricule_cnss' => $obj->num_cnss ? $obj->num_cnss : '9999999999',
					'nom' => $obj->lastname,
					'prenom' => $obj->firstname,
					'num_cnie' => $obj->num_cnie ? $obj->num_cnie : '',
					'num_interne' => $obj->num_interne ? $obj->num_interne : '',
					'sexe' => $obj->sexe == 'man' ? 'M' : 'F',
					'nationalite' => 'M', // Par défaut Marocain
					'date_affiliation' => '', // À compléter si nécessaire
					'date_naissance' => $obj->date_naissance,
					'situation_familiale' => $obj->situation_familiale ? $obj->situation_familiale : 'C',
					'nbr_enfants' => $obj->nbr_enfants ? intval($obj->nbr_enfants) : 0,
					'salaire_trimestre' => $obj->total_salaire_trimestre ? floatval($obj->total_salaire_trimestre) : 0,
					'taux_cotisation' => $this->getTauxCotisation(),
					'categorie' => $this->getCategorie(),
					'regime' => $this->getRegime(),
					'gsm' => $obj->gsm ? $obj->gsm : '',
					'email' => $obj->email ? $obj->email : ''
				);
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Génère le fichier CIMR au format texte ASCII
	 *
	 * @param string $filepath Chemin du fichier à générer
	 * @return int >0 si OK, <0 si KO
	 */
	public function generateCIMRFile($filepath)
	{
		global $conf;

		if (empty($this->num_adherent)) {
			$this->num_adherent = !empty($conf->global->EXPORTPAIE_CIMR_NUM_ADHERENT) ?
				$conf->global->EXPORTPAIE_CIMR_NUM_ADHERENT : '';
		}

		if (empty($this->num_adherent)) {
			$this->error = "Numéro d'adhérent CIMR non configuré";
			return -1;
		}

		$content = '';

		// Pour le régime TCNSS, créer 2 lignes par salarié
		foreach ($this->salaries_data as $salarie) {
			// Gestion des salariés avec régime TCNSS (2 tranches)
			if ($salarie['regime'] == 'TCNSS') {
				// Tranche 1 : salaire <= 18000 DH
				$salaire_t1 = min($salarie['salaire_trimestre'], 18000);
				if ($salaire_t1 > 0) {
					$line = $this->generateSalarieLine($salarie, 1, $salaire_t1);
					$content .= $line . "\n";
				}

				// Tranche 2 : salaire > 18000 DH
				if ($salarie['salaire_trimestre'] > 18000) {
					$salaire_t2 = $salarie['salaire_trimestre'] - 18000;
					$line = $this->generateSalarieLine($salarie, 2, $salaire_t2);
					$content .= $line . "\n";
				}
			} else {
				// Régimes Al Kamil et Al Mounassib : 1 ligne par salarié
				$line = $this->generateSalarieLine($salarie, 0, $salarie['salaire_trimestre']);
				$content .= $line . "\n";
			}
		}

		// Écriture du fichier avec encodage ANSI
		$result = file_put_contents($filepath, $content);
		if ($result === false) {
			$this->error = "Impossible d'écrire le fichier CIMR";
			return -1;
		}

		return 1;
	}

	/**
	 * Génère une ligne de salarié au format CIMR (190 positions)
	 *
	 * @param array $salarie Données du salarié
	 * @param int $tranche Numéro de tranche (0 = pas de tranche, 1 = tranche 1, 2 = tranche 2)
	 * @param float $salaire_soumis Salaire soumis à contribution pour cette ligne
	 * @return string Ligne formatée (190 caractères)
	 */
	private function generateSalarieLine($salarie, $tranche = 0, $salaire_soumis = 0)
	{
		$line = '';

		// Champ 1 : Code enregistrement (1 position)
		// 2 = salarié actif, 7 = sortant
		$line .= '2'; // Par défaut salarié actif

		// Champ 2 : Numéro d'adhérent (6 positions)
		$line .= str_pad(substr($this->num_adherent, 0, 6), 6, '0', STR_PAD_LEFT);

		// Champ 3 : Numéro de catégorie (2 positions)
		$categorie = $this->getCategorieFormatee($salarie, $tranche);
		$line .= str_pad($categorie, 2, '0', STR_PAD_LEFT);

		// Champ 4 : Matricule CIMR (9 positions)
		$line .= str_pad(substr($salarie['matricule_cimr'], 0, 9), 9, '0', STR_PAD_LEFT);

		// Champ 5 : Taux de cotisation (4 positions)
		$taux = in_array($salarie['taux_cotisation'], $this->taux_cotisation) ?
			$salarie['taux_cotisation'] : '0600';
		$line .= str_pad($taux, 4, '0', STR_PAD_LEFT);

		// Champ 6 : Nom (25 positions)
		$line .= str_pad(substr($this->cleanString($salarie['nom']), 0, 25), 25);

		// Champ 7 : Prénom (25 positions)
		$line .= str_pad(substr($this->cleanString($salarie['prenom']), 0, 25), 25);

		// Champ 8 : Numéro intérieur (6 positions)
		$line .= str_pad(substr($salarie['num_interne'], 0, 6), 6);

		// Champ 9 : Sexe (1 position) - M ou F
		$line .= $salarie['sexe'];

		// Champ 10 : Nationalité (1 position) - M (Marocain) ou A (Autre)
		$line .= $salarie['nationalite'];

		// Champ 11 : Date d'affiliation (8 positions) - JJMMAAAA
		$date_affiliation = $this->formatDate($salarie['date_affiliation']);
		$line .= str_pad($date_affiliation, 8, '0', STR_PAD_LEFT);

		// Champ 12 : Date de naissance (8 positions) - JJMMAAAA
		$date_naissance = $this->formatDate($salarie['date_naissance']);
		$line .= str_pad($date_naissance, 8, '0', STR_PAD_LEFT);

		// Champ 13 : Situation de famille (1 position) - C, M, D, V
		$line .= $salarie['situation_familiale'];

		// Champ 14 : Nombre d'enfants (1 position)
		$line .= min($salarie['nbr_enfants'], 9);

		// Champ 15 : Salaires soumis à contributions (10 positions)
		// Format: avec décimales (ex: 0003600025 pour 36000,25 DH)
		$salaire_centimes = intval($salaire_soumis * 100);
		$line .= str_pad($salaire_centimes, 10, '0', STR_PAD_LEFT);

		// Champ 16 : Date de sortie (8 positions) - JJMMAAAA
		// Vide si salarié actif
		$line .= str_repeat('0', 8);

		// Champ 17 : N° CNIE (10 positions)
		$cnie = preg_replace('/[^A-Z0-9]/', '', strtoupper($salarie['num_cnie']));
		$line .= str_pad(substr($cnie, 0, 10), 10);

		// Champ 18 : Matricule CNSS (10 positions)
		$line .= str_pad(substr($salarie['matricule_cnss'], 0, 10), 10, '0', STR_PAD_LEFT);

		// Champ 19 : Numéro de GSM (14 positions)
		// Format: 002126******** pour un numéro marocain
		$gsm = $this->formatGSM($salarie['gsm']);
		$line .= str_pad($gsm, 14, '0', STR_PAD_LEFT);

		// Champ 20 : Adresse email (35 positions)
		$line .= str_pad(substr($salarie['email'], 0, 35), 35);

		// Champ 21 : Trimestre (1 position)
		$line .= $this->trimestre;

		// Champ 22 : Année (4 positions)
		$line .= str_pad($this->annee, 4, '0', STR_PAD_LEFT);

		return substr($line, 0, 190);
	}

	/**
	 * Détermine la catégorie CIMR selon le régime et la tranche
	 *
	 * @param array $salarie Données du salarié
	 * @param int $tranche Numéro de tranche (0, 1, 2)
	 * @return string Catégorie formatée (2 chiffres)
	 */
	private function getCategorieFormatee($salarie, $tranche)
	{
		$categorie = intval($salarie['categorie']);

		// Pour TCNSS, ajuster selon la tranche
		if ($salarie['regime'] == 'TCNSS' && $tranche == 2) {
			// Tranche 2 = catégorie + 90
			$categorie = $categorie + 90;
		}

		return str_pad($categorie, 2, '0', STR_PAD_LEFT);
	}

	/**
	 * Formate une date au format JJMMAAAA
	 *
	 * @param string $date Date au format YYYY-MM-DD ou timestamp
	 * @return string Date formatée JJMMAAAA
	 */
	private function formatDate($date)
	{
		if (empty($date)) {
			return '00000000';
		}

		if (is_numeric($date)) {
			// C'est un timestamp
			return date('dmY', $date);
		} else {
			// C'est une date au format string
			$timestamp = strtotime($date);
			if ($timestamp === false) {
				return '00000000';
			}
			return date('dmY', $timestamp);
		}
	}

	/**
	 * Formate un numéro de GSM au format international
	 *
	 * @param string $gsm Numéro de téléphone
	 * @return string Numéro formaté (14 chiffres)
	 */
	private function formatGSM($gsm)
	{
		// Nettoyer le numéro (garder seulement les chiffres)
		$gsm = preg_replace('/[^0-9]/', '', $gsm);

		if (empty($gsm)) {
			return '00000000000000';
		}

		// Si commence par 0, remplacer par 212 (indicatif Maroc)
		if (substr($gsm, 0, 1) == '0') {
			$gsm = '212' . substr($gsm, 1);
		}

		// Ajouter 00 au début si pas déjà présent
		if (substr($gsm, 0, 2) != '00') {
			$gsm = '00' . $gsm;
		}

		return str_pad(substr($gsm, 0, 14), 14, '0', STR_PAD_LEFT);
	}

	/**
	 * Nettoie une chaîne de caractères (enlever accents, caractères spéciaux)
	 *
	 * @param string $str Chaîne à nettoyer
	 * @return string Chaîne nettoyée
	 */
	private function cleanString($str)
	{
		// Convertir en majuscules
		$str = strtoupper($str);

		// Remplacer les caractères accentués
		$unwanted_array = array(
			'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
			'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
			'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
			'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
			'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
			'Ç' => 'C', 'Ñ' => 'N'
		);
		$str = strtr($str, $unwanted_array);

		// Garder seulement lettres, chiffres et espaces
		$str = preg_replace('/[^A-Z0-9 ]/', ' ', $str);

		return $str;
	}

	/**
	 * Retourne les mois d'un trimestre
	 *
	 * @param int $trimestre Numéro du trimestre (1-4)
	 * @return array Tableau des mois du trimestre
	 */
	private function getTrimestreMois($trimestre)
	{
		$mois = array(
			1 => array(1, 2, 3),    // T1 : Janvier à Mars
			2 => array(4, 5, 6),    // T2 : Avril à Juin
			3 => array(7, 8, 9),    // T3 : Juillet à Septembre
			4 => array(10, 11, 12)  // T4 : Octobre à Décembre
		);

		return isset($mois[$trimestre]) ? $mois[$trimestre] : array();
	}

	/**
	 * Ajoute un salarié sortant
	 *
	 * @param int $user_id ID de l'utilisateur
	 * @param string $date_sortie Date de sortie (YYYY-MM-DD)
	 * @return int >0 si OK, <0 si KO
	 */
	public function addSortant($user_id, $date_sortie)
	{
		// Chercher le salarié dans les données
		foreach ($this->salaries_data as &$salarie) {
			if ($salarie['user_id'] == $user_id) {
				$salarie['code_enreg'] = 7; // Code pour sortant
				$salarie['date_sortie'] = $date_sortie;
				return 1;
			}
		}

		return -1;
	}

	/**
	 * Valide les données avant export
	 *
	 * @return bool True si valide, False sinon
	 */
	public function validateData()
	{
		$this->errors = array();

		if (empty($this->num_adherent)) {
			$this->errors[] = "Numéro d'adhérent CIMR manquant";
		}

		if (empty($this->trimestre) || $this->trimestre < 1 || $this->trimestre > 4) {
			$this->errors[] = "Trimestre invalide";
		}

		if (empty($this->annee) || $this->annee < 2000) {
			$this->errors[] = "Année invalide";
		}

		if (count($this->salaries_data) == 0) {
			$this->errors[] = "Aucun salarié à exporter";
		}

		// Valider chaque salarié
		foreach ($this->salaries_data as $idx => $salarie) {
			if (empty($salarie['nom'])) {
				$this->errors[] = "Nom manquant pour le salarié #" . ($idx + 1);
			}
			if (empty($salarie['prenom'])) {
				$this->errors[] = "Prénom manquant pour le salarié #" . ($idx + 1);
			}
			if (empty($salarie['num_cnie']) && empty($salarie['matricule_cimr'])) {
				$this->errors[] = "N° CNIE ou Matricule CIMR requis pour " . $salarie['nom'];
			}
		}

		return count($this->errors) == 0;
	}

	/**
	 * Récupère le taux de cotisation depuis la configuration
	 *
	 * @return string Taux de cotisation (format: 0600 pour 6%)
	 */
	private function getTauxCotisation()
	{
		global $conf;
		return !empty($conf->global->EXPORTPAIE_CIMR_DEFAULT_TAUX) ?
			$conf->global->EXPORTPAIE_CIMR_DEFAULT_TAUX : '0600';
	}

	/**
	 * Récupère la catégorie depuis la configuration
	 *
	 * @return int Catégorie (0-99)
	 */
	private function getCategorie()
	{
		global $conf;
		return !empty($conf->global->EXPORTPAIE_CIMR_DEFAULT_CATEGORIE) ?
			intval($conf->global->EXPORTPAIE_CIMR_DEFAULT_CATEGORIE) : 0;
	}

	/**
	 * Récupère le régime depuis la configuration
	 *
	 * @return string Régime (AL_KAMIL, AL_MOUNASSIB, TCNSS)
	 */
	private function getRegime()
	{
		global $conf;
		return !empty($conf->global->EXPORTPAIE_CIMR_DEFAULT_REGIME) ?
			$conf->global->EXPORTPAIE_CIMR_DEFAULT_REGIME : 'AL_KAMIL';
	}
}
