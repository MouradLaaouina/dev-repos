<?php

if ($action == 'update' && $request_method === 'POST') {

    $code = GETPOST('code', 'aZ09');
    if(!$code){
        $error++;
        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("paieCode")), null, 'errors');
        header('Location: ./card.php?id='.$id.'&action=edit');
        exit;
    }

    $data = array();
    $data['numero']      = $numero;
    $data['code']        = strtoupper($code);
    $data['label']       = addslashes(GETPOST('label'));
    $data['amounttype']  = trim(GETPOST('amounttype'));
    $data['category']    = GETPOST('category');

    if($data['amounttype'] == 'FIX'){
        $data['amount'] = price2num(GETPOST('amount', 'alpha'), '', 2);
        $data['taux']   = price2num(GETPOST('taux', 'alpha'), '', 2);
    }else{
        
        $formule_base = str_replace("%", "/100", trim(GETPOST('formule_base', 'none')));
        $formule_taux = str_replace("%", "/100", trim(GETPOST('formule_taux', 'none')));

        $data['formule_base'] = $db->escape($formule_base);
        $data['formule_taux'] = $db->escape($formule_taux);

        // $data['formule'] = str_replace("%", "/100", trim(GETPOST('formule')));
    }

    $data['showdefault'] = isset($_POST['showdefault']) ? 1 : 0;
    $data['showcumul'] = isset($_POST['showcumul']) ? 1 : 0;

    // d($data);
    $isvalid = $object->update($id, $data);
    $object->fetch($id);
    
    if ($isvalid > 0) {
        // header('Location: ./card.php?id='.$id);
        setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
        header('Location: ./card.php?id='.$id.'&action=edit');
        exit;
    } 
    else {
        setEventMessages($object->errors, null, 'errors');
        header('Location: ./card.php?id='. $id .'&action=edit');
        exit;
    }
}
if($action == "edit"){

    // $h = 0;
    // $head = array();
    // $head[$h][0] = dol_buildpath("/paiedolibarr/card.php?id=".$id."&action=edit", 1);
    // $head[$h][1] = $langs->trans($modname);
    // $head[$h][2] = 'affichage';
    // $h++;
    // dol_file_head($head,'affichage',"",0,"logo@paiedolibarr");

    // $object->fetchAll('','',0,0,' and rowid = '.$id);
    $object->fetch($id);
    $item = $object;

    $linkback = '<a href="./index.php?page='.$page.'">'.$langs->trans("BackToList").'</a>';
    // print $paiedolibarr->showNavigations($object, $linkback);

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="competcpaiedolibarr card_paiedolibarr">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<input type="hidden" name="entity" value="'.$object->entity.'" />';

    


    print '<table class="border" width="100%">';
    print '<tbody>';
    
    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('Numero').'</td>';
        print '<td ><input type="text" class="minwidth300 numeropaie" id="numero" name="numero" value="'.$item->numero.'"/>';
        print '</td>';
    print '</tr>';
    
    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paieCode').'</td>';
        print '<td ><input type="text" class="minwidth300 codepaie" id="code" name="code" value="'.$item->code.'"/>';
        print '</td>';
    print '</tr>';
    
    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paieDesignation').'</td>';
        print '<td ><input type="text" class="minwidth300" id="label" name="label" value="'.$item->label.'"/>';
        print '</td>';
    print '</tr>';

    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paieCategorie').'</td>';
        print '<td>';
        print $paies->selectCategories($item->category, 'category', 0, false);
        print '</td>';
    print '</tr>';

    print '<tr class="baseamount">';
        print '<td>'.$langs->trans('Type').'</td>';
        print '<td>';
        print '<span class="amounttype">';
        print $object->selectAmounttype($item->amounttype, 'amounttype', 0);
        print '</span>';
        print '</td>';
    print '</tr>';

    $discss = $item->amounttype == 'CALCULATED' ? '' : 'style="display:none;"';
    print '<tr class="baseamountfix" '.$discss.'>';
        print '<td>'.$langs->trans('paieBase').'</td>';
        print '<td>';
        print '<input type="number" step="0.01" class="minwidth50" id="amount" name="amount"  value="'.$paiedolibarr->number_format($item->amount,2,'.','').'"/>';
        print '</td>';
    print '</tr>';
    print '<tr class="baseamountfix" '.$discss.'>';
        print '<td>'.$langs->trans('paieTaux').' (%)</td>';
        print '<td>';
        print '<input type="number" step="0.01" class="minwidth50" id="taux" name="taux"  value="'.$paiedolibarr->number_format($item->taux,2,'.','').'"/>';
        print '</td>';
    print '</tr>';

    // print '<tr class="baseamountcalculated" '.$discss.'>';
    //     print '<td>'.$langs->trans('paieForm').'</td>';
    //     // print '<td>';
    //     // print '<input type="text" class="width100percent" id="formule" name="formule" placeholder="0.04 * (SALARY)" value="'.trim($item->formule).'"/>';
    //     // print '</td>';
    //     print '<td><textarea name="formule" id="formule" class="centpercent" rows="4" placeholder="0.04 * (SALARY)" wrap="soft">';
    //         print trim($item->formule);
    //     print '</textarea></td>';
    // print '</tr>';

    print '<tr class="baseamountcalculated" '.$discss.'>';
        print '<td>'.$langs->trans('paieForm').': <b>'.$langs->trans('paieBase').'</b></td>';
        print '<td><textarea name="formule_base" id="formule_base" class="centpercent" rows="8" placeholder="0.04 * (SALARY)">';
        print trim($item->formule_base);
        print '</textarea></td>';
    print '</tr>';
    
    print '<tr class="baseamountcalculated" '.$discss.'>';
        print '<td>'.$langs->trans('paieForm').': <b>'.$langs->trans('paieTaux').' (%)</b></td>';
        print '<td><textarea name="formule_taux" id="formule_taux" class="centpercent" rows="8" placeholder="0.04 * (SALARY)">';
        print trim($item->formule_taux);
        print '</textarea></td>';
    print '</tr>';
    
    // print '<tr class="baseamountcalculated" '.$discss.'>';
    //     print '<td>'.$langs->trans('paieForm').': <b>'.$langs->trans('Total').'</b></td>';
    //     print '<td><textarea name="formule" id="formule" class="centpercent" rows="4" placeholder="0.04 * (SALARY)">';
    //     print trim($item->formule);
    //     print '</textarea></td>';
    // print '</tr>';

    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paietexte_showdefault').'</td>';
        print '<td>';
        $chkd = ($item->showdefault) ? 'checked' : '';
        print '<input type="checkbox" '.$chkd.' name="showdefault" value="'.$item->showdefault.'">';
        print '</td>';
    print '</tr>';

    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paietexte_showcumul').'</td>';
        print '<td>';
        $chkd = ($item->showcumul) ? 'checked' : '';
        print '<input type="checkbox" '.$chkd.' name="showcumul" value="'.$item->showcumul.'">';
        print '</td>';
    print '</tr>';

    print '</tbody>';
    print '</table>';

    print '<br>';

    $butactions = '<table class="" width="100%">';
    $butactions .= '<tr>';
        $butactions .= '<td colspan="2" align="center">';
        $butactions .= '<input type="submit" value="'.$langs->trans('Validate').'" name="bouton" class="button" />';
        $butactions .= '<a href="./card.php?id='.$id.'&action=delete" class="butActionDelete">'.$langs->trans('Delete').'</a>';
        $butactions .= '<input type="button" class="button" value="'.$langs->trans('Cancel').'" onclick="javascript:history.go(-1)">';
        $butactions .= '</td>';
    $butactions .= '</tr>';
    $butactions .= '</table>';

    print $butactions;
    print '<br>';
    
    print '<div class="baseamountcalculated" '.$discss.'>';
        $filter = ' AND rowid < '.$id;
        $helpsubstit = $paies->getAvailableSubstitKey($filter);
        echo $helpsubstit;
        print '<br>';
        // Actions
        print $butactions;
    print '</div>';
   

    print '<br><br>';



    print '</form>';
        

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function() {
            $("input.datepicker55").datepicker({
                dateFormat: "dd/mm/yy"
            });
            $('#fk_user').select2();
        });
    </script>
    <?php
}

?>