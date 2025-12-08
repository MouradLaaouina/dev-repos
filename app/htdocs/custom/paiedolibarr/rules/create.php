<?php

if ($action == 'create' && $request_method === 'POST') {

    global $conf;

    $code = GETPOST('code', 'aZ09');
    if(!$code){
        $error++;
        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("paieCode")), null, 'errors');
        header('Location: ./card.php?action=add');
        exit;
    }

    $data = array();
    $data['numero']     = $numero;
    $data['code']       = strtoupper($code);
    $data['entity']     = $conf->entity;
    $data['label']      = addslashes(GETPOST('label'));
    $data['amounttype'] = trim(GETPOST('amounttype'));
    $data['category']   = GETPOST('category');
    
    if($data['amounttype'] == 'FIX'){
        $data['amount'] = price2num(GETPOST('amount', 'alpha'), '', 2);
        $data['taux']   = price2num(GETPOST('taux', 'alpha'), '', 2);
    }else{
        $data['formule_base'] = str_replace("%", "/100", trim(GETPOST('formule_base')));
        $data['formule_taux'] = str_replace("%", "/100", trim(GETPOST('formule_taux')));
        // $data['formule'] = str_replace("%", "/100", trim(GETPOST('formule')));
    }

    $data['showdefault'] = isset($_POST['showdefault']) ? 1 : 0;
    $data['showcumul'] = isset($_POST['showcumul']) ? 1 : 0;

    $avance = $object->create(1,$data);
    $object->fetch($avance);
   
    if ($avance > 0) {
        header('Location: ./card.php?id='. $avance);
        exit;
    }
    else {
        setEventMessages($object->errors, null, 'errors');
        header('Location: card.php?action=add');
        exit;
    }
}

if($action == "add"){

    global $conf;
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="competcpaiedolibarr card_paiedolibarr">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="create" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<table class="border" width="100%">';
    print '<tbody>';

    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('Numero').'</td>';
        print '<td ><input type="text" class="width75 numeropaie" id="numero" name="numero" value=""/>';
        print '</td>';
    print '</tr>';

    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paieCode').'</td>';
        print '<td ><input type="text" class="minwidth300 codepaie" id="code" name="code" value=""/>';
        print '</td>';
    print '</tr>';
    
    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paieDesignation').'</td>';
        print '<td ><input type="text" class="minwidth300" id="label" name="label" value=""/>';
        print '</td>';
    print '</tr>';

    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paieCategorie').'</td>';
        print '<td>';
        print $paies->selectCategories('', 'category', 0, false);
        print '</td>';
    print '</tr>';

    print '<tr class="baseamount">';
        print '<td>'.$langs->trans('Type').'</td>';
        print '<td>';
        print '<span class="amounttype">';
        print $object->selectAmounttype('CALCULATED', 'amounttype', 0);
        print '</span>';
        print '</td>';
    print '</tr>';

    $discss = '';
    print '<tr class="baseamountfix" '.$discss.'>';
        print '<td>'.$langs->trans('paieTotal').'</td>';
        print '<td>';
        print '<input type="number" step="0.01" class="minwidth50" id="amount" name="amount"  value="0"/>';
        print '</td>';
    print '</tr>';
    print '<tr class="baseamountfix" '.$discss.'>';
        print '<td>'.$langs->trans('paieTaux').' (%)</td>';
        print '<td>';
        $tauxval =  $item->taux ? trim($item->taux) : 100;
        print '<input type="number" step="0.01" class="minwidth50" id="taux" name="taux"  value="100"/>';
        print '</td>';
    print '</tr>';


    // print '<tr class="baseamountcalculated" '.$discss.'>';
    //     print '<td>'.$langs->trans('paieForm').'</td>';
    //     // print '<td>';
    //     // print '<input type="text" class="width100percent" id="formule" name="formule" placeholder="0.04 * (SALARY)" value=""/>';
    //     // print '</td>';
    //     print '<td><textarea name="formule" id="formule" class="centpercent" rows="4" placeholder="0.04 * (SALARY)">';
    //     print '</textarea></td>';
    // print '</tr>';

    print '<tr class="baseamountcalculated" '.$discss.'>';
        print '<td>'.$langs->trans('paieForm').': <b>'.$langs->trans('paieBase').'</b></td>';
        print '<td><textarea name="formule_base" id="formule_base" class="centpercent" rows="4" placeholder="0.04 * (SALARY)">';
        print '</textarea></td>';
    print '</tr>';
    
    print '<tr class="baseamountcalculated" '.$discss.'>';
        print '<td>'.$langs->trans('paieForm').': <b>'.$langs->trans('paieTaux').' (%)</b></td>';
        print '<td><textarea name="formule_taux" id="formule_taux" class="centpercent" rows="4" placeholder="0.04 * (SALARY)">';
        print '</textarea></td>';
    print '</tr>';

    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paietexte_showdefault').'</td>';
        print '<td>';
        print '<input type="checkbox" name="showdefault" checked value="1">';
        print '</td>';
    print '</tr>';

    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paietexte_showcumul').'</td>';
        print '<td>';
        print '<input type="checkbox" name="showcumul" value="0">';
        print '</td>';
    print '</tr>';

    print '</tbody>';
    print '</table>';

    $butactions = '<table class="" width="100%">';
    $butactions .= '<tr>';
        $butactions .= '<td colspan="2" align="center" >';
        $butactions .= '<br>';
        $butactions .= '<input type="submit" class="button" name="save" value="'.$langs->trans('Validate').'">';
        $butactions .= '<input type="button" class="button" value="'.$langs->trans('Cancel').'" onclick="javascript:history.go(-1)">';
    $butactions .= '</tr>';
    $butactions .= '</table>';

    print $butactions;
    
    print '<br>';

    print '<div class="baseamountcalculated" '.$discss.'>';
        $helpsubstit = $paies->getAvailableSubstitKey();
        echo $helpsubstit;
        print $butactions;
        print '<br>';
    print '</div>';
    
    


    print '</form>';

}