<?php
/* Copyright (C) 2024 Utopios
 *
 * Licence payante - Tous droits réservés
 * L'utilisation de ce module nécessite un accord commercial avec Utopios
 *
 * Pour plus d'informations, contactez: contact@utopios.ma
 */

/**
 * \file    class/importstockmovements.class.php
 * \ingroup importdata
 * \brief   Classe pour l'import des mouvements de stock
 */

dol_include_once('/importdata/class/importbase.class.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

/**
 * Classe ImportStockMovements
 */
class ImportStockMovements extends ImportBase
{
	/**
	 * Importe les mouvements de stock
	 *
	 * @param array $data Données à importer
	 * @return int >0 si OK, <0 si KO
	 */
	public function import($data)
	{
		global $user, $conf;

		$this->stats['total'] = count($data);

		foreach ($data as $index => $row) {
			$line_number = $index + 2;

			// Champs requis
			$required_fields = array('product_ref', 'warehouse_ref', 'qty', 'type');

			if (!$this->validateRow($row, $required_fields)) {
				$this->stats['errors']++;
				$this->log('error', "Ligne $line_number: Champs requis manquants", $row);
				continue;
			}

			// Récupérer le produit
			$product = new Product($this->db);
			$product_id = $product->fetch('', $row['product_ref']);

			if ($product_id <= 0) {
				$this->stats['errors']++;
				$this->log('error', "Ligne $line_number: Produit '{$row['product_ref']}' non trouvé");
				continue;
			}

			// Récupérer l'entrepôt
			$warehouse = new Entrepot($this->db);
			$warehouse_id = $warehouse->fetch('', $row['warehouse_ref']);

			if ($warehouse_id <= 0) {
				$this->stats['errors']++;
				$this->log('error', "Ligne $line_number: Entrepôt '{$row['warehouse_ref']}' non trouvé");
				continue;
			}

			// Type de mouvement
			// 0 = Entrée manuelle
			// 1 = Sortie manuelle
			// 2 = Correction/Inventaire
			// 3 = Transfert
			$type = intval($row['type']);

			// Quantité (peut être négative pour une sortie)
			$qty = floatval($row['qty']);

			// Label du mouvement
			$label = !empty($row['label']) ? $row['label'] : 'Import';

			// Numéro de lot/série si applicable
			$batch = !empty($row['batch']) ? $row['batch'] : '';

			// Date du mouvement (défaut = aujourd'hui)
			$datemovement = !empty($row['date']) ? strtotime($row['date']) : time();

			// Prix unitaire
			$price = !empty($row['price']) ? floatval($row['price']) : 0;

			if (!$this->simulation_mode) {
				$mouvementstock = new MouvementStock($this->db);

				// Selon le type de mouvement
				if ($type == 0) {
					// Entrée de stock
					$result = $mouvementstock->livraison($user, $product_id, $warehouse_id, abs($qty), $price, $label, '', $datemovement, '', '', $batch);
				} elseif ($type == 1) {
					// Sortie de stock
					$result = $mouvementstock->livraison($user, $product_id, $warehouse_id, -abs($qty), $price, $label, '', $datemovement, '', '', $batch);
				} elseif ($type == 2) {
					// Correction de stock (inventaire)
					$result = $mouvementstock->_create($user, $product_id, $warehouse_id, $qty, 2, $price, $label, '', $datemovement, '', $batch);
				} elseif ($type == 3 && !empty($row['warehouse_dest_ref'])) {
					// Transfert entre entrepôts
					$warehouse_dest = new Entrepot($this->db);
					$warehouse_dest_id = $warehouse_dest->fetch('', $row['warehouse_dest_ref']);

					if ($warehouse_dest_id > 0) {
						// Sortie de l'entrepôt source
						$result1 = $mouvementstock->livraison($user, $product_id, $warehouse_id, -abs($qty), $price, $label . ' (Transfert sortie)', '', $datemovement, '', '', $batch);

						// Entrée dans l'entrepôt destination
						$result2 = $mouvementstock->livraison($user, $product_id, $warehouse_dest_id, abs($qty), $price, $label . ' (Transfert entrée)', '', $datemovement, '', '', $batch);

						$result = ($result1 > 0 && $result2 > 0) ? 1 : -1;
					} else {
						$result = -1;
						$mouvementstock->error = "Entrepôt destination '{$row['warehouse_dest_ref']}' non trouvé";
					}
				} else {
					$result = -1;
					$mouvementstock->error = "Type de mouvement invalide ou entrepôt destination manquant pour transfert";
				}

				if ($result > 0) {
					$this->stats['success']++;
					$this->log('success', "Ligne $line_number: Mouvement de stock créé (Produit: {$row['product_ref']}, Entrepôt: {$row['warehouse_ref']}, Qté: $qty)");
				} else {
					$this->stats['errors']++;
					$this->log('error', "Ligne $line_number: Erreur création mouvement: " . $mouvementstock->error);
				}
			} else {
				$this->stats['success']++;
				$type_label = array(0 => 'Entrée', 1 => 'Sortie', 2 => 'Inventaire', 3 => 'Transfert');
				$this->log('info', "Ligne $line_number: [SIMULATION] Mouvement type '{$type_label[$type]}' serait créé (Produit: {$row['product_ref']}, Entrepôt: {$row['warehouse_ref']}, Qté: $qty)");
			}
		}

		return 1;
	}

	/**
	 * Génère le template CSV pour l'import des mouvements de stock
	 *
	 * @return string Contenu du template CSV
	 */
	public function generateTemplate()
	{
		$headers = array(
			'product_ref',
			'warehouse_ref',
			'qty',
			'type',
			'label',
			'date',
			'price',
			'batch',
			'warehouse_dest_ref'
		);

		$help = array(
			'# Types de mouvement:',
			'# 0 = Entrée manuelle',
			'# 1 = Sortie manuelle',
			'# 2 = Correction/Inventaire',
			'# 3 = Transfert (nécessite warehouse_dest_ref)',
			'# Date format: YYYY-MM-DD',
			'# qty peut être négatif pour une sortie avec type 1'
		);

		return implode(';', $headers) . "\n" . implode("\n", $help) . "\n";
	}
}
