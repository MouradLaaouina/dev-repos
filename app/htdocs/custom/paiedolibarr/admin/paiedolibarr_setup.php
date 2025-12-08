<?php
/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once '../lib/paiedolibarr.lib.php';

dol_include_once('/paiedolibarr/class/paiedolibarr.class.php');
$paiedolibarr   = new paiedolibarr($db);

// Translations
$langs->load("paiedolibarr@paiedolibarr");
$langs->load("ecm");
$langs->load("banks");

// Access control
// if (! $user->admin) {
//     accessforbidden();
// }

// Parameters
$action = GETPOST('action', 'alpha');

if(!empty($action)){
    if (!$user->rights->paiedolibarr->creer) accessforbidden();
}

$paiedolibarrmodel = GETPOST('paiedolibarrmodel','alpha') ? GETPOST('paiedolibarrmodel','alpha') : 'maroc';
$paiedolibarrnbmonth = GETPOST('paiedolibarrnbmonth','int') ? GETPOST('paiedolibarrnbmonth','int') : '9';
$aftercomma = GETPOST('aftercomma','alpha');
$accountid  = GETPOST('accountid');
$yearsofposition  = GETPOST('yearsofposition', 'int');

/*
 * Actions
 */

if(!empty($action)){

    if($action == "update"){

        $error = 0;

        // $upload_dir     = $conf->mycompany->dir_output.'/watermark/';
        // if(!empty($_FILES['logo']['name'])){
        //     $TFile = $_FILES['logo'];
        //     $logo = array('logo' => dol_sanitizeFileName($TFile['name'],''));
        //     if (dol_mkdir($upload_dir) >= 0)
        //     {
        //         $destfull = $upload_dir.$TFile['name'];
        //         $info     = pathinfo($destfull); 
                
        //         $watermarkname    = dol_sanitizeFileName($TFile['name'],'');
        //         $destfull   = $info['dirname'].'/'.$watermarkname;
        //         $destfull   = dol_string_nohtmltag($destfull);
        //         $noerror  = dol_move_uploaded_file($TFile['tmp_name'], $destfull, 0, 0, $TFile['error'], 0);
        //         if($noerror)
        //             dolibarr_set_const($db, "PAIEDOLIBARR_WATERMARK_IMG", $watermarkname,'chaine',0,'',$conf->entity);
        //         else
        //             $error++;
        //     }
        // }

        if(!dolibarr_set_const($db, "PAIEDOLIBARR_BANK_ACCOUNT_ID", $accountid,'int',0,'',$conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "PAIE_NUMBER_OF_DIGITS_AFTER_THE_DECIMAL_POINT", $aftercomma,'chaine',0,'',$conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "PAIEDOLIBARR_YEARSOFPOSITION", $yearsofposition,'int',0,'',$conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "PAIEDOLIBARR_PAIE_MODEL", $paiedolibarrmodel,'chaine',0,'',$conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "PAIEDOLIBARR_PAIE_NBMONTH", $paiedolibarrnbmonth,'int',0,'',$conf->entity))
            $error++;

        if(!$error)
            setEventMessage($langs->trans("SetupSavedpaie"), 'mesgs');
        else
            setEventMessage($langs->trans("Error"), 'errors');
    }
    elseif($action == "remove"){
        $result = dolibarr_set_const($db, "PAIEDOLIBARR_WATERMARK_IMG", 0,'int',0,'',$conf->entity);
        if($result)
            setEventMessage($langs->trans("SetupSavedpaie"), 'mesgs');
        else
            setEventMessage($langs->trans("Error"), 'errors');
    }
    elseif($action == "enableaddsalary"){
        $name = GETPOST ( 'name', 'text' ); 
        $value = GETPOST ( 'value', 'int' );

        $error = 0;
        if ($value){
            $res = dolibarr_set_const($db, "PAIEDOLIBARR_ENABLE_ADDSALARY", 1, 'int', 0, '', 0);
        }else{
            $res = dolibarr_set_const($db, "PAIEDOLIBARR_ENABLE_ADDSALARY", 0, 'int', 0, '', 0);
        }

        if (! $res > 0) $error ++;

        if (! $error) {
            // activateModule('modCron');
            setEventMessage($langs->trans("SetupSavedpaie"), 'mesgs');
        } else {
            setEventMessage($langs->trans("Error"), 'errors');
        }
    }
    elseif($action == "enablesalaryscall"){
        $name = GETPOST ( 'name', 'text' ); 
        $value = GETPOST ( 'value', 'int' );

        $error = 0;
        if ($value){
            $res = dolibarr_set_const($db, "PAIEDOLIBARR_ENABLE_SALARYSCALL", 1, 'int', 0, '', 0);
        }else{
            $res = dolibarr_set_const($db, "PAIEDOLIBARR_ENABLE_SALARYSCALL", 0, 'int', 0, '', 0);
        }

        if (! $res > 0) $error ++;

        if (! $error) {
            // activateModule('modCron');
            setEventMessage($langs->trans("SetupSavedpaie"), '', 'mesgs');
        } else {
            setEventMessage($langs->trans("Error"), '', 'errors');
        }
    }
    elseif ($action == 'enablearrondi') {

        $name = GETPOST ( 'name', 'text' ); 
        $value = GETPOST ( 'value', 'int' );

        $error = 0;
        if ($value){
            $res = dolibarr_set_const($db, $name, 1, 'int', 0, '', $conf->entity);
        }else{
            $res = dolibarr_set_const($db, $name, 0, 'int', 0, '', $conf->entity);
        }

        if (! $res > 0) $error ++;

        if (! $error) {
            // activateModule('modCron');
            setEventMessage($langs->trans("SetupSavedpaie"), '', 'mesgs');
        } else {
            setEventMessage($langs->trans("Error"), '', 'errors');
        }
    }

    header('Location: ./paiedolibarr_setup.php');
    exit;
}



/*
 * View
 */
$page_name = "paiedolibarrSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'. $langs->trans("BackToModuleList") . '</a>';
// print_fiche_titre($langs->trans($page_name), $linkback);

$head = paiedolibarrAdminPrepareHead();

print dol_get_fiche_head($head, 'general', $langs->trans("paiedolibarr"), -1, 'payment');

// Configuration header
// $head = paiedolibarrAdminPrepareHead();
// dol_fiche_head(
//     $head,
//     'settings',
//     $langs->trans("paiedolibarr"),
//     1,
//     "paiedolibarr@paiedolibarr"
// );


// Setup page goes here
$form=new Form($db);

$var=false;
$currentwate        = $conf->global->PAIEDOLIBARR_WATERMARK_IMG;

print '<div class="paieconfigurationmod">';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update" />';
    print '<table class="border dstable_" width="100%">';
        // print '<tr>';
        //     print '<td class="titlefield">'.$langs->trans('paiedolibarrelepaie').'</td>';
        //     print '<td>';
        //         print $paiedolibarr->getSelectPaieDolibarrels($paiedolibarrel);
        //     print '</td>';
        // print '</tr>';

        $aftercomma = isset($conf->global->PAIE_NUMBER_OF_DIGITS_AFTER_THE_DECIMAL_POINT) ? $conf->global->PAIE_NUMBER_OF_DIGITS_AFTER_THE_DECIMAL_POINT : 2;
        print '<tr>';
            print '<td class="titlefield">'.$langs->trans('paieshowafterpoint').'</td>';
            print '<td>';
            print '<input type="number" step="1" min="0" name="aftercomma" value="'.$aftercomma.'" />';
            print '</td>';
        print '</tr>';
        // print '<tr>';
        //     print '<td>'.$langs->trans('Watermarkphoto').'</td>';
        //     print '<td>';
        //         print '<div id="wrapper">';
        //             print '<input type="file" name="logo" id="logo" ">';
        //             if(!empty($currentwate)){
        //                 $minifile = getImageFileNameForSize($currentwate, '');  
        //                 $dt_files = getAdvancedPreviewUrl('mycompany', '/watermark/'.$minifile, 1, '&entity='.(!empty($object->entity)?$object->entity:$conf->entity));
        //                 print '<a href="'.$dt_files['url'].'" class="'.$dt_files['css'].' butAction" target="'.$dt_files['target'].'" mime="'.$dt_files['mime'].'" />';
        //                 print '<span class="fa fa-search-plus" style="color: gray"></span>';
        //                 print '</a>';

        //                 print '<a href="'.$_SERVER["PHP_SELF"].'?action=remove" style="" class="butActionDelete" />';
        //                 print $langs->trans('Delete');
        //                 print '</a>';
        //             }

        //         print '</div>';
        //     print '</td>';
        // print '</tr>';

        print '<tr>';
            print '<td class="titlefieldcreate ">'.$langs->trans("BankAccount").'</td>';
            print '<td class="">';
                $accountid = dolibarr_get_const($db, 'PAIEDOLIBARR_BANK_ACCOUNT_ID', $conf->entity);
                $form->select_comptes($accountid, 'accountid', 0, '', 1, '', 0, 'width300 maxwidth300');
            print '</td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefield">';
                print $langs->trans("Arrondi").' ('.$langs->trans("ECMTypeAuto").')';
            print '</td>';
            print '<td class="left">';
                $name1 = 'PAIEDOLIBARR_PAIE_ENABLE_ARRONDI_AUTO';
                if (!empty($conf->global->PAIEDOLIBARR_PAIE_ENABLE_ARRONDI_AUTO)) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=enablearrondi&name='.$name1.'&value=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=enablearrondi&name='.$name1.'&value=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
            print '</td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefield">';
                print $langs->trans("CreatedSalary");
            print '</td>';
            print '<td class="left">';
                $name1 = 'PAIEDOLIBARR_ENABLE_ADDSALARY';
                if (!empty($conf->global->PAIEDOLIBARR_ENABLE_ADDSALARY)) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=enableaddsalary&name='.$name1.'&value=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=enableaddsalary&name='.$name1.'&value=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
            print '</td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefield">';
                print $langs->trans("salaryscall");
            print '</td>';
            print '<td class="left">';
                $name1 = 'PAIEDOLIBARR_ENABLE_SALARYSCALL';
                if (!empty($conf->global->PAIEDOLIBARR_ENABLE_SALARYSCALL)) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=enablesalaryscall&name='.$name1.'&value=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=enablesalaryscall&name='.$name1.'&value=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
            print '</td>';
        print '</tr>';

        if (!empty($conf->global->PAIEDOLIBARR_ENABLE_SALARYSCALL)) {
            print '<tr>';
                print '<td class="titlefield">';
                    print $langs->trans("yearsofposition");
                print '</td>';
                print '<td class="left">';
                    print '<input type="number" name="yearsofposition" value="'.($conf->global->PAIEDOLIBARR_YEARSOFPOSITION ? $conf->global->PAIEDOLIBARR_YEARSOFPOSITION : 8).'"> '.$langs->trans('DurationYears');
                print '</td>';
            print '</tr>';
        }
        // print '<tr>';
        //     print '<td class="titlefieldcreate ">'.$langs->trans("JobPatronal").'</td>';
        //     print '<td class="">';
        //         $jobpatronal = dolibarr_get_const($db, 'PAIEDOLIBARR_JOB_PATRONAL', $conf->entity);
        //         print '<input name="jobpatronal" value="'.$jobpatronal.'" >';
        //     print '</td>';
        // print '</tr>';

        $paiedolibarrmodel = $conf->global->PAIEDOLIBARR_PAIE_MODEL ? $conf->global->PAIEDOLIBARR_PAIE_MODEL : 'maroc';


          print '<tr>';
            print '<td class="titlefield">'.$langs->trans('paiedolibarrmodelepaie').'</td>';
            print '<td>';
                print $paiedolibarr->getSelectPaieDolibarrModel($paiedolibarrmodel);
            print '</td>';
        print '</tr>';
        $paiedolibarrnbmonth = $conf->global->PAIEDOLIBARR_PAIE_NBMONTH ? $conf->global->PAIEDOLIBARR_PAIE_NBMONTH : '9';


          print '<tr>';
            print '<td class="titlefield">'.$langs->trans('paiedolibarrnbmonth').'</td>';
            print '<td>';
                print '<input type="number" value="'.$paiedolibarrnbmonth.'" min="0" name="paiedolibarrnbmonth">';
            print '</td>';
        print '</tr>';

    print '</table><br>';

    // Actions
    print '<table class="" width="100%">';
    print '<tr>';
        print '<td colspan="2" align="left">';
        print '<input type="submit" value="'.$langs->trans('Validate').'" name="bouton" class="button" />';
        print '</td>';
    print '</tr>';
    print '</table>';

print '</form>';
print '</div>';
dol_fiche_end(1);


llxFooter();
$db->close();
