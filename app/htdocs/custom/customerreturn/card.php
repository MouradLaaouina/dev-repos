<?php
/**
 * Customer Return Card
 */

// Load Dolibarr environment
$res = 0;
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/customerreturn/class/customerreturn.class.php');
dol_include_once('/customerreturn/core/modules/customerreturn/modules_customerreturn.php');

// Initialize module conf if not set
if (!isset($conf->customerreturn) || !is_object($conf->customerreturn)) {
    $conf->customerreturn = new stdClass();
}
$conf->customerreturn->enabled = 1;
$conf->customerreturn->dir_output = DOL_DATA_ROOT.'/customerreturn';
$conf->customerreturn->dir_temp = DOL_DATA_ROOT.'/customerreturn/temp';

// Load translations
$langs->loadLangs(array('customerreturn@customerreturn', 'products', 'bills', 'companies', 'stocks', 'other'));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$lineid = GETPOSTINT('lineid');

// Initialize objects
$object = new CustomerReturn($db);
$form = new Form($db);
$formcompany = new FormCompany($db);
$formproduct = new FormProduct($db);
$formother = new FormOther($db);

$hookmanager->initHooks(array('customerreturncard', 'globalcard'));

// Load object
if ($id > 0 || !empty($ref)) {
    $result = $object->fetch($id, $ref);
    if ($result < 0) {
        setEventMessages($object->error, $object->errors, 'errors');
    }
}

// Security check - skip for create action
if ($action != 'create' && $id > 0) {
    $result = restrictedArea($user, 'customerreturn', $id, 'customerreturn');
}

// Permissions
$permissiontoread = $user->hasRight('customerreturn', 'read');
$permissiontoadd = $user->hasRight('customerreturn', 'write');
$permissiontovalidate = $user->hasRight('customerreturn', 'validate');
$permissiontodelete = $user->hasRight('customerreturn', 'delete');


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    // Create
    if ($action == 'add' && $permissiontoadd) {
        $object->fk_soc = GETPOSTINT('socid');
        $object->fk_expedition = GETPOSTINT('fk_expedition');
        $object->fk_deliveryman = GETPOSTINT('fk_deliveryman');
        $object->date_return = dol_mktime(0, 0, 0, GETPOSTINT('date_returnmonth'), GETPOSTINT('date_returnday'), GETPOSTINT('date_returnyear'));
        $object->note_private = GETPOST('note_private', 'restricthtml');
        $object->note_public = GETPOST('note_public', 'restricthtml');

        if (empty($object->fk_soc)) {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Customer')), null, 'errors');
            $action = 'create';
        } else {
            $result = $object->create($user);
            if ($result > 0) {
                header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
                exit;
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
                $action = 'create';
            }
        }
    }

    // Update
    if ($action == 'update' && $permissiontoadd) {
        $object->fk_soc = GETPOSTINT('socid');
        $object->fk_expedition = GETPOSTINT('fk_expedition');
        $object->fk_deliveryman = GETPOSTINT('fk_deliveryman');
        $object->date_return = dol_mktime(0, 0, 0, GETPOSTINT('date_returnmonth'), GETPOSTINT('date_returnday'), GETPOSTINT('date_returnyear'));
        $object->note_private = GETPOST('note_private', 'restricthtml');
        $object->note_public = GETPOST('note_public', 'restricthtml');

        $result = $object->update($user);
        if ($result > 0) {
            header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
            exit;
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }

    // Delete
    if ($action == 'confirm_delete' && $confirm == 'yes' && $permissiontodelete) {
        $result = $object->delete($user);
        if ($result > 0) {
            header("Location: ".dol_buildpath('/custom/customerreturn/list.php', 1));
            exit;
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }

    // Validate
    if ($action == 'confirm_validate' && $confirm == 'yes' && $permissiontovalidate) {
        $result = $object->validate($user);
        if ($result > 0) {
            setEventMessages($langs->trans('ReturnValidated'), null, 'mesgs');
            header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
            exit;
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }

    // Add line
    if ($action == 'addline' && $permissiontoadd) {
        $fk_product = GETPOSTINT('fk_product');
        $qty = price2num(GETPOST('qty', 'alpha'));
        $subprice = price2num(GETPOST('subprice', 'alpha'));
        $tva_tx = price2num(GETPOST('tva_tx', 'alpha'));
        $fk_entrepot = GETPOSTINT('fk_entrepot');
        $label = GETPOST('label', 'alpha');
        $description = GETPOST('description', 'restricthtml');
        $reason = GETPOST('reason', 'alpha');
        $batch = GETPOST('batch', 'alpha');

        // Get product info if not set
        if ($fk_product > 0 && (empty($subprice) || $subprice == 0)) {
            $product = new Product($db);
            $product->fetch($fk_product);
            $subprice = $product->price;
            $tva_tx = $product->tva_tx;
            if (empty($label)) {
                $label = $product->label;
            }
        }

        if ($qty <= 0) {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Qty')), null, 'errors');
        } else {
            $result = $object->addLine($fk_product, $qty, $subprice, $tva_tx, $fk_entrepot, $label, $description, $reason, $batch);
            if ($result > 0) {
                header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
                exit;
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }
    }

    // Update line
    if ($action == 'updateline' && $permissiontoadd) {
        $lineid = GETPOSTINT('lineid');
        $qty = price2num(GETPOST('qty', 'alpha'));
        $subprice = price2num(GETPOST('subprice', 'alpha'));
        $tva_tx = price2num(GETPOST('tva_tx', 'alpha'));
        $fk_entrepot = GETPOSTINT('fk_entrepot');
        $label = GETPOST('label', 'alpha');
        $description = GETPOST('description', 'restricthtml');
        $reason = GETPOST('reason', 'alpha');
        $batch = GETPOST('batch', 'alpha');

        $result = $object->updateLine($lineid, $qty, $subprice, $tva_tx, $fk_entrepot, $label, $description, $reason, $batch);
        if ($result > 0) {
            header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
            exit;
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }

    // Delete line
    if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontoadd) {
        $result = $object->deleteLine($lineid);
        if ($result > 0) {
            header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
            exit;
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }

    // Build PDF document
    if ($action == 'builddoc' && $permissiontoread) {
        $outputlangs = $langs;
        $newlang = '';

        if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
            $newlang = GETPOST('lang_id', 'aZ09');
        }
        if (!empty($newlang)) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($newlang);
        }

        $model = GETPOST('model', 'alpha') ? GETPOST('model', 'alpha') : 'standard_customerreturn';

        $result = $object->generateDocument($model, $outputlangs);
        if ($result <= 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        } else {
            setEventMessages($langs->trans('PDFGenerated'), null, 'mesgs');
        }
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }

    // Remove PDF file
    if ($action == 'remove_file' && $permissiontoadd) {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $upload_dir = $conf->customerreturn->dir_output;
        $file = $upload_dir.'/'.GETPOST('file', 'alpha');
        $ret = dol_delete_file($file, 0, 0, 0, $object);
        if ($ret) {
            setEventMessages($langs->trans("FileWasRemoved", GETPOST('file', 'alpha')), null, 'mesgs');
        } else {
            setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file', 'alpha')), null, 'errors');
        }
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }
}


/*
 * View
 */

$title = $langs->trans('CustomerReturn');
llxHeader('', $title);

// Create form
if ($action == 'create') {
    print load_fiche_titre($langs->trans('NewCustomerReturn'), '', 'dollyrevert');

    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';

    print dol_get_fiche_head(array(), '');

    print '<table class="border centpercent tableforfieldcreate">';

    // Customer
    print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Customer').'</td><td>';
    print $form->select_company(GETPOSTINT('socid'), 'socid', '', 'SelectThirdParty', 1, 0, null, 0, 'minwidth300');
    print '</td></tr>';

    // Deliveryman
    print '<tr><td>'.$langs->trans('Deliveryman').'</td><td>';
    print $form->select_dolusers(GETPOSTINT('fk_deliveryman'), 'fk_deliveryman', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200');
    print '</td></tr>';

    // Expedition (optional ID)
    print '<tr><td>'.$langs->trans('Shipment').'</td><td>';
    print '<input type="text" name="fk_expedition" value="'.GETPOSTINT('fk_expedition').'" class="flat minwidth100">';
    print ' <span class="opacitymedium">('.$langs->trans('Optional').')</span>';
    print '</td></tr>';

    // Date return
    print '<tr><td>'.$langs->trans('DateReturn').'</td><td>';
    print $form->selectDate(dol_now(), 'date_return', 0, 0, 0, '', 1, 1);
    print '</td></tr>';

    // Note public
    print '<tr><td class="tdtop">'.$langs->trans('NotePublic').'</td><td>';
    $doleditor = new DolEditor('note_public', GETPOST('note_public', 'restricthtml'), '', 100, 'dolibarr_notes', 'In', false, true, true, ROWS_3, '90%');
    print $doleditor->Create(1);
    print '</td></tr>';

    // Note private
    print '<tr><td class="tdtop">'.$langs->trans('NotePrivate').'</td><td>';
    $doleditor = new DolEditor('note_private', GETPOST('note_private', 'restricthtml'), '', 100, 'dolibarr_notes', 'In', false, true, true, ROWS_3, '90%');
    print $doleditor->Create(1);
    print '</td></tr>';

    print '</table>';

    print dol_get_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans('Create').'">';
    print ' &nbsp; ';
    print '<input type="button" class="button button-cancel" value="'.$langs->trans('Cancel').'" onclick="history.go(-1)">';
    print '</div>';

    print '</form>';
}

// View/Edit card
if ($object->id > 0) {
    $head = array();
    $head[0][0] = $_SERVER['PHP_SELF'].'?id='.$object->id;
    $head[0][1] = $langs->trans('Card');
    $head[0][2] = 'card';

    print dol_get_fiche_head($head, 'card', $langs->trans('CustomerReturn'), -1, 'dollyrevert');

    // Confirm delete
    if ($action == 'delete') {
        print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('DeleteReturn'), $langs->trans('ConfirmDeleteReturn'), 'confirm_delete', '', 0, 1);
    }

    // Confirm validate
    if ($action == 'validate') {
        $text = $langs->trans('ConfirmValidateReturn');
        if (getDolGlobalString('CUSTOMERRETURN_AUTO_CREDIT_NOTE')) {
            $text .= '<br><br><strong>'.$langs->trans('CreditNoteWillBeCreated').'</strong>';
        }
        print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('ValidateReturn'), $text, 'confirm_validate', '', 0, 1);
    }

    // Confirm delete line
    if ($action == 'deleteline') {
        print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
    }

    // Object card
    $linkback = '<a href="'.dol_buildpath('/custom/customerreturn/list.php', 1).'">'.$langs->trans('BackToList').'</a>';

    $morehtmlref = '';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent tableforfield">';

    // Customer
    print '<tr><td class="titlefield">'.$langs->trans('Customer').'</td><td>';
    $societe = new Societe($db);
    $societe->fetch($object->fk_soc);
    print $societe->getNomUrl(1);
    print '</td></tr>';

    // Deliveryman
    print '<tr><td>'.$langs->trans('Deliveryman').'</td><td>';
    if ($object->fk_deliveryman > 0) {
        $deliveryman = new User($db);
        $deliveryman->fetch($object->fk_deliveryman);
        print $deliveryman->getNomUrl(1);
    } else {
        print '-';
    }
    print '</td></tr>';

    // Date return
    print '<tr><td>'.$langs->trans('DateReturn').'</td><td>';
    print dol_print_date($object->date_return, 'day');
    print '</td></tr>';

    // Status
    print '<tr><td>'.$langs->trans('Status').'</td><td>';
    print $object->getLibStatut(4);
    print '</td></tr>';

    // Credit note link
    if ($object->fk_facture_avoir > 0) {
        require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
        $facture = new Facture($db);
        $facture->fetch($object->fk_facture_avoir);
        print '<tr><td>'.$langs->trans('CreditNote').'</td><td>';
        print $facture->getNomUrl(1);
        print '</td></tr>';
    }

    print '</table>';

    print '</div>';
    print '<div class="fichehalfright">';

    print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent tableforfield">';

    // Total HT
    print '<tr><td class="titlefield">'.$langs->trans('TotalHT').'</td><td>';
    print price($object->total_ht, 0, $langs, 1, -1, -1, $conf->currency);
    print '</td></tr>';

    // Total VAT
    print '<tr><td>'.$langs->trans('TotalVAT').'</td><td>';
    print price($object->total_tva, 0, $langs, 1, -1, -1, $conf->currency);
    print '</td></tr>';

    // Total TTC
    print '<tr><td>'.$langs->trans('TotalTTC').'</td><td>';
    print '<strong>'.price($object->total_ttc, 0, $langs, 1, -1, -1, $conf->currency).'</strong>';
    print '</td></tr>';

    print '</table>';

    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();

    /*
     * Lines
     */
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder noshadow centpercent">';

    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans('Product').'</td>';
    print '<td>'.$langs->trans('Label').'</td>';
    print '<td class="right">'.$langs->trans('Qty').'</td>';
    print '<td class="right">'.$langs->trans('UnitPriceHT').'</td>';
    print '<td class="right">'.$langs->trans('VAT').' %</td>';
    print '<td class="right">'.$langs->trans('TotalHT').'</td>';
    print '<td>'.$langs->trans('Warehouse').'</td>';
    print '<td>'.$langs->trans('Reason').'</td>';
    print '<td class="center" width="80">'.$langs->trans('Action').'</td>';
    print '</tr>';

    // Existing lines
    if (!empty($object->lines)) {
        foreach ($object->lines as $line) {
            // Edit line mode
            if ($action == 'editline' && $lineid == $line->id && $permissiontoadd) {
                print '<tr class="oddeven">';
                print '<input type="hidden" name="action" value="updateline">';
                print '<input type="hidden" name="lineid" value="'.$line->id.'">';

                // Product (readonly)
                print '<td>';
                if ($line->fk_product > 0) {
                    $product = new Product($db);
                    $product->fetch($line->fk_product);
                    print $product->getNomUrl(1);
                }
                print '</td>';

                // Label
                print '<td><input type="text" name="label" value="'.dol_escape_htmltag($line->label).'" class="flat maxwidth200"></td>';

                // Qty
                print '<td class="right"><input type="text" name="qty" value="'.$line->qty.'" class="flat width50 right"></td>';

                // Unit price
                print '<td class="right"><input type="text" name="subprice" value="'.price2num($line->subprice).'" class="flat width75 right"></td>';

                // VAT
                print '<td class="right"><input type="text" name="tva_tx" value="'.price2num($line->tva_tx).'" class="flat width50 right"></td>';

                // Total (calculated)
                print '<td class="right">'.price($line->total_ht).'</td>';

                // Warehouse
                print '<td>';
                print $formproduct->selectWarehouses($line->fk_entrepot, 'fk_entrepot', '', 1, 0, 0, '', 0, 0, array(), 'minwidth100');
                print '</td>';

                // Reason
                print '<td><input type="text" name="reason" value="'.dol_escape_htmltag($line->reason).'" class="flat maxwidth150"></td>';

                // Actions
                print '<td class="center nowraponall">';
                print '<input type="submit" class="button buttongen marginrightonly button-save" value="'.$langs->trans('Save').'">';
                print '<a class="button buttongen button-cancel" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">'.$langs->trans('Cancel').'</a>';
                print '</td>';

                print '</tr>';
            } else {
                // Normal display
                print '<tr class="oddeven">';

                // Product
                print '<td>';
                if ($line->fk_product > 0) {
                    $product = new Product($db);
                    $product->fetch($line->fk_product);
                    print $product->getNomUrl(1);
                }
                print '</td>';

                // Label
                print '<td>'.dol_escape_htmltag($line->label).'</td>';

                // Qty
                print '<td class="right">'.$line->qty.'</td>';

                // Unit price
                print '<td class="right">'.price($line->subprice).'</td>';

                // VAT
                print '<td class="right">'.vatrate($line->tva_tx).'</td>';

                // Total HT
                print '<td class="right">'.price($line->total_ht).'</td>';

                // Warehouse
                print '<td>';
                if ($line->fk_entrepot > 0) {
                    require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
                    $warehouse = new Entrepot($db);
                    $warehouse->fetch($line->fk_entrepot);
                    print $warehouse->getNomUrl(1);
                }
                print '</td>';

                // Reason
                print '<td>'.dol_escape_htmltag($line->reason).'</td>';

                // Actions
                print '<td class="center nowraponall">';
                if ($object->statut == CustomerReturn::STATUS_DRAFT && $permissiontoadd) {
                    print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=editline&lineid='.$line->id.'&token='.newToken().'">'.img_edit().'</a>';
                    print ' <a class="reposition" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=deleteline&lineid='.$line->id.'&token='.newToken().'">'.img_delete().'</a>';
                }
                print '</td>';

                print '</tr>';
            }
        }
    }

    // Add new line form (only if draft)
    if ($object->statut == CustomerReturn::STATUS_DRAFT && $permissiontoadd && $action != 'editline') {
        print '<tr class="oddeven">';
        print '<input type="hidden" name="action" value="addline">';

        // Product
        print '<td>';
        print $form->select_produits(GETPOSTINT('fk_product'), 'fk_product', '', 0, 0, -1, 2, '', 0, array(), 0, '1', 0, 'minwidth200', 0, '', null, 1);
        print '</td>';

        // Label
        print '<td><input type="text" name="label" value="'.GETPOST('label', 'alpha').'" class="flat maxwidth200" placeholder="'.$langs->trans('Label').'"></td>';

        // Qty
        print '<td class="right"><input type="text" name="qty" value="'.(GETPOST('qty') ? GETPOST('qty') : 1).'" class="flat width50 right"></td>';

        // Unit price
        print '<td class="right"><input type="text" name="subprice" value="'.GETPOST('subprice').'" class="flat width75 right" placeholder="'.$langs->trans('Price').'"></td>';

        // VAT
        print '<td class="right"><input type="text" name="tva_tx" value="'.(GETPOST('tva_tx') ? GETPOST('tva_tx') : '20').'" class="flat width50 right"></td>';

        // Total (empty for new line)
        print '<td class="right"></td>';

        // Warehouse
        print '<td>';
        print $formproduct->selectWarehouses(GETPOSTINT('fk_entrepot'), 'fk_entrepot', '', 1, 0, 0, '', 0, 0, array(), 'minwidth100');
        print '</td>';

        // Reason
        print '<td><input type="text" name="reason" value="'.GETPOST('reason', 'alpha').'" class="flat maxwidth150" placeholder="'.$langs->trans('Reason').'"></td>';

        // Add button
        print '<td class="center">';
        print '<input type="submit" class="button buttongen button-add" value="'.$langs->trans('Add').'">';
        print '</td>';

        print '</tr>';
    }

    // Empty message
    if (empty($object->lines) && $action != 'editline') {
        print '<tr class="oddeven"><td colspan="9" class="opacitymedium">'.$langs->trans('NoLines').'</td></tr>';
    }

    print '</table>';
    print '</div>';
    print '</form>';

    print '<div class="clearboth"></div>';

    /*
     * Buttons
     */
    print '<div class="tabsAction">';

    // Validate
    if ($object->statut == CustomerReturn::STATUS_DRAFT) {
        if ($permissiontovalidate) {
            if (!empty($object->lines) && count($object->lines) > 0) {
                print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate&token='.newToken().'">'.$langs->trans('Validate').'</a>';
            } else {
                print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans('AddLinesFirst').'">'.$langs->trans('Validate').'</a>';
            }
        }
    }

    // Delete
    if ($object->statut == CustomerReturn::STATUS_DRAFT && $permissiontodelete) {
        print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans('Delete').'</a>';
    }

    print '</div>';

    /*
     * Documents generated
     */
    $objref = dol_sanitizeFileName($object->ref);

    // Define output directory
    if (!empty($conf->customerreturn->dir_output)) {
        $diroutput = $conf->customerreturn->dir_output;
    } else {
        $diroutput = DOL_DATA_ROOT.'/customerreturn';
    }
    $filedir = $diroutput.'/'.$objref;

    // Create directory if not exists
    if (!is_dir($diroutput)) {
        dol_mkdir($diroutput);
    }
    if (!is_dir($filedir)) {
        dol_mkdir($filedir);
    }

    // Get list of PDF files
    $filearray = dol_dir_list($filedir, "files", 0, '\.pdf$', '', 'date', SORT_DESC);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="div-table-responsive-no-min">';

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td colspan="4">'.$langs->trans("Documents").'</td>';
    print '</tr>';

    // Generate PDF button
    if ($permissiontoread) {
        print '<tr class="oddeven">';
        print '<td colspan="4">';
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" style="display:inline;">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="builddoc">';
        print '<input type="hidden" name="model" value="standard_customerreturn">';
        print '<input type="submit" class="button" value="'.$langs->trans("Generate").' PDF">';
        print '</form>';
        print '</td>';
        print '</tr>';
    }

    // List existing PDF files
    if (!empty($filearray)) {
        foreach ($filearray as $file) {
            $relativepath = $objref.'/'.$file['name'];
            $modulepart = 'customerreturn';

            print '<tr class="oddeven">';
            print '<td class="minwidth200">';
            print '<a class="documentdownload" href="'.dol_buildpath('/custom/customerreturn/document.php', 1).'?file='.urlencode($relativepath).'&action=download">';
            print img_mime($file['name'], $langs->trans("File").': '.$file['name']);
            print ' '.$file['name'];
            print '</a>';
            print '</td>';
            print '<td class="right nowraponall">'.dol_print_size($file['size'], 1).'</td>';
            print '<td class="right nowraponall">'.dol_print_date($file['date'], 'dayhour').'</td>';
            print '<td class="right">';
            if ($permissiontoadd) {
                print '<a class="deletefilelink" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=remove_file&token='.newToken().'&file='.urlencode($relativepath).'">';
                print img_picto($langs->trans("Delete"), 'delete');
                print '</a>';
            }
            print '</td>';
            print '</tr>';
        }
    } else {
        print '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("NoDocuments").'</td></tr>';
    }

    print '</table>';
    print '</div>';
    print '</div>';

    // Right side - PDF Preview
    print '<div class="fichehalfright">';

    if (!empty($filearray)) {
        $lastfile = $filearray[0]; // Most recent file
        $relativepath = $objref.'/'.$lastfile['name'];
        $modulepart = 'customerreturn';

        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("Preview").' - '.$lastfile['name'].'</td>';
        print '</tr>';
        print '<tr class="oddeven">';
        print '<td class="center">';

        // PDF viewer iframe - use local document.php for custom module
        $urlfile = dol_buildpath('/custom/customerreturn/document.php', 1).'?file='.urlencode($relativepath);

        // Check browser support for PDF
        print '<div style="width:100%; min-height:500px;">';
        print '<iframe src="'.$urlfile.'" style="width:100%; height:500px; border:1px solid #ccc;"></iframe>';
        print '</div>';

        print '<div class="margintoponlyshort">';
        print '<a class="button" href="'.$urlfile.'" target="_blank">'.$langs->trans("OpenInNewTab").'</a>';
        print '</div>';

        print '</td>';
        print '</tr>';
        print '</table>';
        print '</div>';
    } else {
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("Preview").'</td>';
        print '</tr>';
        print '<tr class="oddeven">';
        print '<td class="center opacitymedium" style="padding: 20px;">';
        print $langs->trans("NoDocuments");
        print '<br><br>';
        print $langs->trans("ClickGenerateToCreatePDF");
        print '</td>';
        print '</tr>';
        print '</table>';
        print '</div>';
    }

    print '</div>';
    print '</div>';
}

// If nothing to show
if ($action != 'create' && !($object->id > 0)) {
    print '<div class="error">'.$langs->trans('RecordNotFound').'</div>';
}

llxFooter();
$db->close();
