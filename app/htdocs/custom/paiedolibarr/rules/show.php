<?php

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {
    
    if (!$id || $id <= 0) {
        header('Location: ./card.php?action=request&error=dalete_failed&id='.$id);
        exit;
    }

    $page  = GETPOST('page');

    $object->fetch($id);
    $error = $object->delete();
    
    // $error = 1;
    if ($error == 1) {
        header('Location: index.php?delete='.$id.'&page='.$page);
        exit;
    }
    else {      
        header('Location: card.php?delete=1&page='.$page);
        exit;
    }
}


if( ($id && empty($action)) || $action == "delete" ){
    
    // $h = 0;
    // $head = array();
    // $head[$h][0] = dol_buildpath("/paiedolibarr/card.php?id=".$id, 1);
    // $head[$h][1] = $langs->trans($modname);
    // $head[$h][2] = 'affichage';
    // $h++;
    // dol_fiche_head($head,'affichage',"",0,"logo@paiedolibarr");


    if($action == "delete"){
        print $form->formconfirm("card.php?id=".$id."&page=".$page,$langs->trans('Confirmation') , $langs->trans('paiedolibarrmsgconfirmdelet'),"confirm_delete", 'index.php?page='.$page, 0, 1);
    }

    
    $object->fetch($id);
    $item = $object;

    $linkback = '<a href="./index.php?page='.$page.'">'.$langs->trans("BackToList").'</a>';
    print $paiedolibarr->showNavigations($object, $linkback);
    
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" class="card_paiedolibarr">';

    print '<input type="hidden" name="confirm" value="no" id="confirm" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<table class="border tableforfield" width="100%">';
    print '<tbody>';

    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('Numero').'</td>';
        print '<td >';
        print nl2br(trim($item->numero));
        print '</td>';
    print '</tr>';

    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paieCode').'</td>';
        print '<td >';
        print nl2br(trim($item->code));
        print '</td>';
    print '</tr>';

    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paieDesignation').'</td>';
        print '<td >';
        print nl2br(trim($item->label));
        print '</td>';
    print '</tr>';

    print '<tr>';
        print '<td>'.$langs->trans('paieCategorie').'</td>';
        print '<td>';
        print $paies->rulescategory[$item->category];
        print '</td>';
    print '</tr>';

    print '<tr class="baseamount">';
        print '<td>'.$langs->trans('Type').'</td>';
        print '<td>';
        print '<span class="amounttype">';
            print '<span class="amounttype">';
            print $object->amounttypes[$item->amounttype];
            print '</span>';
        print '</span>';
        print '</td>';
    print '</tr>';

    if($item->amounttype == 'FIX'){
        print '<tr class="baseamountfix">';
            print '<td>'.$langs->trans('paieBase').'</td>';
            print '<td>';
            print $paiedolibarr->number_format($item->amount,2,',',' ');
            print '</td>';
        print '</tr>';
        print '<tr class="baseamountfix">';
            print '<td>'.$langs->trans('paieTaux').' (%)</td>';
            print '<td>';
            print $paiedolibarr->number_format($item->taux,2,',',' ');
            print '</td>';
        print '</tr>';

    } else {
        // print '<tr class="baseamountcalculated">';
        //     print '<td>'.$langs->trans('paieForm').'</td>';
        //     print '<td>';
        //     print trim($item->formule);
        //     print '</td>';
        // print '</tr>';

        
        print '<tr class="baseamountcalculated">';
            print '<td>'.$langs->trans('paieForm').': <b>'.$langs->trans('paieBase').'</b></td>';
            print '<td>';
            print '<pre class="">';
            print ($item->formule_base);
            print '</pre>';
            print '</td>';
        print '</tr>';
        
        print '<tr class="baseamountcalculated">';
            print '<td>'.$langs->trans('paieForm').': <b>'.$langs->trans('paieTaux').' (%)</b></td>';
            print '<td>';
            print '<pre class="">';
            print ($item->formule_taux);
            print '</pre>';
            print '</td>';
        print '</tr>';
    } 
    
    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paietexte_showdefault').'</td>';
        print '<td>';
        $chkd = ($item->showdefault) ? 'checked' : '';
        print '<input type="checkbox" '.$chkd.' disabled name="showdefault" value="'.$item->showdefault.'">';
        print '</td>';
    print '</tr>';
    
    print '<tr>';
        print '<td class="titlefieldcreate">'.$langs->trans('paietexte_showcumul').'</td>';
        print '<td>';
        $chkd = ($item->showcumul) ? 'checked' : '';
        print '<input type="checkbox" '.$chkd.' disabled name="showcumul" value="'.$item->showcumul.'">';
        print '</td>';
    print '</tr>';

    print '</tbody>';
    print '</table>';

    print '<br>';
    print '<br>';
    // Actions
    print '<table class="" width="100%">';
    print '<tr>';
        print '<td colspan="2" align="right" >';
            print '<a href="./card.php?id='.$id.'&action=edit" class="butAction">'.$langs->trans('Modify').'</a>';
            if($item->code != 'SALARY'){
                print '<a href="./card.php?id='.$id.'&action=delete" class=" butActionDelete">'.$langs->trans('Delete').'</a>';
            }
            print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
        print '</td>';
    print '</tr>';
    print '</table>';


    print '</form>';

}

?>
