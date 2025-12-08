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
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

dol_include_once('/paiedolibarr/lib/paiedolibarr.lib.php');
dol_include_once('/paiedolibarr/class/paiedolibarr.class.php');
dol_include_once('/paiedolibarr/class/paiedolibarr_paies.class.php');


$form         = new Form($db);
$formother    = new FormOther($db);
$extrafields  = new ExtraFields($db);
$paiedolibarr = new paiedolibarr($db);

// Translations
$langs->load("paiedolibarr@paiedolibarr");
// Load translation files required by the page
$langs->loadLangs(array('companies', 'admin', 'bills'));


// Load variable for pagination
$limit          = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield      = GETPOST('sortfield', 'aZ09comma');
$sortorder      = GETPOST('sortorder', 'aZ09comma');
$page           = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
    $page = 0;
}

$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
    $sortorder = "DESC, ASC";
}
if (!$sortfield) {
    $sortfield = "degre, year";
}


$idline = GETPOST('idline', 'int');
$action = GETPOST('action', 'aZ09');

$search_year = GETPOST('search_year');
$search_degre = GETPOST('search_degre');


if (!$user->rights->paiedolibarr->creer) accessforbidden();

/*
* Actions
*/

if(GETPOST('cancel')){
    header('Location: ./salaryscall.php');
    exit;
}

$newlines = GETPOST('newlines', 'array');
$editlines = GETPOST('editlines', 'alpha');
$linesdeleted = GETPOST('linesdeleted', 'alpha');

if($linesdeleted){
    $res = $db->query('DELETE FROM '.MAIN_DB_PREFIX.'paiedolibarr_salaryscall WHERE rowid IN ('.$linesdeleted.')');
}

$j=0;
if($newlines && count($newlines)>0){
    $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiedolibarr_salaryscall (degre, year, amount) VALUES ';
    foreach ($newlines as $key => $line) {
        $sql .= ($j>0 ? ',' : ''); 
        $sql .= '('.$line['degre'].', '.$line['year'].', '.($line['amount'] ? price2num($line['amount']) : 0).')';
        $j++;
    }
    
    $res = $db->query($sql);
    if($res){
        setEventMessages($langs->trans('RecordCreatedAllSuccessfully', count($newlines)), null, 'mesgs');
    }else{
        setEventMessages('Error : '. $db->lasterror(), null, 'errors');
        header('Location: ./salaryscall.php');
        exit;
    }
}

$j=0;
if($action == 'updateline' && $idline>0){
    
    $degre = GETPOST('degre');
    $year = GETPOST('year');
    $amount = GETPOST('amount');

    $sql = 'UPDATE '.MAIN_DB_PREFIX.'paiedolibarr_salaryscall SET ';
    $sql .= 'degre='.$db->escape($degre);
    $sql .= $year ? ', year='.$db->escape($year) : '';
    $sql .= $amount ? ', amount='.$db->escape($amount) : '';
    $sql .= ' WHERE rowid='.$idline;
    $res = $db->query($sql);
    
    if($res>0){
        setEventMessages($langs->trans('RecordModifiedSuccessfully'), null, 'mesgs');
    }else{
        setEventMessages('Error : '. $db->lasterror(), null, 'errors');
    }
    header('Location: ./salaryscall.php');
    exit;
}

/*
* View
*/

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."paiedolibarr_salaryscall";
$sql .= $search_year ? ' AND year='.$search_year : '';
$sql .= $search_degre ? ' AND degre='.$search_degre : '';
$sql .= $db->order($sortfield, $sortorder);

############################################################################################################################################

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST || 1>0)) {
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
        $page = 0;
        $offset = 0;
    }
}



$sql .= $db->plimit($limit + 1, $offset);
$resql = $db->query($sql);

if ($resql) {
    $num = $db->num_rows($resql);
}

$textobject = strtolower($langs->transnoentitiesnoconv("paiesemployee"));
$title = $langs->trans("salaryscall");

llxHeader('', $title);

$head = paiedolibarrAdminPrepareHead($nbtotalofrecords);
print dol_get_fiche_head($head, 'salaryscall', $langs->trans("salaryscall"), -1, 'payment');

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" id="paiedolibarr_salaryscall">';
    print '<input type="hidden" id="lastline" name="lastline" value="'.($num>0 ? $num : 0).'" >';

    print '<input type="hidden" id="action" name="action" value="'.(($action == 'editline' && $idline>0) ? 'updateline' : 'list').'" >';
    if($action == "editline") print '<input type="hidden" name="idline" value="'.$idline.'">';

    print '<input type="hidden" id="linesdeleted" name="linesdeleted" value="" >';
    
    print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $paies->picto, 0, $newcardbutton, '', $limit, 0, 0, 1, $btnright);

    print '<div class="div-table-responsive">';
        print '<table class="tagtable liste">';
            print '<tr class="liste_titre">';
                print_liste_field_titre($langs->trans('Degre'), $_SERVER["PHP_SELF"], "o.degre", "", $param, 'align="left"', $sortfield, $sortorder, 'left ');
                print_liste_field_titre($langs->trans('Year'), $_SERVER["PHP_SELF"], "o.year", "", $param, 'align="left"', $sortfield, $sortorder, 'center ');
                print_liste_field_titre($langs->trans('Amount'), $_SERVER["PHP_SELF"], "o.amount", "", $param, 'align="left"', $sortfield, $sortorder, 'center ');
                // <a href="" class="newligne"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span></a>
                print_liste_field_titre('', $_SERVER["PHP_SELF"], "o.amount", "", $param, '', $sortfield, $sortorder, 'center ');
            print '</tr>';
            print '<tr class="liste_titre_filter">';
                print '<td class="liste_titre left select2js">'.$paiedolibarr->selectdegres('search_degre', $search_degre, 'search_degre', 1).'</td>';
                print '<td class="liste_titre center select2js">'.$paiedolibarr->selectyears('search_year', $search_year, 1).'</td>';
                print '<td class="liste_titre center "></td>';
                // Action column
                print '<td class="liste_titre maxwidthsearch center">';
                    $searchpicto = $form->showFilterButtons();
                    print $searchpicto;
                print '</td>';
            print '</tr>';
            print '<tbody id="lines_scall">';
                $clhiden = '';
                $clactionhiden='hidden';
                if($num>0){
                    $clhiden = 'hidden';
                    // $clactionhiden="";
                    $i=0;
                    while ($i < min($num, $limit)) {
                        $obj = $db->fetch_object($resql);

                        if($action == 'editline' && $idline == $obj->rowid){
                            print '<tr class="tr_ligne">';
                                print '<td class="select2js">'.$paiedolibarr->selectdegres('degre', $obj->degre, 'degre').'</td>';
                                print '<td class="center select2js">'.$paiedolibarr->selectyears('year', $obj->year).'</td>';
                                print '<td class="center"><input type="number" name="amount" value="'.price2num($obj->amount).'"></td>';
                                print '<td class="minwidth300">';
                                    print '<input type="submit" name="save" class="button badge-status4" value="'.$langs->trans('Save').'">';
                                    print '<input type="submit" name="cancel" class="button" value="'.$langs->trans('Cancel').'">';
                                print '</td>';
                            print '</tr>';
                        }else{
                            print '<tr class="tr_ligne">';
                                print '<td>'.$obj->degre.'</td>';
                                print '<td class="center">'.$langs->trans('Year').' '.$obj->year.'</td>';
                                print '<td class="center">'.price($obj->amount).'</td>';
                                print '<td class="center">';
                                    print '<a href="salaryscall.php?action=editline&idline='.$obj->rowid.'">'.img_picto($langs->trans("Edit"), 'edit', 'class="valignmiddle marginleftonly paddingrightonly"').'</a>';
                                    print '<a class="cursorpointer" onclick="deleteligne(this)" data-id="'.$obj->rowid.'" title="'.$langs->trans('Delete').'">'.img_delete().'</a>';
                                print '</td>';
                            print '</tr>';
                        }

                        $i++;
                    }
                }
            print '</tbody>';
            print '<tr class="tr_noresult '.$clhiden.'"><td colspan="5" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
        print '</table>';
    print '</div>';

    print '<div class="center '.$clactionhiden.' btnsave" id="save_newlines" class="hidden"><input type="submit" class="butAction badge-status4" name="save" value="'.$langs->trans('Save').'">';
    print '<input type="submit" class="butAction" name="cancel" value="'.$langs->trans('Cancel').'"></div>';

print '</form>';

// Buttons
if($action != 'editline'){
    print '<div class="tabsAction">';
        print '<a class="butAction" onclick="addligne(this)" ><span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span>  '.$langs->trans("newline").'</a>';
    print "</div>";
}

print dol_get_fiche_end();

?>

<script>
    $(document).ready(function() {
        $('.select2js select').select2();
    })

    function addligne(that) {
        var id = parseInt($('#lastline').val()) + 1;
        $('#lastline').val(id);

        data = '<tr class="tr_ligne newline'+id+'">';
            data += '<td class="left width25p select2js"><?php echo dol_escape_js($paiedolibarr->selectdegres('new_degre')); ?></td>';
            data += '<td class="center width25p select2js"><?php echo dol_escape_js($paiedolibarr->selectyears('new_year')); ?></td>';
            data += '<td class="center"><input name="newlines['+id+'][amount]"></td>';
            data += '<td class="center"><a class="cursorpointer pull-right" data-ligne="t4" onclick="removeligne(this)" title="<?php echo dol_escape_htmltag($langs->trans('Delete')); ?>"><?php echo img_delete()?></a>';
            data += '</td>';
        data += '</tr>';

        $('#lines_scall').prepend(data);
        if($('#lines_scall tr.tr_ligne').length>0){
            if(!($('.tr_noresult').hasClass('hidden')))
                $('.tr_noresult').addClass('hidden');
            $('#save_newlines').removeClass('hidden');
        }

        $('.newline'+id).find('select#new_degre').attr('name', 'newlines['+id+'][degre]');
        $('.newline'+id).find('select#new_degre').attr('data-id', id);
        $('.newline'+id).find('select#new_degre').attr('id', 'degre'+id);
        $('.newline'+id).find('#degre'+id).select2();

        $('.newline'+id).find('select#new_year').attr('name', 'newlines['+id+'][year]');
        $('.newline'+id).find('select#new_year').attr('data-id', id);
        $('.newline'+id).find('select#new_year').attr('id', 'year'+id);
        $('.newline'+id).find('#year'+id).select2();

    }

    function removeligne(that) {
        $(that).parents('tr').remove();

        var id = parseInt($('#lastline').val()) - 1;
        $('#lastline').val(id);

        if($('#lines_scall tr.tr_ligne').length <= 0){
            $('.tr_noresult').removeClass('hidden');
            $('.btnsave').addClass('hidden');
        }
    }

    function deleteligne(that) {

        var id = $(that).data('id');
        var ids = $('#linesdeleted').val();

        if(id > 0){
            if(ids) ids += ','+id;
            else ids = id;
        }
        $('#linesdeleted').val(ids);
        $('#save_newlines').removeClass('hidden');

        removeligne($(that));
    }

</script>

<?php 

// End of page
llxFooter();
$db->close();
