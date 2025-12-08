<?php
/* Copyright (C) 2024 Utopios
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    exportpaie/admin/exportpaie_setup.php
 * \ingroup exportpaie
 * \brief   Page de configuration du module Export Paie
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/exportpaie/lib/exportpaie.lib.php');

// Load translation files
$langs->loadLangs(array("admin", "exportpaie@exportpaie"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Get parameters
$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');

$error = 0;

/*
 * Actions
 */

if ($action == 'updateMask') {
	$maskconstcnss = GETPOST('maskconstcnss', 'alpha');
	$maskvalue = GETPOST('maskvalue', 'alpha');

	if ($maskconstcnss) {
		$res = dolibarr_set_const($db, $maskconstcnss, $maskvalue, 'chaine', 0, '', $conf->entity);
	}

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// Paramètres CNSS
if ($action == 'set_cnss_num_affilie') {
	$num_affilie = GETPOST('EXPORTPAIE_CNSS_NUM_AFFILIE', 'alpha');
	dolibarr_set_const($db, 'EXPORTPAIE_CNSS_NUM_AFFILIE', $num_affilie, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}

if ($action == 'set_cnss_plafond') {
	$plafond = GETPOST('EXPORTPAIE_CNSS_PLAFOND', 'alpha');
	dolibarr_set_const($db, 'EXPORTPAIE_CNSS_PLAFOND', $plafond, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}

// Paramètres CIMR
if ($action == 'set_cimr_num_adherent') {
	$num_adherent = GETPOST('EXPORTPAIE_CIMR_NUM_ADHERENT', 'alpha');
	dolibarr_set_const($db, 'EXPORTPAIE_CIMR_NUM_ADHERENT', $num_adherent, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}

if ($action == 'set_cimr_regime') {
	$regime = GETPOST('EXPORTPAIE_CIMR_DEFAULT_REGIME', 'alpha');
	dolibarr_set_const($db, 'EXPORTPAIE_CIMR_DEFAULT_REGIME', $regime, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}

if ($action == 'set_cimr_categorie') {
	$categorie = GETPOST('EXPORTPAIE_CIMR_DEFAULT_CATEGORIE', 'int');
	dolibarr_set_const($db, 'EXPORTPAIE_CIMR_DEFAULT_CATEGORIE', $categorie, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}

if ($action == 'set_cimr_taux') {
	$taux = GETPOST('EXPORTPAIE_CIMR_DEFAULT_TAUX', 'alpha');
	dolibarr_set_const($db, 'EXPORTPAIE_CIMR_DEFAULT_TAUX', $taux, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}

/*
 * View
 */

$form = new Form($db);

$page_name = "ExportPaieSetup";
llxHeader('', $langs->trans($page_name));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

$head = exportpaie_admin_prepare_head();
print dol_get_fiche_head($head, 'settings', $langs->trans("ExportPaie"), -1, 'exportpaie@exportpaie');

// Configuration CNSS
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_cnss_num_affilie">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("CNSSSettings").'</th>';
print '</tr>';

// Numéro d'affilié CNSS
print '<tr class="oddeven">';
print '<td width="50%">'.$langs->trans("CNSSNumAffilie").'</td>';
print '<td>';
print '<input type="text" name="EXPORTPAIE_CNSS_NUM_AFFILIE" size="10" value="'.(isset($conf->global->EXPORTPAIE_CNSS_NUM_AFFILIE) ? $conf->global->EXPORTPAIE_CNSS_NUM_AFFILIE : '').'">';
print ' <input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</td>';
print '</tr>';

print '</table>';
print '</form>';

print '<br>';

// Plafond CNSS
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_cnss_plafond">';

print '<table class="noborder centpercent">';
print '<tr class="oddeven">';
print '<td width="50%">'.$langs->trans("CNSSPlafond").'</td>';
print '<td>';
print '<input type="text" name="EXPORTPAIE_CNSS_PLAFOND" size="10" value="'.(isset($conf->global->EXPORTPAIE_CNSS_PLAFOND) ? $conf->global->EXPORTPAIE_CNSS_PLAFOND : '6000').'">';
print ' DH <input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</td>';
print '</tr>';

print '</table>';
print '</form>';

print '<br><br>';

// Configuration CIMR
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_cimr_num_adherent">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("CIMRSettings").'</th>';
print '</tr>';

// Numéro d'adhérent CIMR
print '<tr class="oddeven">';
print '<td width="50%">'.$langs->trans("CIMRNumAdherent").'</td>';
print '<td>';
print '<input type="text" name="EXPORTPAIE_CIMR_NUM_ADHERENT" size="10" value="'.(isset($conf->global->EXPORTPAIE_CIMR_NUM_ADHERENT) ? $conf->global->EXPORTPAIE_CIMR_NUM_ADHERENT : '').'">';
print ' <input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</td>';
print '</tr>';

print '</table>';
print '</form>';

print '<br>';

// Régime par défaut
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_cimr_regime">';

print '<table class="noborder centpercent">';
print '<tr class="oddeven">';
print '<td width="50%">'.$langs->trans("DefaultRegime").'</td>';
print '<td>';
$regimes = array(
	'AL_KAMIL' => $langs->trans("AlKamil"),
	'AL_MOUNASSIB' => $langs->trans("AlMounassib"),
	'TCNSS' => $langs->trans("TCNSS")
);
print $form->selectarray('EXPORTPAIE_CIMR_DEFAULT_REGIME', $regimes,
	(isset($conf->global->EXPORTPAIE_CIMR_DEFAULT_REGIME) ? $conf->global->EXPORTPAIE_CIMR_DEFAULT_REGIME : 'AL_KAMIL'));
print ' <input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</td>';
print '</tr>';

print '</table>';
print '</form>';

print '<br>';

// Catégorie par défaut
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_cimr_categorie">';

print '<table class="noborder centpercent">';
print '<tr class="oddeven">';
print '<td width="50%">'.$langs->trans("DefaultCategorie").'</td>';
print '<td>';
print '<input type="number" name="EXPORTPAIE_CIMR_DEFAULT_CATEGORIE" min="0" max="99" value="'.(isset($conf->global->EXPORTPAIE_CIMR_DEFAULT_CATEGORIE) ? $conf->global->EXPORTPAIE_CIMR_DEFAULT_CATEGORIE : '0').'">';
print ' <input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</td>';
print '</tr>';

print '</table>';
print '</form>';

print '<br>';

// Taux par défaut
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_cimr_taux">';

print '<table class="noborder centpercent">';
print '<tr class="oddeven">';
print '<td width="50%">'.$langs->trans("DefaultTaux").'</td>';
print '<td>';
$taux_list = array(
	'0300' => '3%',
	'0375' => '3,75%',
	'0450' => '4,5%',
	'0525' => '5,25%',
	'0600' => '6%',
	'0700' => '7%',
	'0750' => '7,5%',
	'0800' => '8%',
	'0850' => '8,5%',
	'0900' => '9%',
	'0950' => '9,5%',
	'1000' => '10%',
	'1100' => '11%',
	'1200' => '12%'
);
print $form->selectarray('EXPORTPAIE_CIMR_DEFAULT_TAUX', $taux_list,
	(isset($conf->global->EXPORTPAIE_CIMR_DEFAULT_TAUX) ? $conf->global->EXPORTPAIE_CIMR_DEFAULT_TAUX : '0600'));
print ' <input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</td>';
print '</tr>';

print '</table>';
print '</form>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
