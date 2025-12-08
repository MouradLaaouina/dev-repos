<?php
/* Copyright (C) 2024 Utopios
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    exportpaie/cnss/index.php
 * \ingroup exportpaie
 * \brief   Page d'export CNSS
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
dol_include_once('/exportpaie/class/exportcnss.class.php');

// Load translation files
$langs->loadLangs(array("exportpaie@exportpaie"));

// Access control
if (empty($conf->exportpaie->enabled)) {
	accessforbidden();
}
if (!$user->rights->exportpaie->read) {
	accessforbidden();
}

// Get parameters
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$annee = GETPOST('annee', 'int') ? GETPOST('annee', 'int') : date('Y');
$mois = GETPOST('mois', 'int') ? GETPOST('mois', 'int') : date('m');
$is_complementaire = GETPOST('complementaire', 'int') ? true : false;

$error = 0;
$errors = array();

// Initialize objects
$exportcnss = new ExportCNSS($db);
$exportcnss->num_affilie = !empty($conf->global->EXPORTPAIE_CNSS_NUM_AFFILIE) ?
	$conf->global->EXPORTPAIE_CNSS_NUM_AFFILIE : '';

/*
 * Actions
 */

// Import préétabli
if ($action == 'import_preetabli' && !empty($_FILES['preetabli']['tmp_name'])) {
	$upload_dir = $conf->exportpaie->dir_temp;
	if (!file_exists($upload_dir)) {
		dol_mkdir($upload_dir);
	}

	$file = $_FILES['preetabli'];
	$filepath = $upload_dir . '/' . $file['name'];

	if (move_uploaded_file($file['tmp_name'], $filepath)) {
		$result = $exportcnss->importPreetabli($filepath);
		if ($result > 0) {
			setEventMessages($langs->trans("PreetabliImported"), null, 'mesgs');
		} else {
			setEventMessages($exportcnss->error, null, 'errors');
		}
	} else {
		setEventMessages($langs->trans("ErrorFileUpload"), null, 'errors');
	}
}

// Générer BDS
if ($action == 'generate_bds') {
	// Charger les données de paie
	$result = $exportcnss->loadPaieData($annee, $mois);

	if ($result > 0) {
		// Générer le fichier BDS
		$filename = sprintf("DS_%s_%04d%02d.txt",
			str_pad($exportcnss->num_affilie, 7, '0', STR_PAD_LEFT),
			$annee, $mois);

		if ($is_complementaire) {
			// Déterminer le numéro de séquence
			$seq = 1; // À améliorer : chercher le dernier numéro
			$filename = sprintf("DSC%d_%s_%04d%02d.txt",
				$seq,
				str_pad($exportcnss->num_affilie, 7, '0', STR_PAD_LEFT),
				$annee, $mois);
		}

		$export_dir = $conf->exportpaie->dir_output . '/export';
		if (!file_exists($export_dir)) {
			dol_mkdir($export_dir);
		}

		$filepath = $export_dir . '/' . $filename;

		$result = $exportcnss->generateBDS($filepath, $is_complementaire);

		if ($result > 0) {
			setEventMessages($langs->trans("BDSGenerated"), null, 'mesgs');

			// Télécharger le fichier
			header('Content-Type: text/plain; charset=utf-8');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Length: ' . filesize($filepath));
			readfile($filepath);
			exit;
		} else {
			setEventMessages($exportcnss->error, null, 'errors');
		}
	} else {
		setEventMessages($exportcnss->error, null, 'errors');
	}
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formother = new FormOther($db);

llxHeader("", $langs->trans("ExportCNSS"));

print load_fiche_titre($langs->trans("ExportCNSS"), '', 'exportpaie@exportpaie');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="generate_bds">';

print dol_get_fiche_head('');

// Section 1 : Import préétabli
print '<table class="border centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("CNSSPreetabli").'</th>';
print '</tr>';

print '<tr>';
print '<td class="fieldrequired">'.$langs->trans("ImportPreetabli").'</td>';
print '<td>';
print '<input type="file" name="preetabli" id="preetabli" accept=".txt">';
print ' <input type="button" class="button" value="'.$langs->trans("Import").'" onclick="importPreetabli()">';
print '<div class="opacitymedium">'.$langs->trans("HelpPreetabli").'</div>';
print '</td>';
print '</tr>';

// Afficher les informations du préétabli si chargé
if (!empty($exportcnss->preetabli_data)) {
	print '<tr>';
	print '<td>'.$langs->trans("NumAffilie").'</td>';
	print '<td><strong>'.$exportcnss->num_affilie.'</strong></td>';
	print '</tr>';

	print '<tr>';
	print '<td>'.$langs->trans("PeriodeBDS").'</td>';
	print '<td><strong>'.$exportcnss->periode.'</strong></td>';
	print '</tr>';

	print '<tr>';
	print '<td>'.$langs->trans("NbrSalaries").'</td>';
	print '<td><strong>'.count($exportcnss->preetabli_data['salaries']).'</strong></td>';
	print '</tr>';
}

print '</table>';
print '<br>';

// Section 2 : Paramètres d'export
print '<table class="border centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("SelectPeriode").'</th>';
print '</tr>';

print '<tr>';
print '<td class="fieldrequired" width="30%">'.$langs->trans("Annee").'</td>';
print '<td>';
// Générer la liste des années (10 ans en arrière et 2 ans en avant)
$year_array = array();
$current_year = date('Y');
for ($y = $current_year - 10; $y <= $current_year + 2; $y++) {
	$year_array[$y] = $y;
}
print $form->selectarray('annee', $year_array, $annee, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100');
print '</td>';
print '</tr>';

print '<tr>';
print '<td class="fieldrequired">'.$langs->trans("Month").'</td>';
print '<td>';
print $form->selectarray('mois', array(
	1 => $langs->trans("January"),
	2 => $langs->trans("February"),
	3 => $langs->trans("March"),
	4 => $langs->trans("April"),
	5 => $langs->trans("May"),
	6 => $langs->trans("June"),
	7 => $langs->trans("July"),
	8 => $langs->trans("August"),
	9 => $langs->trans("September"),
	10 => $langs->trans("October"),
	11 => $langs->trans("November"),
	12 => $langs->trans("December")
), $mois);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>'.$langs->trans("BDSComplementaire").'</td>';
print '<td>';
print '<input type="checkbox" name="complementaire" value="1"'.($is_complementaire ? ' checked' : '').'>';
print ' <span class="opacitymedium">'.$langs->trans("HelpBDSComplementaire").'</span>';
print '</td>';
print '</tr>';

print '</table>';

print dol_get_fiche_end();

// Boutons d'action
print '<div class="center">';
print '<input type="submit" class="button" name="generate" value="'.$langs->trans("GenerateBDS").'">';
print ' &nbsp; ';
print '<input type="button" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'" onclick="history.back()">';
print '</div>';

print '</form>';

// JavaScript pour l'import du préétabli
print '<script type="text/javascript">
function importPreetabli() {
	var form = document.createElement("form");
	form.method = "POST";
	form.action = "'.$_SERVER["PHP_SELF"].'";
	form.enctype = "multipart/form-data";

	var input = document.createElement("input");
	input.type = "hidden";
	input.name = "action";
	input.value = "import_preetabli";
	form.appendChild(input);

	var file = document.getElementById("preetabli");
	form.appendChild(file.cloneNode());

	document.body.appendChild(form);
	form.submit();
}
</script>';

// End of page
llxFooter();
$db->close();
