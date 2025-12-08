<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

dol_include_once('/arrivalcost/class/arrivalcost.class.php');

$langs->loadLangs(array('arrivalcost@arrivalcost', 'orders', 'suppliers'));

if (empty($user->rights->arrivalcost->write)) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$viewId = GETPOST('id', 'int');
$fromOrder = GETPOST('from_order', 'int');

$selectedOrders = GETPOST('orders', 'array');
if ($fromOrder > 0 && empty($selectedOrders)) {
    $selectedOrders = array($fromOrder);
}

$fkSupplier = (int) GETPOST('fk_supplier', 'int');
$createInvoice = GETPOST('create_invoice', 'int');

$helper = new ArrivalCost($db);
$form = new Form($db);
$formcompany = new FormCompany($db);

if ($action === 'create') {
    $data = array(
        'amount' => GETPOST('amount_ht', 'alpha'),
        'mode' => GETPOST('mode', 'alpha'),
        'label' => GETPOST('label', 'restricthtml'),
        'note' => GETPOST('note', 'restricthtml'),
        'orders' => $selectedOrders,
        'fk_supplier' => $fkSupplier,
        'create_invoice' => $createInvoice
    );

    $result = $helper->create($user, $data);
    if ($result > 0) {
        $created = $helper->fetch($result);
        $label = $helper->last_ref ?: ($created && !empty($created['ref']) ? $created['ref'] : $result);
        setEventMessages($langs->trans('ArrivalCostCreated', $label), null, 'mesgs');
        $selectedOrders = array();
        $viewId = $result;
    } else {
        if (!empty($helper->errors)) {
            setEventMessages('', $helper->errors, 'errors');
        } elseif (!empty($helper->error)) {
            setEventMessages('', array($helper->error), 'errors');
        } else {
            setEventMessages($langs->trans('Error'), null, 'errors');
        }
    }
}

$current = $viewId > 0 ? $helper->fetch($viewId) : null;
$lines = $viewId > 0 ? $helper->fetchLines($viewId) : array();
$rows = $helper->fetchAll(50);
$ordersOptions = fetchSupplierOrdersOptions($db);
$defaultMode = getDolGlobalString('ARRIVALCOST_DEFAULT_MODE', 'amount');

$help_url = '';
llxHeader('', $langs->trans('ArrivalCostMenu'), $help_url);

print load_fiche_titre($langs->trans('ArrivalCostMenu'), '', 'generic');

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="create">';

print '<table class="border centpercent">';
print '<tr><td class="fieldrequired">'.$langs->trans('Label').'</td>';
print '<td><input type="text" class="flat minwidth300" name="label" value="'.dol_escape_htmltag(GETPOST('label', 'alphanohtml')).'"></td></tr>';

print '<tr><td class="fieldrequired">'.$langs->trans('AmountHT').'</td>';
print '<td><input type="number" step="0.01" min="0" class="flat minwidth150" name="amount_ht" value="'.dol_escape_htmltag(GETPOST('amount_ht', 'alpha')).'"> '.$langs->getCurrencySymbol($conf->currency).'</td></tr>';

print '<tr><td>'.$langs->trans('ArrivalCostMode').'</td>';
$selectedMode = GETPOST('mode', 'alpha') ?: $defaultMode;
print '<td><select name="mode" class="flat minwidth150">';
print '<option value="amount"'.($selectedMode === 'amount' ? ' selected' : '').'>'.$langs->trans('ArrivalCostModeAmount').'</option>';
print '<option value="qty"'.($selectedMode === 'qty' ? ' selected' : '').'>'.$langs->trans('ArrivalCostModeQty').'</option>';
print '</select></td></tr>';

print '<tr><td class="fieldrequired">'.$langs->trans('SupplierOrders').'</td>';
if (empty($ordersOptions)) {
    print '<td class="opacitymedium">'.$langs->trans('None').'</td>';
} else {
    // Render a plain multi-select to avoid JS/CSS dependencies
    print '<td><select name="orders[]" class="flat minwidth400" multiple size="8">';
    foreach ($ordersOptions as $oid => $olabel) {
        $sel = in_array($oid, $selectedOrders, true) ? ' selected' : '';
        print '<option value="'.((int) $oid).'"'.$sel.'>'.dol_escape_htmltag($olabel).'</option>';
    }
    print '</select></td>';
}
print '</tr>';

print '<tr><td>'.$langs->trans('Supplier').'</td>';
print '<td>'.$formcompany->select_company($fkSupplier, 'fk_supplier', 's.fournisseur = 1', 1, 0, 0, array(), 0, 0, 0, 'minwidth400').'</td></tr>';

print '<tr><td>'.$langs->trans('ArrivalCostCreateInvoice').'</td>';
print '<td><label><input type="checkbox" name="create_invoice" value="1" '.($createInvoice ? 'checked' : '').'> '.$langs->trans('ArrivalCostCreateInvoiceHelp').'</label></td></tr>';

print '<tr><td>'.$langs->trans('Note').'</td>';
print '<td><textarea class="flat quatrevingtpercent" name="note" rows="3">'.dol_escape_htmltag(GETPOST('note', 'restricthtml')).'</textarea></td></tr>';

print '</table>';

print '<div class="center marginbottomonly marginleftonly margintoponly">';
print '<input type="submit" class="button" value="'.$langs->trans('ArrivalCostAllocate').'">';
print '</div>';
print '</form>';
print '</div>';

print '<div class="fichehalfright">';
print '<h3>'.$langs->trans('ArrivalCostHistory').'</h3>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans('Ref').'</th>';
print '<th>'.$langs->trans('Label').'</th>';
print '<th class="right">'.$langs->trans('AmountHT').'</th>';
print '<th>'.$langs->trans('ArrivalCostModeShort').'</th>';
print '<th class="right">'.$langs->trans('Lines').'</th>';
print '<th>'.$langs->trans('SupplierInvoice').'</th>';
print '<th class="center">'.$langs->trans('Date').'</th>';
print '<th class="center">'.$langs->trans('Action').'</th>';
print '</tr>';

if (empty($rows)) {
    print '<tr class="oddeven"><td colspan="7">'.$langs->trans('None').'</td></tr>';
} else {
    foreach ($rows as $row) {
        $url = $_SERVER['PHP_SELF'].'?id='.(int) $row['rowid'];
        print '<tr class="oddeven">';
        print '<td><a href="'.$url.'">'.dol_escape_htmltag($row['ref']).'</a></td>';
        print '<td>'.dol_escape_htmltag($row['label']).'</td>';
        print '<td class="right">'.price($row['amount_ht'], 0, $langs, 0, 0, 0, $conf->currency).'</td>';
        print '<td>'.($row['mode'] === 'qty' ? $langs->trans('ArrivalCostModeQty') : $langs->trans('ArrivalCostModeAmount')).'</td>';
        print '<td class="right">'.(int) $row['line_count'].'</td>';
        $inv = !empty($row['fk_supplier_invoice']) ? '<a href="'.dol_buildpath('/fourn/facture/card.php?facid='.(int) $row['fk_supplier_invoice'], 1).'">'.$langs->trans('Show').'</a>' : '';
        print '<td>'.$inv.'</td>';
        print '<td class="center">'.dol_print_date($db->jdate($row['datec']), 'dayhour').'</td>';
        print '<td class="center"><a class="reposition" href="'.$url.'">'.$langs->trans('Detail').'</a></td>';
        print '</tr>';
    }
}
print '</table>';
print '</div>';
print '</div>';

if ($current) {
    print '<div class="clearboth"></div>';
    print '<br>';
    print '<h3>'.$langs->trans('ArrivalCostDetail', dol_escape_htmltag($current['ref'])).'</h3>';
    print '<table class="border centpercent">';
    print '<tr><td>'.$langs->trans('Ref').'</td><td>'.dol_escape_htmltag($current['ref']).'</td></tr>';
    print '<tr><td>'.$langs->trans('Label').'</td><td>'.dol_escape_htmltag($current['label']).'</td></tr>';
    print '<tr><td>'.$langs->trans('AmountHT').'</td><td>'.price($current['amount_ht'], 0, $langs, 0, 0, 0, $conf->currency).'</td></tr>';
    print '<tr><td>'.$langs->trans('ArrivalCostMode').'</td><td>'.($current['mode'] === 'qty' ? $langs->trans('ArrivalCostModeQty') : $langs->trans('ArrivalCostModeAmount')).'</td></tr>';
    if (!empty($current['note'])) {
        print '<tr><td>'.$langs->trans('Note').'</td><td>'.dol_nl2br(dol_escape_htmltag($current['note'])).'</td></tr>';
    }
    print '</table>';

    print '<br>';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans('SupplierOrder').'</th>';
    print '<th>'.$langs->trans('Description').'</th>';
    print '<th class="right">'.$langs->trans('Qty').'</th>';
    print '<th class="right">'.$langs->trans('ArrivalCostBase').'</th>';
    print '<th class="right">'.$langs->trans('ArrivalCostAllocated').'</th>';
    print '<th class="right">'.$langs->trans('ArrivalCostUnit').'</th>';
    print '</tr>';

    if (empty($lines)) {
        print '<tr class="oddeven"><td colspan="6">'.$langs->trans('None').'</td></tr>';
    } else {
        foreach ($lines as $line) {
            $orderLink = '';
            if (!empty($line['fk_order'])) {
                $orderLink = '<a href="'.dol_buildpath('/fourn/commande/card.php?id='.(int) $line['fk_order'], 1).'">'.dol_escape_htmltag($line['order_ref']).'</a>';
            }

            $label = $line['product_ref'] ? $line['product_ref'].' - '.$line['product_label'] : $line['description'];

            print '<tr class="oddeven">';
            print '<td>'.$orderLink.'</td>';
            print '<td>'.dol_escape_htmltag($label).'</td>';
            print '<td class="right">'.price($line['qty'], 0, $langs, 0, 0, 0, '').'</td>';
            print '<td class="right">'.price($line['base_amount'], 0, $langs, 0, 0, 0, $conf->currency).'</td>';
            print '<td class="right">'.price($line['allocated_ht'], 0, $langs, 0, 0, 0, $conf->currency).'</td>';
            print '<td class="right">'.price($line['allocated_unit'], 0, $langs, 0, 0, 0, $conf->currency).'</td>';
            print '</tr>';
        }
    }

    print '</table>';
}

llxFooter();
$db->close();

/**
 * Build select options for supplier orders
 *
 * @param DoliDB $db
 * @return array
 */
function fetchSupplierOrdersOptions($db)
{
    global $conf, $langs;

    $sql = "SELECT c.rowid, c.ref, c.date_commande, c.fk_statut, s.nom as socname";
    $sql .= " FROM ".$db->prefix()."commande_fournisseur as c";
    $sql .= " LEFT JOIN ".$db->prefix()."societe as s ON s.rowid = c.fk_soc";
    $sql .= " WHERE c.entity IN (".getEntity('commande_fournisseur').")";
    $sql .= " AND c.fk_statut >= 0";
    $sql .= " ORDER BY c.date_commande DESC, c.rowid DESC";
    $sql .= " LIMIT 1000";

    $resql = $db->query($sql);
    if (!$resql) {
        setEventMessages($langs->trans('Error').' '.$db->lasterror(), null, 'errors');
        return array();
    }

    $options = array();
    while ($obj = $db->fetch_object($resql)) {
        $label = $obj->ref;
        if (!empty($obj->socname)) {
            $label .= ' - '.$obj->socname;
        }
        if (!empty($obj->date_commande)) {
            $label .= ' ('.dol_print_date($db->jdate($obj->date_commande), 'day').')';
        }
        $options[$obj->rowid] = $label;
    }

    return $options;
}
