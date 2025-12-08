<?php
/* Copyright (C) 2024 Utopios
 *
 * Licence payante - Tous droits réservés
 * L'utilisation de ce module nécessite un accord commercial avec Utopios
 *
 * Pour plus d'informations, contactez: contact@utopios.ma
 */

/**
 * \file    class/exportcnss.class.php
 * \ingroup exportpaie
 * \brief   Classe pour l'export des déclarations CNSS au format e-BDS
 */

/**
 * Classe ExportCNSS
 */
class ExportCNSS
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
	 * @var string Numéro d'affilié
	 */
	public $num_affilie;

	/**
	 * @var string Période (AAAAMM)
	 */
	public $periode;

	/**
	 * @var array Données du préétabli
	 */
	public $preetabli_data = array();

	/**
	 * @var array Données des salariés
	 */
	public $salaries_data = array();

	/**
	 * @var array Données des entrants
	 */
	public $entrants_data = array();

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
	 * Import du fichier préétabli CNSS
	 *
	 * @param string $filepath Chemin vers le fichier préétabli
	 * @return int >0 si OK, <0 si KO
	 */
	public function importPreetabli($filepath)
	{
		if (!file_exists($filepath)) {
			$this->error = "Fichier préétabli non trouvé : " . $filepath;
			return -1;
		}

		$content = file_get_contents($filepath);
		if ($content === false) {
			$this->error = "Impossible de lire le fichier préétabli";
			return -1;
		}

		$lines = explode("\n", $content);
		$this->preetabli_data = array();

		foreach ($lines as $line) {
			if (strlen($line) < 260) continue;

			$type_enreg = substr($line, 0, 3);

			switch ($type_enreg) {
				case 'A00':
					// Nature du fichier
					$this->preetabli_data['identif_transfert'] = trim(substr($line, 3, 14));
					break;

				case 'A01':
					// Entête globale
					$this->num_affilie = trim(substr($line, 3, 7));
					$this->periode = trim(substr($line, 10, 6));
					$this->preetabli_data['raison_sociale'] = trim(substr($line, 16, 40));
					$this->preetabli_data['activite'] = trim(substr($line, 56, 40));
					$this->preetabli_data['adresse'] = trim(substr($line, 96, 120));
					$this->preetabli_data['ville'] = trim(substr($line, 216, 20));
					$this->preetabli_data['code_postal'] = trim(substr($line, 236, 6));
					$this->preetabli_data['code_agence'] = trim(substr($line, 242, 2));
					$this->preetabli_data['date_emission'] = trim(substr($line, 244, 8));
					$this->preetabli_data['date_exig'] = trim(substr($line, 252, 8));
					break;

				case 'A02':
					// Détail émission (salarié)
					$salarie = array(
						'num_assure' => trim(substr($line, 16, 9)),
						'nom_prenom' => trim(substr($line, 25, 60)),
						'enfants' => intval(substr($line, 85, 2)),
						'af_a_payer' => intval(substr($line, 87, 6)),
						'af_a_deduire' => intval(substr($line, 93, 6)),
						'af_net_a_payer' => intval(substr($line, 99, 6))
					);
					$this->preetabli_data['salaries'][] = $salarie;
					break;

				case 'A03':
					// Récapitulatif
					$this->preetabli_data['nbr_salaries'] = intval(substr($line, 16, 6));
					$this->preetabli_data['t_enfants'] = intval(substr($line, 22, 6));
					$this->preetabli_data['t_af_a_payer'] = intval(substr($line, 28, 12));
					$this->preetabli_data['t_af_a_deduire'] = intval(substr($line, 40, 12));
					$this->preetabli_data['t_af_net_a_payer'] = intval(substr($line, 52, 12));
					break;
			}
		}

		return 1;
	}

	/**
	 * Charge les données de paie depuis le module paiedolibarr
	 *
	 * @param int $annee Année
	 * @param int $mois Mois
	 * @return int >0 si OK, <0 si KO
	 */
	public function loadPaieData($annee, $mois)
	{
		$this->periode = sprintf("%04d%02d", $annee, $mois);

		// Récupération des données de paie depuis le module paiedolibarr
		// Convertir la période YYYYMM en format date pour la comparaison
		$annee = substr($this->periode, 0, 4);
		$mois = substr($this->periode, 4, 2);

		$sql = "SELECT DISTINCT";
		$sql .= " u.rowid,";
		$sql .= " u.lastname,";
		$sql .= " u.firstname,";
		$sql .= " u.email,";
		$sql .= " u.birth as date_naissance,";
		$sql .= " u.gender as sexe,";
		$sql .= " ue.paiedolibarrcnss as num_cnss,";
		$sql .= " ue.paiedolibarrcin as num_cnie,";
		$sql .= " ue.paiedolibarrnbrenfants as nbr_enfants,";
		$sql .= " p.rowid as paie_id,";
		$sql .= " p.period,";
		$sql .= " p.salairebrut,";
		$sql .= " p.nbdaywork";
		$sql .= " FROM " . MAIN_DB_PREFIX . "user as u";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user_extrafields as ue ON u.rowid = ue.fk_object";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "paiedolibarr_paies as p ON u.rowid = p.fk_user";
		$sql .= " WHERE YEAR(p.period) = " . intval($annee);
		$sql .= " AND MONTH(p.period) = " . intval($mois);
		$sql .= " AND u.statut = 1";
		$sql .= " ORDER BY u.lastname, u.firstname";

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->salaries_data = array();
			while ($obj = $this->db->fetch_object($resql)) {
				$this->salaries_data[] = array(
					'user_id' => $obj->rowid,
					'num_assure' => $obj->num_cnss ? $obj->num_cnss : '',
					'nom' => $obj->lastname,
					'prenom' => $obj->firstname,
					'nom_prenom' => trim($obj->lastname . ' ' . $obj->firstname),
					'num_cnie' => $obj->num_cnie ? $obj->num_cnie : '',
					'enfants' => $obj->nbr_enfants ? intval($obj->nbr_enfants) : 0,
					'nbr_jours' => $obj->nbdaywork ? intval($obj->nbdaywork) : 0,
					'salaire_brut' => $obj->salairebrut ? floatval($obj->salairebrut) : 0,
					'date_naissance' => $obj->date_naissance,
					'sexe' => $obj->sexe
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
	 * Génère le fichier BDS principal
	 *
	 * @param string $filepath Chemin du fichier à générer
	 * @param bool $is_complementaire Si true, génère un fichier complémentaire
	 * @return int >0 si OK, <0 si KO
	 */
	public function generateBDS($filepath, $is_complementaire = false)
	{
		global $conf;

		$content = '';
		$prefix = $is_complementaire ? 'E' : 'B';
		$cat = $is_complementaire ? 'E0' : 'B0';

		// Enregistrement B00/E00 - Nature du fichier
		$line = str_pad($prefix . '00', 3);
		$line .= str_pad($this->preetabli_data['identif_transfert'], 14, '0', STR_PAD_LEFT);
		$line .= str_pad($cat, 2);
		$line .= str_repeat(' ', 241);
		$content .= substr($line, 0, 260) . "\n";

		// Enregistrement B01/E01 - Entête globale
		$line = str_pad($prefix . '01', 3);
		$line .= str_pad($this->num_affilie, 7, '0', STR_PAD_LEFT);
		$line .= str_pad($this->periode, 6);
		$line .= str_pad($this->preetabli_data['raison_sociale'], 40);
		$line .= str_pad($this->preetabli_data['activite'], 40);
		$line .= str_pad($this->preetabli_data['adresse'], 120);
		$line .= str_pad($this->preetabli_data['ville'], 20);
		$line .= str_pad($this->preetabli_data['code_postal'], 6);
		$line .= str_pad($this->preetabli_data['code_agence'], 2);
		$line .= str_pad($this->preetabli_data['date_emission'], 8);
		$line .= str_pad($this->preetabli_data['date_exig'], 8);
		$content .= substr($line, 0, 260) . "\n";

		// Totaux pour B03/E03
		$total_salaries = 0;
		$total_jours = 0;
		$total_salaire_reel = 0;
		$total_salaire_plaf = 0;
		$total_num_imma = 0;
		$total_ctr = 0;

		// Traitement des salariés (B02/E02 et suivants)
		if (!$is_complementaire) {
			// Déclaration principale - salariés existants
			foreach ($this->salaries_data as $salarie) {
				$line = $this->generateDetailLine($prefix . '02', $salarie, false);
				$content .= $line . "\n";

				$total_salaries++;
				$total_jours += $salarie['nbr_jours'];
				$total_salaire_reel += intval($salarie['salaire_brut'] * 100);
				$total_salaire_plaf += $this->calculateSalairePlafonne($salarie['salaire_brut']);
				$total_num_imma += intval($salarie['num_assure']);
			}
		}

		// Enregistrement B03/E03 - Récapitulatif préétabli
		$line = $this->generateRecapLine($prefix . '03', $total_salaries, $total_jours,
			$total_salaire_reel, $total_salaire_plaf, $total_num_imma, $total_ctr);
		$content .= $line . "\n";

		// Enregistrement B04/E04 - Entrants (obligatoire même si vide)
		$total_entrants = 0;
		$total_jours_entrants = 0;
		$total_salaire_reel_entrants = 0;
		$total_salaire_plaf_entrants = 0;
		$total_num_imma_entrants = 0;
		$total_ctr_entrants = 0;

		if (count($this->entrants_data) > 0) {
			foreach ($this->entrants_data as $entrant) {
				$line = $this->generateEntrantLine($prefix . '04', $entrant);
				$content .= $line . "\n";

				$total_entrants++;
				$total_jours_entrants += $entrant['nbr_jours'];
				$total_salaire_reel_entrants += intval($entrant['salaire_brut'] * 100);
				$total_salaire_plaf_entrants += $this->calculateSalairePlafonne($entrant['salaire_brut']);
				if (!empty($entrant['num_assure'])) {
					$total_num_imma_entrants += intval($entrant['num_assure']);
				}
			}
		} else {
			// Enregistrement vide pour les entrants
			$line = str_pad($prefix . '04', 3);
			$line .= str_pad($this->num_affilie, 7, '0', STR_PAD_LEFT);
			$line .= str_pad($this->periode, 6);
			$line .= str_repeat(' ', 9); // Num_assure vide
			$line .= str_repeat(' ', 60); // Nom_prenom vide
			$line .= str_repeat(' ', 8); // Num_CIN vide
			$line .= str_repeat('0', 2); // Nbr_jours
			$line .= str_repeat('0', 13); // Sal_reel
			$line .= str_repeat('0', 9); // Sal_plaf
			$line .= str_repeat('0', 19); // Ctr
			$line .= str_repeat(' ', 124); // filler
			$content .= substr($line, 0, 260) . "\n";
		}

		// Enregistrement B05/E05 - Récapitulatif entrants
		$line = $this->generateRecapEntrantsLine($prefix . '05', $total_entrants,
			$total_jours_entrants, $total_salaire_reel_entrants,
			$total_salaire_plaf_entrants, $total_num_imma_entrants, $total_ctr_entrants);
		$content .= $line . "\n";

		// Enregistrement B06/E06 - Récapitulatif global
		$line = $this->generateRecapGlobalLine($prefix . '06',
			$total_salaries + $total_entrants,
			$total_num_imma + $total_num_imma_entrants,
			$total_jours + $total_jours_entrants,
			$total_salaire_reel + $total_salaire_reel_entrants,
			$total_salaire_plaf + $total_salaire_plaf_entrants,
			$total_ctr + $total_ctr_entrants);
		$content .= $line . "\n";

		// Écriture du fichier
		$result = file_put_contents($filepath, $content);
		if ($result === false) {
			$this->error = "Impossible d'écrire le fichier BDS";
			return -1;
		}

		return 1;
	}

	/**
	 * Génère une ligne de détail salarié (B02/E02)
	 *
	 * @param string $type Type d'enregistrement
	 * @param array $salarie Données du salarié
	 * @param bool $is_entrant Si true, c'est un entrant
	 * @return string Ligne formatée
	 */
	private function generateDetailLine($type, $salarie, $is_entrant = false)
	{
		$line = str_pad($type, 3);
		$line .= str_pad($this->num_affilie, 7, '0', STR_PAD_LEFT);
		$line .= str_pad($this->periode, 6);
		$line .= str_pad($salarie['num_assure'], 9, '0', STR_PAD_LEFT);
		$line .= str_pad(substr($salarie['nom_prenom'], 0, 60), 60);
		$line .= str_pad($salarie['enfants'], 2, '0', STR_PAD_LEFT);

		// AF (Allocations familiales) - à récupérer du préétabli
		$af_a_payer = 0;
		$af_a_deduire = 0;
		$af_net_a_payer = 0;
		$af_a_reverser = 0;

		if (isset($this->preetabli_data['salaries'])) {
			foreach ($this->preetabli_data['salaries'] as $sal_preetabli) {
				if ($sal_preetabli['num_assure'] == $salarie['num_assure']) {
					$af_a_payer = $sal_preetabli['af_a_payer'];
					$af_a_deduire = $sal_preetabli['af_a_deduire'];
					$af_net_a_payer = $sal_preetabli['af_net_a_payer'];
					$af_a_reverser = $af_net_a_payer; // À ajuster selon les règles
					break;
				}
			}
		}

		$line .= str_pad($af_a_payer, 6, '0', STR_PAD_LEFT);
		$line .= str_pad($af_a_deduire, 6, '0', STR_PAD_LEFT);
		$line .= str_pad($af_net_a_payer, 6, '0', STR_PAD_LEFT);
		$line .= str_pad($af_a_reverser, 6, '0', STR_PAD_LEFT);
		$line .= str_pad($salarie['nbr_jours'], 2, '0', STR_PAD_LEFT);

		// Salaires en centimes
		$salaire_reel = intval($salarie['salaire_brut'] * 100);
		$salaire_plaf = $this->calculateSalairePlafonne($salarie['salaire_brut']);

		$line .= str_pad($salaire_reel, 13, '0', STR_PAD_LEFT);
		$line .= str_pad($salaire_plaf, 9, '0', STR_PAD_LEFT);

		// Situation (vide = normal)
		$line .= str_repeat(' ', 2);

		// Contrôle horizontal
		$ctr = intval($salarie['num_assure']) + $af_a_reverser + $salarie['nbr_jours'] +
		       $salaire_reel + $salaire_plaf;
		$line .= str_pad($ctr, 19, '0', STR_PAD_LEFT);

		// Filler
		$line .= str_repeat(' ', 104);

		return substr($line, 0, 260);
	}

	/**
	 * Génère une ligne de détail entrant (B04/E04)
	 *
	 * @param string $type Type d'enregistrement
	 * @param array $entrant Données de l'entrant
	 * @return string Ligne formatée
	 */
	private function generateEntrantLine($type, $entrant)
	{
		$line = str_pad($type, 3);
		$line .= str_pad($this->num_affilie, 7, '0', STR_PAD_LEFT);
		$line .= str_pad($this->periode, 6);

		// Numéro d'assuré (peut être vide pour les nouveaux)
		if (!empty($entrant['num_assure'])) {
			$line .= str_pad($entrant['num_assure'], 9, '0', STR_PAD_LEFT);
		} else {
			$line .= str_repeat('0', 9);
		}

		$line .= str_pad(substr($entrant['nom_prenom'], 0, 60), 60);
		$line .= str_pad(substr($entrant['num_cnie'], 0, 8), 8);
		$line .= str_pad($entrant['nbr_jours'], 2, '0', STR_PAD_LEFT);

		// Salaires en centimes
		$salaire_reel = intval($entrant['salaire_brut'] * 100);
		$salaire_plaf = $this->calculateSalairePlafonne($entrant['salaire_brut']);

		$line .= str_pad($salaire_reel, 13, '0', STR_PAD_LEFT);
		$line .= str_pad($salaire_plaf, 9, '0', STR_PAD_LEFT);

		// Contrôle horizontal
		$num_imma = !empty($entrant['num_assure']) ? intval($entrant['num_assure']) : 0;
		$ctr = $num_imma + $entrant['nbr_jours'] + $salaire_reel + $salaire_plaf;
		$line .= str_pad($ctr, 19, '0', STR_PAD_LEFT);

		// Filler
		$line .= str_repeat(' ', 124);

		return substr($line, 0, 260);
	}

	/**
	 * Génère une ligne récapitulative (B03/E03)
	 */
	private function generateRecapLine($type, $nbr_salaries, $total_jours, $total_sal_reel,
		$total_sal_plaf, $total_num_imma, $total_ctr)
	{
		$line = str_pad($type, 3);
		$line .= str_pad($this->num_affilie, 7, '0', STR_PAD_LEFT);
		$line .= str_pad($this->periode, 6);
		$line .= str_pad($nbr_salaries, 6, '0', STR_PAD_LEFT);
		$line .= str_repeat('0', 6); // T_Enfants
		$line .= str_repeat('0', 12); // T_AF_A_Payer
		$line .= str_repeat('0', 12); // T_AF_A_Deduire
		$line .= str_repeat('0', 12); // T_AF_Net_A_Payer
		$line .= str_pad($total_num_imma, 15, '0', STR_PAD_LEFT);
		$line .= str_repeat('0', 12); // T_AF_A_Reverser
		$line .= str_pad($total_jours, 6, '0', STR_PAD_LEFT);
		$line .= str_pad($total_sal_reel, 15, '0', STR_PAD_LEFT);
		$line .= str_pad($total_sal_plaf, 13, '0', STR_PAD_LEFT);
		$line .= str_pad($total_ctr, 19, '0', STR_PAD_LEFT);
		$line .= str_repeat(' ', 116);

		return substr($line, 0, 260);
	}

	/**
	 * Génère une ligne récapitulative entrants (B05/E05)
	 */
	private function generateRecapEntrantsLine($type, $nbr_entrants, $total_jours,
		$total_sal_reel, $total_sal_plaf, $total_num_imma, $total_ctr)
	{
		$line = str_pad($type, 3);
		$line .= str_pad($this->num_affilie, 7, '0', STR_PAD_LEFT);
		$line .= str_pad($this->periode, 6);
		$line .= str_pad($nbr_entrants, 6, '0', STR_PAD_LEFT);
		$line .= str_pad($total_num_imma, 15, '0', STR_PAD_LEFT);
		$line .= str_pad($total_jours, 6, '0', STR_PAD_LEFT);
		$line .= str_pad($total_sal_reel, 15, '0', STR_PAD_LEFT);
		$line .= str_pad($total_sal_plaf, 13, '0', STR_PAD_LEFT);
		$line .= str_pad($total_ctr, 19, '0', STR_PAD_LEFT);
		$line .= str_repeat(' ', 170);

		return substr($line, 0, 260);
	}

	/**
	 * Génère une ligne récapitulative globale (B06/E06)
	 */
	private function generateRecapGlobalLine($type, $nbr_total, $total_num_imma, $total_jours,
		$total_sal_reel, $total_sal_plaf, $total_ctr)
	{
		$line = str_pad($type, 3);
		$line .= str_pad($this->num_affilie, 7, '0', STR_PAD_LEFT);
		$line .= str_pad($this->periode, 6);
		$line .= str_pad($nbr_total, 6, '0', STR_PAD_LEFT);
		$line .= str_pad($total_num_imma, 15, '0', STR_PAD_LEFT);
		$line .= str_pad($total_jours, 6, '0', STR_PAD_LEFT);
		$line .= str_pad($total_sal_reel, 15, '0', STR_PAD_LEFT);
		$line .= str_pad($total_sal_plaf, 13, '0', STR_PAD_LEFT);
		$line .= str_pad($total_ctr, 19, '0', STR_PAD_LEFT);
		$line .= str_repeat(' ', 170);

		return substr($line, 0, 260);
	}

	/**
	 * Calcule le salaire plafonné selon le plafond CNSS
	 *
	 * @param float $salaire_brut Salaire brut
	 * @return int Salaire plafonné en centimes
	 */
	private function calculateSalairePlafonne($salaire_brut)
	{
		global $conf;

		// Plafond CNSS (à configurer dans la configuration du module)
		$plafond = !empty($conf->global->EXPORTPAIE_CNSS_PLAFOND) ?
			floatval($conf->global->EXPORTPAIE_CNSS_PLAFOND) : 6000.00;

		$salaire_centimes = $salaire_brut * 100;
		$plafond_centimes = $plafond * 100;

		return intval(min($salaire_centimes, $plafond_centimes));
	}

	/**
	 * Ajoute un entrant à la liste
	 *
	 * @param array $entrant Données de l'entrant
	 * @return void
	 */
	public function addEntrant($entrant)
	{
		$this->entrants_data[] = $entrant;
	}
}
