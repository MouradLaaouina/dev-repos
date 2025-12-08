<?php
/* Copyright (C) 2024 Utopios
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    exportpaie/index.php
 * \ingroup exportpaie
 * \brief   Page d'accueil du module Export Paie
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("exportpaie@exportpaie"));

// Access control
if (empty($conf->exportpaie->enabled)) {
	accessforbidden();
}

// Security check
if (!$user->rights->exportpaie->read) {
	accessforbidden();
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("ExportPaie"));

print load_fiche_titre($langs->trans("ExportPaie"), '', 'exportpaie@exportpaie');

print '<div class="fichecenter">';
print '<div class="fichethirdleft">';

// Description
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("ExportPaie").'</th>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>';
print $langs->trans("ExportPaieDescription");
print '</td>';
print '</tr>';
print '</table>';
print '</div>';

print '</div>';

print '<div class="fichetwothirdright">';

// Boutons d'action
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("SelectOrganisme").'</th>';
print '</tr>';

// Export CNSS
print '<tr class="oddeven">';
print '<td>';
print '<a class="butAction" href="'.dol_buildpath('/exportpaie/cnss/index.php', 1).'">';
print '<span class="fa fa-university paddingright"></span>';
print $langs->trans("ExportCNSS");
print '</a>';
print '<div class="opacitymedium">';
print $langs->trans("HelpCNSS");
print '</div>';
print '</td>';
print '</tr>';

// Export CIMR
print '<tr class="oddeven">';
print '<td>';
print '<a class="butAction" href="'.dol_buildpath('/exportpaie/cimr/index.php', 1).'">';
print '<span class="fa fa-university paddingright"></span>';
print $langs->trans("ExportCIMR");
print '</a>';
print '<div class="opacitymedium">';
print $langs->trans("HelpCIMR");
print '</div>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '</div>';
print '</div>';

print '<div class="clearboth"></div>';

// Statistiques (optionnel)
print '<br>';
print '<div class="fichecenter">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("Statistics").'</th>';
print '</tr>';

// Nombre d'exports du mois
$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."paiedolibarr_paies";
$sql .= " WHERE SUBSTRING(periode, 1, 6) = '".date('Ym')."'";
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	$nb_paies_mois = $obj->nb;
	$db->free($resql);
} else {
	$nb_paies_mois = 0;
}

print '<tr class="oddeven">';
print '<td>'.$langs->trans("NbrSalaries").' ('.$langs->trans("CurrentMonth").')</td>';
print '<td class="right"><strong>'.$nb_paies_mois.'</strong></td>';
print '</tr>';

print '</table>';
print '</div>';
print '</div>';

// End of page
llxFooter();
$db->close();
