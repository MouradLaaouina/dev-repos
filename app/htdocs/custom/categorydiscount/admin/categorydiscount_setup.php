<?php
// phpcs:ignorefile
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

dol_include_once('/categorydiscount/class/categorydiscountrule.class.php');

$langs->loadLangs(array('admin', 'categorydiscount@categorydiscount'));

if (!$user->admin && empty($user->rights->categorydiscount->write)) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$error = 0;
$mesgs = array();
$mesgerrors = array();

$form = new Form($db);
$formcompany = new FormCompany($db);
$ruleHelper = new CategoryDiscountRule($db);

if (!$ruleHelper->ensureTable()) {
    setEventMessages('', array($langs->trans('Error').': '.$db->lasterror()), 'errors');
}

if ($action === 'add') {
    $fkSoc = (int) GETPOST('fk_soc', 'int');
    $fkCategorie = (int) GETPOST('fk_categorie', 'int');
    $discount = (float) price2num(GETPOST('discount_percent', 'alpha'));

    if ($fkCategorie <= 0) {
        $error++;
        $mesgerrors[] = $langs->trans('CategoryDiscountMissingCategory');
    }
    if ($discount <= 0) {
        $error++;
        $mesgerrors[] = $langs->trans('CategoryDiscountMissingDiscount');
    }

    if (!$error) {
        $ruleHelper->entity = $conf->entity;
        $ruleHelper->fk_soc = $fkSoc;
        $ruleHelper->fk_categorie = $fkCategorie;
        $ruleHelper->discount_percent = $discount;
        $result = $ruleHelper->create($user);
        if ($result < 0) {
            $mesgerrors[] = $langs->trans('Error').': '.$db->lasterror();
        } else {
            $mesgs[] = $langs->trans('CategoryDiscountRuleCreated');
        }
    }
}

if ($action === 'delete') {
    $id = (int) GETPOST('id', 'int');
    if ($id > 0) {
        $ruleHelper->delete($id);
        $mesgs[] = $langs->trans('CategoryDiscountRuleDeleted');
    }
}

$rules = $ruleHelper->fetchAll($conf->entity);

$help_url = '';
$page_name = 'CategoryDiscountSetup';

llxHeader('', $langs->trans($page_name), $help_url);

print load_fiche_titre($langs->trans($page_name), '', 'discount');

if (!empty($mesgs)) {
    setEventMessages('', $mesgs, 'mesgs');
}
if (!empty($mesgerrors)) {
    setEventMessages('', $mesgerrors, 'errors');
}

print '<div class="fichecenter">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="add">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Customer').'</td>';
print '<td>'.$langs->trans('ProductCategory').'</td>';
print '<td class="right">'.$langs->trans('Discount').'</td>';
print '<td class="center">&nbsp;</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$formcompany->select_company(GETPOST('fk_soc', 'int'), 'fk_soc', '', 1, 0, 0, array(), 0, 0, 0, 'minwidth300').'</td>';
print '<td>'.$form->select_all_categories(Categorie::TYPE_PRODUCT, GETPOST('fk_categorie', 'int'), 'fk_categorie', 64, 0, 0, 0, 'minwidth300').'</td>';
print '<td class="right"><input type="number" step="0.01" min="0" max="100" class="flat" name="discount_percent" value="'.dol_escape_htmltag(GETPOST('discount_percent', 'alpha')).'"> %</td>';
print '<td class="center"><input type="submit" class="button" value="'.$langs->trans('Add').'"></td>';
print '</tr>';
print '</table>';
print '</form>';

print '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Customer').'</td>';
print '<td>'.$langs->trans('ProductCategory').'</td>';
print '<td class="right">'.$langs->trans('Discount').'</td>';
print '<td class="center">'.$langs->trans('DateCreation').'</td>';
print '<td class="center">&nbsp;</td>';
print '</tr>';

if (empty($rules)) {
    print '<tr class="oddeven"><td colspan="5">'.$langs->trans('None').'</td></tr>';
} else {
    foreach ($rules as $rule) {
        $socLabel = $langs->trans('All');
        if (!empty($rule->fk_soc)) {
            $soc = new Societe($db);
            if ($soc->fetch($rule->fk_soc) > 0) {
                $socLabel = $soc->getNomUrl(1);
            } else {
                $socLabel = $rule->fk_soc;
            }
        }

        $catLabel = '';
        $cat = new Categorie($db);
        if ($cat->fetch($rule->fk_categorie) > 0) {
            $catLabel = $cat->getNomUrl(1);
        } else {
            $catLabel = $rule->fk_categorie;
        }

        print '<tr class="oddeven">';
        print '<td>'.$socLabel.'</td>';
        print '<td>'.$catLabel.'</td>';
        print '<td class="right">'.price($rule->discount_percent, 0, $langs, 0, 0, 2).' %</td>';
        print '<td class="center">'.dol_print_date($db->jdate($rule->datec), 'dayhour').'</td>';
        $deleteUrl = $_SERVER['PHP_SELF'].'?token='.newToken().'&action=delete&id='.(int) $rule->rowid;
        print '<td class="center"><a class="reposition" href="'.$deleteUrl.'">'.img_delete().'</a></td>';
        print '</tr>';
    }
}
print '</table>';
print '</div>';

llxFooter();
$db->close();
