<?php
/* Copyright (C) 2024 Utopios
 *
 * Licence payante - Tous droits réservés
 * L'utilisation de ce module nécessite un accord commercial avec Utopios
 *
 * Pour plus d'informations, contactez: contact@utopios.ma
 */

/**
 * \file    class/importwarehouses.class.php
 * \ingroup importdata
 * \brief   Classe pour l'import des entrepôts
 */

dol_include_once('/importdata/class/importbase.class.php');
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

/**
 * Classe ImportWarehouses
 */
class ImportWarehouses extends ImportBase
{
	/**
	 * Importe les entrepôts
	 *
	 * @param array $data Données à importer
	 * @return int >0 si OK, <0 si KO
	 */
	public function import($data)
	{
		global $user, $conf;

		$this->stats['total'] = count($data);

		foreach ($data as $index => $row) {
			$line_number = $index + 2; // +2 car ligne 1 = header

			// Champs requis
			$required_fields = array('ref', 'label');

			if (!$this->validateRow($row, $required_fields)) {
				$this->stats['errors']++;
				$this->log('error', "Ligne $line_number: Champs requis manquants", $row);
				continue;
			}

			// Vérifier si l'entrepôt existe déjà
			$warehouse = new Entrepot($this->db);
			$existing_id = $warehouse->fetch('', $row['ref']);

			if ($existing_id > 0) {
				// Entrepôt existe : mise à jour ou skip selon configuration
				if (!empty($conf->global->IMPORTDATA_UPDATE_EXISTING)) {
					$warehouse->label = $row['label'];
					$warehouse->description = !empty($row['description']) ? $row['description'] : '';
					$warehouse->statut = !empty($row['statut']) ? intval($row['statut']) : 1;
					$warehouse->lieu = !empty($row['lieu']) ? $row['lieu'] : '';
					$warehouse->address = !empty($row['address']) ? $row['address'] : '';
					$warehouse->zip = !empty($row['zip']) ? $row['zip'] : '';
					$warehouse->town = !empty($row['town']) ? $row['town'] : '';
					$warehouse->country_id = !empty($row['country_id']) ? intval($row['country_id']) : 0;
					$warehouse->phone = !empty($row['phone']) ? $row['phone'] : '';
					$warehouse->fax = !empty($row['fax']) ? $row['fax'] : '';

					// Import des extrafields
					if (!empty($row['extrafields'])) {
						$extrafields_data = json_decode($row['extrafields'], true);
						if (is_array($extrafields_data)) {
							$warehouse->array_options = $extrafields_data;
						}
					}

					if (!$this->simulation_mode) {
						$result = $warehouse->update($existing_id, $user);

						if ($result > 0) {
							$this->stats['success']++;
							$this->log('success', "Ligne $line_number: Entrepôt '{$row['ref']}' mis à jour (ID: $existing_id)");
						} else {
							$this->stats['errors']++;
							$this->log('error', "Ligne $line_number: Erreur mise à jour entrepôt '{$row['ref']}': " . $warehouse->error);
						}
					} else {
						$this->stats['success']++;
						$this->log('info', "Ligne $line_number: [SIMULATION] Entrepôt '{$row['ref']}' serait mis à jour");
					}
				} else {
					$this->stats['skipped']++;
					$this->log('warning', "Ligne $line_number: Entrepôt '{$row['ref']}' existe déjà (ID: $existing_id), ignoré");
				}
			} else {
				// Nouvel entrepôt
				$warehouse->ref = $row['ref'];
				$warehouse->label = $row['label'];
				$warehouse->description = !empty($row['description']) ? $row['description'] : '';
				$warehouse->statut = !empty($row['statut']) ? intval($row['statut']) : 1;
				$warehouse->lieu = !empty($row['lieu']) ? $row['lieu'] : '';
				$warehouse->address = !empty($row['address']) ? $row['address'] : '';
				$warehouse->zip = !empty($row['zip']) ? $row['zip'] : '';
				$warehouse->town = !empty($row['town']) ? $row['town'] : '';
				$warehouse->country_id = !empty($row['country_id']) ? intval($row['country_id']) : 0;
				$warehouse->phone = !empty($row['phone']) ? $row['phone'] : '';
				$warehouse->fax = !empty($row['fax']) ? $row['fax'] : '';
				$warehouse->entity = $conf->entity;

				// Import des extrafields
				if (!empty($row['extrafields'])) {
					$extrafields_data = json_decode($row['extrafields'], true);
					if (is_array($extrafields_data)) {
						$warehouse->array_options = $extrafields_data;
					}
				}

				if (!$this->simulation_mode) {
					$result = $warehouse->create($user);

					if ($result > 0) {
						$this->stats['success']++;
						$this->log('success', "Ligne $line_number: Entrepôt '{$row['ref']}' créé (ID: $result)");
					} else {
						$this->stats['errors']++;
						$this->log('error', "Ligne $line_number: Erreur création entrepôt '{$row['ref']}': " . $warehouse->error);
					}
				} else {
					$this->stats['success']++;
					$this->log('info', "Ligne $line_number: [SIMULATION] Entrepôt '{$row['ref']}' serait créé");
				}
			}
		}

		return 1;
	}

	/**
	 * Génère le template CSV pour l'import des entrepôts
	 *
	 * @return string Contenu du template CSV
	 */
	public function generateTemplate()
	{
		$headers = array(
			'ref',
			'label',
			'description',
			'statut',
			'lieu',
			'address',
			'zip',
			'town',
			'country_id',
			'phone',
			'fax',
			'extrafields'
		);

		// Récupérer les extrafields
		$extrafields = $this->getExtraFields('entrepot');
		if (is_array($extrafields) && count($extrafields['label']) > 0) {
			$headers[] = '# Extrafields disponibles: ' . implode(', ', array_keys($extrafields['label']));
		}

		return implode(';', $headers) . "\n";
	}
}
