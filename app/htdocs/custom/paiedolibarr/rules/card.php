<?php 

if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

global $conf;
if (!$conf->paiedolibarr->enabled) {
    accessforbidden();
}

dol_include_once('/paiedolibarr/class/paiedolibarr.class.php');
$paiedolibarr = new paiedolibarr($db);
$res = $paiedolibarr->upgradeTheModule();

dol_include_once('/paiedolibarr/class/paiedolibarr_paies.class.php');
dol_include_once('/paiedolibarr/class/paiedolibarr_rules.class.php');
dol_include_once('/paiedolibarr/class/paiedolibarr_paiesrules.class.php');

dol_include_once('/core/class/html.form.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load('paiedolibarr@paiedolibarr');
$langs->loadLangs(array('bills'));
$title = $langs->trans("paierules");

// Initial Objects
$paies      = new paiedolibarr_paies($db);
$object     = new paiedolibarr_rules($db);

$form        	= new Form($db);
$formother 		= new FormOther($db);
$userpay 		= new User($db);


// Get parameters
$titleid     = GETPOST('titleid', 'alpha');
$request_method = $_SERVER['REQUEST_METHOD'];
$action         = GETPOST('action', 'alpha');
$page           = GETPOST('page');
$id             = (int) ( (!empty($_GET['id'])) ? $_GET['id'] : GETPOST('id') ) ;
if($id){
    $object->fetch($id);
    // if($object->entity != $conf->entity)
    //     $result = restrictedArea($user, 'paiedolibarr_rules', $id);
}
$error  = false;
if (!$user->rights->paiedolibarr->lire) {
    accessforbidden();
}

if(in_array($action, ["add","edit"])) {
    if (!$user->rights->paiedolibarr->creer) {
      accessforbidden();
    }
}
if($action == "delete") {
    if (!$user->rights->paiedolibarr->supprimer) {
      accessforbidden();
    }
}

$numero = GETPOST('numero', 'aZ09');
// ------------------------------------------------------------------------- Actions "Create/Update/Delete"
if ($action == 'create' && $request_method === 'POST') {
    require_once 'create.php';
}

if ($action == 'update' && $request_method === 'POST') {
    require_once 'edit.php';
}

// If delete of request
if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {
    require_once 'show.php';
}

$export = GETPOST('export');



/* ------------------------ View ------------------------------ */
$morejs  = array();
llxHeader(array(), $title,'','','','',$morejs,0,0);


$head = '';

// $newcardbutton .= '<a href="index.php">'.$langs->trans('BackToList').'</a>';
$newcardbutton = '';
// print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num, $nbrtotal, 'user', 0, $newcardbutton, '', $limit, 0, 0, 1);
print_fiche_titre($title, $htmlright = '', 'user');

// if($action != 'add'){
    // dol_fiche_head(
    //     $head,
    //     'paiedolibarr',
    //     '', 
    //     0,
    //     "paiedolibarr@paiedolibarr"
    // );
// }


// ------------------------------------------------------------------------- Views
print '<div class="paiepaierulesdiv">';
if($action == "add")
    require_once 'create.php';

if($action == "edit")
    require_once 'edit.php';

if( ($id && empty($action)) || $action == "delete" )
    require_once 'show.php';
print '</div>';
?>

<script type="text/javascript">
    $( document ).ready(function() {
        $('select.select_amounttype').on('change', function() {
            if(this.value !== 'FIX'){
                $('.baseamountfix').hide();
                $('.baseamountcalculated').show();
            }else{
                $('.baseamountfix').show();
                $('.baseamountcalculated').hide();
            }
        });
        $('select.select_ptramounttype').on('change', function() {
            if(this.value !== 'FIX'){
                $('input#ptramount').hide();
            }else{
                $('input#ptramount').show();
            }
        });
        $('select.select_amounttype,select.select_ptramounttype').trigger('change');
        
    });
</script>

<?php

llxFooter();

if (is_object($db)) $db->close();
?>