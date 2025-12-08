<?php
/* Copyright (C) 2024 Utopios
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    exportpaie/cimr/index.php
 * \ingroup exportpaie
 * \brief   Page d'export CIMR
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
dol_include_once('/exportpaie/class/exportcimr.class.php');

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

$annee = GETPOST('annee', 'int') ? GETPOST('annee', 'int') : date('Y');
$trimestre = GETPOST('trimestre', 'int') ? GETPOST('trimestre', 'int') : ceil(date('m') / 3);

$error = 0;
$errors = array();

// Initialize objects
$exportcimr = new ExportCIMR($db);
$exportcimr->num_adherent = !empty($conf->global->EXPORTPAIE_CIMR_NUM_ADHERENT) ?
	$conf->global->EXPORTPAIE_CIMR_NUM_ADHERENT : '';

/*
 * Actions
 */

// Générer fichier CIMR
if ($action == 'generate_cimr') {
	// Vérifier la configuration
	if (empty($exportcimr->num_adherent)) {
		setEventMessages($langs->trans("ConfigureCIMR"), null, 'errors');
		$error++;
	}

	if (!$error) {
		// Charger les données de paie pour le trimestre
		$result = $exportcimr->loadPaieData($annee, $trimestre);

		if ($result > 0) {
			// Valider les données
			if ($exportcimr->validateData()) {
				// Générer le fichier CIMR
				$filename = sprintf("CIMR_%s_T%d_%04d.txt",
					str_pad($exportcimr->num_adherent, 6, '0', STR_PAD_LEFT),
					$trimestre,
					$annee);

				$export_dir = $conf->exportpaie->dir_output . '/export';
				if (!file_exists($export_dir)) {
					dol_mkdir($export_dir);
				}

				$filepath = $export_dir . '/' . $filename;

				$result = $exportcimr->generateCIMRFile($filepath);

				if ($result > 0) {
					setEventMessages($langs->trans("CIMRGenerated"), null, 'mesgs');

					// Télécharger le fichier
					header('Content-Type: text/plain; charset=ANSI');
					header('Content-Disposition: attachment; filename="' . $filename . '"');
					header('Content-Length: ' . filesize($filepath));
					readfile($filepath);
					exit;
				} else {
					setEventMessages($exportcimr->error, null, 'errors');
				}
			} else {
				setEventMessages('', $exportcimr->errors, 'errors');
			}
		} else {
			if ($result == 0) {
				setEventMessages($langs->trans("NoDataToExport"), null, 'warnings');
			} else {
				setEventMessages($exportcimr->error, null, 'errors');
			}
		}
	}
}

// Prévisualiser les données
if ($action == 'preview') {
	$result = $exportcimr->loadPaieData($annee, $trimestre);
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formother = new FormOther($db);

llxHeader("", $langs->trans("ExportCIMR"));

print load_fiche_titre($langs->trans("ExportCIMR"), '', 'exportpaie@exportpaie');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="generate_cimr">';

print dol_get_fiche_head('');

// Section 1 : Informations
print '<table class="border centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("CIMRSettings").'</th>';
print '</tr>';

print '<tr>';
print '<td width="30%">'.$langs->trans("NumAdherent").'</td>';
print '<td>';
if (!empty($exportcimr->num_adherent)) {
	print '<strong>'.$exportcimr->num_adherent.'</strong>';
} else {
	print '<span class="opacitymedium">'.$langs->trans("ConfigureCIMR").'</span>';
	print ' <a href="'.dol_buildpath('/exportpaie/admin/exportpaie_setup.php', 1).'">';
	print '<span class="fa fa-cog paddingleft"></span>';
	print '</a>';
}
print '</td>';
print '</tr>';

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
print '<td class="fieldrequired">'.$langs->trans("Trimestre").'</td>';
print '<td>';
print $form->selectarray('trimestre', array(
	1 => $langs->trans("T1"),
	2 => $langs->trans("T2"),
	3 => $langs->trans("T3"),
	4 => $langs->trans("T4")
), $trimestre);
print '</td>';
print '</tr>';

print '</table>';

print dol_get_fiche_end();

// Boutons d'action
print '<div class="center">';
print '<input type="submit" class="button" name="generate" value="'.$langs->trans("GenerateCIMR").'">';
print ' &nbsp; ';
print '<input type="submit" class="button" name="preview" value="'.$langs->trans("Preview").'" onclick="this.form.action.value=\'preview\'">';
print ' &nbsp; ';
print '<input type="button" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'" onclick="history.back()">';
print '</div>';

print '</form>';

// Prévisualisation des données
if ($action == 'preview' && !empty($exportcimr->salaries_data)) {
	print '<br>';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("PreviewData").' - T'.$trimestre.' '.$annee.'</th>';
	print '<th class="right">'.$langs->trans("MatriculeCIMR").'</th>';
	print '<th class="right">'.$langs->trans("NumCNIE").'</th>';
	print '<th class="right">'.$langs->trans("RegimeCIMR").'</th>';
	print '<th class="right">'.$langs->trans("CategorieCIMR").'</th>';
	print '<th class="right">'.$langs->trans("SalaireTrimestriel").'</th>';
	print '</tr>';

	$total_salaires = 0;
	foreach ($exportcimr->salaries_data as $salarie) {
		print '<tr class="oddeven">';
		print '<td>'.dol_trunc($salarie['nom'].' '.$salarie['prenom'], 40).'</td>';
		print '<td class="right">'.$salarie['matricule_cimr'].'</td>';
		print '<td class="right">'.$salarie['num_cnie'].'</td>';
		print '<td class="right">'.$langs->trans($salarie['regime']).'</td>';
		print '<td class="right">'.str_pad($salarie['categorie'], 2, '0', STR_PAD_LEFT).'</td>';
		print '<td class="right">'.price($salarie['salaire_trimestre'], 0, $langs, 1, -1, -1, 'MAD').'</td>';
		print '</tr>';

		$total_salaires += $salarie['salaire_trimestre'];
	}

	// Total
	print '<tr class="liste_total">';
	print '<td colspan="5" class="right"><strong>'.$langs->trans("Total").'</strong></td>';
	print '<td class="right"><strong>'.price($total_salaires, 0, $langs, 1, -1, -1, 'MAD').'</strong></td>';
	print '</tr>';

	print '</table>';
	print '</div>';

	print '<br>';
	print '<div class="opacitymedium center">';
	print $langs->trans("NbrSalaries").' : <strong>'.count($exportcimr->salaries_data).'</strong>';
	print '</div>';
}

// End of page
llxFooter();
$db->close();
