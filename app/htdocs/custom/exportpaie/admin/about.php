<?php
/* Copyright (C) 2024 Utopios
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    exportpaie/admin/about.php
 * \ingroup exportpaie
 * \brief   About page for ExportPaie module
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

/*
 * View
 */

$form = new Form($db);

$page_name = "About";
llxHeader('', $langs->trans($page_name));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ExportPaie").' - '.$langs->trans($page_name), $linkback, 'title_setup');

$head = exportpaie_admin_prepare_head();
print dol_get_fiche_head($head, 'about', $langs->trans("ExportPaie"), -1, 'exportpaie@exportpaie');

print '<table class="border centpercent">';

// Module information
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("ModuleInformation").'</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="30%">'.$langs->trans("ModuleName").'</td>';
print '<td><strong>Export Paie</strong></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Version").'</td>';
print '<td>1.0</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Publisher").'</td>';
print '<td>Utopios</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("License").'</td>';
print '<td>Licence payante - Accord Utopios requis</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>';
print $langs->trans("ExportPaieDescription");
print '</td>';
print '</tr>';

print '</table>';

print '<br>';

// Features
print '<table class="border centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("Features").'</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="30%">Export CNSS (e-BDS)</td>';
print '<td>';
print '<span class="fa fa-check" style="color: green;"></span> ';
print 'Génération de fichiers BDS (Bordereau de Salaires) selon le cahier des charges CNSS';
print '<ul>';
print '<li>Import de fichier préétabli</li>';
print '<li>Génération de déclarations principales et complémentaires</li>';
print '<li>Gestion des 7 types d\'enregistrements (B00-B06)</li>';
print '<li>Validation des données selon les spécifications CNSS</li>';
print '</ul>';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Export CIMR</td>';
print '<td>';
print '<span class="fa fa-check" style="color: green;"></span> ';
print 'Génération de fichiers de déclaration CIMR trimestriels';
print '<ul>';
print '<li>Support des 3 régimes : Al Kamil, Al Mounassib (PME), TCNSS</li>';
print '<li>Déclarations trimestrielles (T1, T2, T3, T4)</li>';
print '<li>Gestion du système à 2 tranches pour TCNSS</li>';
print '<li>Format fixe 190 caractères par ligne</li>';
print '</ul>';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Intégration</td>';
print '<td>';
print '<span class="fa fa-check" style="color: green;"></span> ';
print 'Intégration complète avec le module PaieDolibarr';
print '<ul>';
print '<li>Récupération automatique des données de paie</li>';
print '<li>Utilisation des matricules CNSS et CIMR des salariés</li>';
print '<li>Calcul automatique des plafonds et cotisations</li>';
print '</ul>';
print '</td>';
print '</tr>';

print '</table>';

print '<br>';

// Technical information
print '<table class="border centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("TechnicalInformation").'</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="30%">Format de fichier CNSS</td>';
print '<td>Texte ASCII/ANSI, 260 caractères par ligne, encodage Windows-1252</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Format de fichier CIMR</td>';
print '<td>Texte fixe, 190 caractères par ligne, 22 champs</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Plafond CNSS</td>';
print '<td>6 000 DH par défaut (configurable)</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Périodicité CNSS</td>';
print '<td>Mensuelle</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Périodicité CIMR</td>';
print '<td>Trimestrielle</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Dépendances</td>';
print '<td>';
print '<ul>';
print '<li>Module PaieDolibarr (requis)</li>';
print '<li>Dolibarr 22.0+</li>';
print '</ul>';
print '</td>';
print '</tr>';

print '</table>';

print '<br>';

// Support and documentation
print '<table class="border centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("SupportAndDocumentation").'</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="30%">Cahier des charges CNSS</td>';
print '<td>Cahier de charges e-BDS v1 (Damancom)</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Cahier des charges CIMR</td>';
print '<td>Structure Nouveau Tracé 190 caractères</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Configuration</td>';
print '<td>';
print 'Voir l\'onglet <a href="'.dol_buildpath('/exportpaie/admin/exportpaie_setup.php', 1).'">';
print '<strong>Configuration</strong></a> pour paramétrer le module';
print '</td>';
print '</tr>';

print '</table>';

print '<br>';

// Change log
print '<table class="border centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">Changelog</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="15%"><strong>Version 1.0</strong><br><small>2024</small></td>';
print '<td>';
print '<ul>';
print '<li>Version initiale</li>';
print '<li>Export CNSS (e-BDS) avec gestion complète des 7 types d\'enregistrements</li>';
print '<li>Export CIMR avec support des 3 régimes</li>';
print '<li>Import de préétabli CNSS</li>';
print '<li>Interface de configuration</li>';
print '<li>Prévisualisation des données avant export</li>';
print '</ul>';
print '</td>';
print '</tr>';

print '</table>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
