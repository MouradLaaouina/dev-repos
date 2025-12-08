<?php

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {
    
    if (!$id || $id <= 0) {
        header('Location: ./card.php?action=request&error=dalete_failed&id='.$id);
        exit;
    }

    $page  = GETPOST('page');

    $object->fetch($id);

    $fk_salary = $object->fk_salary;
    
    $error = $object->delete();
    // $error = 1;
    if ($error == 1) {
        if($fk_salary){

            $salary = new Salary($db);
            $salary->fetch($fk_salary);

            $salary->array_options['options_paiedolibarr_fk_paie'] = '';
            $result = $salary->update($user);
        }

        setEventMessages('', $langs->trans("RecordDeleted"), 'mesgs');
        header('Location: list.php?delete='.$id.'&page='.$page);
        exit;
    }
    else {   
        setEventMessages('', $object->error, 'errors');
        header('Location: card.php?id='.$id.'&page='.$page);
        exit;
    }
}


if( $id && (empty($action) || $action == "delete" || $action == "createsalary" )){
    
    // $h = 0;
    // $head = array();
    // $head[$h][0] = dol_buildpath("/paiedolibarr/card.php?id=".$id, 1);
    // $head[$h][1] = $langs->trans($modname);
    // $head[$h][2] = 'affichage';
    // $h++;
    // dol_fiche_head($head,'affichage',"",0,"logo@paiedolibarr");
    $object->fetch($id);
    $item = $object;
    // d($object, 0);
    $periods = explode('-', $item->period);
    $periodyear = $periods[0] + 0;
    $periodmonth = $periods[1];
    $period = $langs->trans("Month".sprintf("%02d", $periodmonth))." ".$periodyear;

    if($action == "delete"){
        print $form->formconfirm("card.php?id=".$id."&page=".$page,$langs->trans('Confirmation') , $langs->trans('paiedolibarrmsgconfirmdelet'),"confirm_delete", 'list.php?page='.$page, 0, 1);
    }
    if($action == "createsalary"){

        $formquestion = array();
        
        $employe = new User($db);
        $employe->fetch($object->fk_user);

        $label = $langs->trans('BankAccount');
        $accountid = $conf->global->PAIEDOLIBARR_BANK_ACCOUNT_ID;
        $list = $form->select_comptes($accountid, 'accountid', 0, '', 2, '', 0, 'minwidth300 maxwidth300', 1);
        $formquestion[] = array('type' => 'other', 'name' => 'accountid', 'label' => $label, 'value' => $list);

        $label = $langs->trans('PaymentMode');
        $modpayment = $conf->global->PAIEDOLIBARR_MODEPAYMENT;
        $list = $form->select_types_paiements($modpayment, 'modpayment', '', 0, 0, 0, 0, 1, 'minwidth300 maxwidth300', 1);
        $formquestion[] = array('type' => 'other', 'name' => 'modpayment', 'label' => $label, 'value' => $list);

        print $form->formconfirm("card.php?id=".$id."&page=".$page, $langs->trans('Confirmation'), $langs->trans('Confirm_Createdsalary', $employe->getFullName($langs),  $period), 'confirm_createsalary', $formquestion,  "yes", 0, 250, 580);
    }

    // $paiedolibarr->fetchAll('','',0,0,' and rowid = '.$id);

    $linkback = '<a href="./list.php?periodyear='.$periodyear.'&periodmonth='.$periodmonth.'">'.$langs->trans("BackToList").'</a>';
    print $paiedolibarr->showNavigations($object, $linkback);

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" class="card_paiedolibarr">';

    print '<input type="hidden" name="confirm" value="no" id="confirm" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';


    print '<div class="fichecenter">';
        print '<div class="fichehalfleft">';

            print '<table class="border tableforfield" width="100%">';

            print '<tr>';
                print '<td>'.$langs->trans('paieofmonth').'</td>';
                print '<td >';
                print $period;
                print '</td>';
            print '</tr>';
            
            $userpay->fetch($item->fk_user);
            print '<tr>';
                print '<td id="titletdpaiedolibarr">'.$langs->trans('paiedolibarr_employe').'</td>';
                print '<td id="useroradherent">';
                print '<span id="users">';
                print $userpay->getNomUrl(1);
                print '</span>';
                print '</td>';
            print '</tr>';

            print '<tr>';
                print '<td class="titlefieldcreate">'.$langs->trans('paiedolibarr_ref').'</td>';
                print '<td >';
                print nl2br(trim($item->ref));
                print '</td>';
            print '</tr>';

            print '<tr>';
                print '<td class="titlefieldcreate">'.$langs->trans('paiesname').'</td>';
                print '<td >';
                print nl2br(trim($item->label));
                print '</td>';
            print '</tr>';

            print '<tr>';
                print '<td class="titlefieldcreate">'.$langs->trans('paiedolibarr_comment').'</td>';
                print '<td>';
                print nl2br($item->comment);
                print '</td>';
            print '</tr>';

            print '<tr>';
                print '<td class="titlefieldcreate">'.$langs->trans('PaymentMode').'</td>';
                print '<td >';
                $form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$item->id, $item->mode_reglement_id, 'none');
                print '</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefieldcreate">'.$langs->trans('paiesdatepay').'</td>';
                print '<td >';
                if($item->datepay) print dol_print_date($item->datepay, 'day');
                print '</td>';
            print '</tr>';

            // print '<tr>';
            //     print '<td class="bold">'.$langs->trans('paieSalaireBrut').'</td>';
            //     print '<td >';
            //     print $paiedolibarr->number_format($item->salairebrut, 2, '.',' ').' '.$conf->currency;
            //     print '</td>';
            // print '</tr>';

            // print '<tr>';
            //     print '<td class="bold">'.$langs->trans('paieSalaireNet').'</td>';
            //     print '<td >';
            //     print $paiedolibarr->number_format($item->salairenet, 2, '.',' ').' '.$conf->currency;
            //     print '</td>';
            // print '</tr>';

            // print '<tr>';
            //     print '<td class="bold">'.$langs->trans('paieNet_a_payer').'</td>';
            //     print '<td >';
            //     print $paiedolibarr->number_format($item->netapayer, 2, '.',' ').' '.$conf->currency;
            //     print '</td>';
            // print '</tr>';
             // Other attributes
            $cols = 2;
            include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

            print '</table>';

            print '<br>';

            print '<table class="valid centpercent">';
            print '<tr>';
                print '<td class="titlefieldcreate bold">'.$langs->trans('paieSalaireBrut').'</td>';
                print '<td >';
                print $paiedolibarr->number_format($item->salairebrut, 2, '.',' ').' '.$conf->currency;
                print '</td>';
            print '</tr>';

            // print '<tr>';
            //     print '<td class="bold">'.$langs->trans('retenus').'</td>';
            //     print '<td >';
            //     print $paiedolibarr->number_format($item->retenus, 2, '.',' ').' '.$conf->currency;
            //     print '</td>';
            // print '</tr>';

            print '<tr>';
                print '<td class="bold">'.$langs->trans('paieNet_imposable').'</td>';
                print '<td >';
                print $paiedolibarr->number_format($item->salairenet, 2, '.',' ').' '.$conf->currency;
                print '</td>';
            print '</tr>';

            print '<tr>';
                print '<td class="bold">'.$langs->trans('paieNet_a_payer').'</td>';
                print '<td >';
                print $paiedolibarr->number_format($item->netapayer, 2, '.',' ').' '.$conf->currency;
                print '</td>';
            print '</tr>';

           
            print '</table>';
        print '</div>';

        print '<div class="fichehalfright">';
            print '<table class="border" width="100%">';
                
                // print '<tr>';
                //     print '<td class="titlefieldcreate">'.$langs->trans('paiematricule').'</td>';
                //     print '<td>'.$item->matricule.'</td>';
                // print '</tr>';
                print '<tr>';
                    print '<td class="titlefieldcreate width200">'.$langs->trans('paiesituation_f').'</td>';
                    print '<td>';
                    print isset($object->situation_fs[$item->situation_f]) ? $object->situation_fs[$item->situation_f] : '';
                    print '</td>';
                print '</tr>';
                print '<tr>';
                    print '<td class="titlefieldcreate">'.$langs->trans('paienbrenfants').'</td>';
                    print '<td >';
                    if($item->nbrenfants) print $item->nbrenfants;
                    print '</td>';
                print '</tr>';
                print '<tr>';
                    print '<td class="titlefieldcreate width300">'.$langs->trans('paienbdaywork').'</td>';
                    print '<td >';
                    if($item->nbdaywork) print $item->nbdaywork.' '.$langs->trans('Days');
                    print '</td>';
                print '</tr>';
                print '<tr>';
                    print '<td class="titlefieldcreate width300">'.$langs->trans('paienbdayabsence').'</td>';
                    print '<td >';
                    if($item->nbdayabsence) print $item->nbdayabsence.' '.$langs->trans('Days');
                    print '</td>';
                print '</tr>';
                print '<tr>';
                    print '<td class="titlefieldcreate">'.$langs->trans('paiematricule').'</td>';
                    print '<td>';
                    if(isset($userpay->array_options['options_paiedolibarrmatricule'])){
                        print $userpay->array_options['options_paiedolibarrmatricule'];
                    }
                    // print $edituser;
                    print '</td>';
                print '</tr>';
                print '<tr>';
                    print '<td class="titlefieldcreate">'.$langs->trans('paiecin').'</td>';
                    print '<td>';
                    if(isset($userpay->array_options['options_paiedolibarrcin'])){
                        print $userpay->array_options['options_paiedolibarrcin'];
                    }
                    print '</td>';
                print '</tr>';

                print '<tr>';
                    print '<td class="titlefieldcreate">'.$langs->trans('paiecnss').'</td>';
                    print '<td>';
                    if(isset($userpay->array_options['options_paiedolibarrcnss'])){
                        print $userpay->array_options['options_paiedolibarrcnss'];
                    }
                    print '</td>';
                print '</tr>';
                print '<tr>';
                    print '<td class="titlefieldcreate">'.$langs->trans('paiecimr').'</td>';
                    print '<td>';
                    if(isset($userpay->array_options['options_paiedolibarrcimr'])){
                        print $userpay->array_options['options_paiedolibarrcimr'];
                    }
                    print '</td>';
                print '</tr>';
                print '<tr>';
                    print '<td class="titlefieldcreate">'.$langs->trans('paieDate_d_embauche').'</td>';
                    print '<td>';
                        if(!empty($userpay->dateemployment)) {
                            $yearincomp = $object->getYearsInCompany($item, $userpay->dateemployment);
                            print dol_print_date($userpay->dateemployment, 'day');
                            if($yearincomp) {
                                print ' <span class="opacitymedium">('.$yearincomp.' '.$langs->trans('Year').')</span>';
                            }
                        }
                    print '</td>';
                print '</tr>';

                print '<tr><td colspan="2"><hr></td></tr>';

                $res = $tmpuser->fetch($item->fk_author);
                print '<tr>';
                    print '<td id="titletdpaiedolibarr">'.$langs->trans('Author').'</td>';
                    print '<td>';
                    if($res) print $tmpuser->getNomUrl(1);
                    print '</td>';
                print '</tr>';

                $res = $tmpuser->fetch($item->fk_lastedit);
                print '<tr>';
                    print '<td id="titletdpaiedolibarr">'.$langs->trans('DateLastModification').'</td>';
                    print '<td>';
                    if($item->tms) print dol_print_date($item->tms, 'dayhour');
                    if($res) {
                        print '<span class="opacitymedium_">';
                        // print ' '.$langs->trans('By');
                        print ' - ';
                        print '</span>';
                        print ' '.$tmpuser->getNomUrl(1);
                    }
                    print '</td>';
                print '</tr>';

                if($object->fk_salary){
                    $salary = new Salary($db);
                    $res=$salary->fetch($object->fk_salary);

                    print '<tr>';
                        print '<td id="titletdpaiedolibarr">'.$langs->trans('Salary').'</td>';
                        print '<td>';
                        if($res) print $salary->getNomUrl(1).' '.$salary->label;
                        print '</td>';
                    print '</tr>';
                }


                print '<tr><td colspan="2"><hr></td></tr>';

            print '</table>';
        print '</div>';

        print '<div style="clear:both"></div>';
        print '</div>';
    print '</div>';

    print '<br>';
    print '<br>';
    // Actions

    $actionbut = '';
    $actionbut .=  '<table class="" width="100%">';
        $actionbut .=  '<tr>';
            $actionbut .=  '<td colspan="2" class="right" >';
            
                if($conf->global->PAIEDOLIBARR_ENABLE_ADDSALARY && !$object->fk_salary && ($user->rights->paiedolibarr->creer_salaire)){
                    $actionbut .=  '<a href="./card.php?id='.$id.'&action=createsalary" class="butAction badge-status1">'.$langs->trans('createsalary').'</a>';
                }
                $actionbut .=  '<a href="./card.php?id='.$id.'&action=edit" class="butAction">'.$langs->trans('Modify').'</a>';
                $actionbut .=  '<a href="./card.php?id='.$id.'&action=delete" class=" butActionDelete">'.$langs->trans('Delete').'</a>';
                $actionbut .=  '<a href="./list.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
                $actionbut .=  '<a style="float:right;margin-left:40px;"  href="./card.php?id='.$id.'&export=pdf" target="_blank" class="butAction badge-status4">'.$langs->trans('paiePrintFile').'</a>';
            $actionbut .=  '</td>';
        $actionbut .=  '</tr>';
    $actionbut .=  '</table>';

    print $actionbut;

    // print '<br><br>';
    print '<div class="paiecalculdesalaire">';
        print '<div class="titre">'.$langs->trans('paiecalculdesalaire').'</div>';

        $cts = '';
        $cts .= '<div class="div-table-responsive tablesalaire">';
            $cts .= '<table class="tagtable liste listwithfilterbefore bodytable" id="paielines" style="width:100%">'; 

                    $thead = '';
                    $thead .= '<thead>';
                        $thead .= '<tr class="liste_titre">';
                        $thead .= '<th class="left">'.$langs->trans('paieDesignation').'</th>'; 
                        $thead .= '<th class="paieemptyline"></th>'; 
                        // $thead .= '<th class="left">'.$langs->trans('Type').'</th>';
                        $thead .= '<th class="right">'.$langs->trans('paieBase').'</th>';
                        // $thead .= '<th class="right">'.$langs->trans('paieTaux').' (%)</th>';
                        $thead .= '<th class="right">'.$langs->trans('NbreOuTaux').'</th>';
                        $thead .= '<th class="right">'.$langs->trans('paieTotal').'</th>';
                        $thead .= '</tr>';
                    $thead .= '</thead>';

                    $cts .= $thead;

                    // $cts .= '<thead>';
                    //     $cts .= '<tr class="liste_titre">';
                    //     $cts .= '<th align="left">'.$langs->trans('paieDesignation').'</th>'; 
                    //     // $cts .= '<th class="paieemptyline"></th>'; 
                    //     $cts .= '<th align="right">'.$langs->trans('paieTotal').'</th>'; 
                    //     // $cts .= '<th class="paieemptyline"></th>'; 
                    //     // $cts .= '<th align="center">'.$langs->trans('paieCategorie').'</th>'; 
                    //     $cts .= '</tr>';
                    // $cts .= '</thead>';
                $cts .= '<tbody>';
                    $payrules = $object->getRulesOfPaie($item->rowid);
                    $i = 1;
                    $firstc = $firsto = 0;

                    $totbrut = $totcotisation = $totother = $totalpatr_G = $totalpatr_R = 0;

                    if($payrules){
                        foreach ($payrules as $key => $rule) {
                            $clas = '';
                            // if($rule->category == 'BASIQUE')

                            $clas = $key0 = $rule->category;



                            $cts .= '<tr class="oddeven '.$clas.'" data-id="'.$i.'">';

                            $cts .= '<td class="td_label left">'; 
                            $cts .= $rule->label; 
                            $cts .= '</td>';
                        
                            // $cts .= '<th class="paieemptyline"></th>';
                            
                            $cts .= '<td class="td_amount right">'; 
                            $cts .= $paiedolibarr->number_format($rule->amount, 2, '.',' '); 
                            $cts .= '</td>';

                            $cts .= '<td class="td_taux right">'; 
                            // $cts .= $paiedolibarr->number_format($rule->taux, 2, '.',' '); 
                            $cts .= ($rule->taux+0);
                            $cts .= '</td>';

                            $cts .= '<td class="td_total right">'; 
                            $cts .= $paiedolibarr->number_format($rule->total, 2, '.',' '); 
                            $cts .= '</td>';

                            // $cts .= '<th class="paieemptyline"></th>';
                            // $cts .= '<td class="td_category center">';
                            // $cts .= '<input type="hidden" name="payrules[new_'.$i.'][category]" size="6" value="'.$rule->category.'"/>';
                            // $cts .= $object->rulescategory[$rule->category]; 
                            // $cts .= '</td>';

                            $cts .= '</tr>';

                            
                            $i++;
                        }
                    }
                $cts .= '</tbody>';
            $cts .= '</table>'; 
        $cts .= '</div>';

        print $cts;

    print '</div>';

    print '<br>';

    print '<a style=""  href="./card.php?id='.$id.'&action=edit&rule=create" class="button">'.$langs->trans('NewPaieRule2').'</a>';

    print '<br>';
    
    // Actions
    print $actionbut;

    print '<br><br>';
    // print '<table class="" width="100%">';
    // print '<tr>';
    //     print '<td colspan="2 right" >';
    //         print '<a href="./card.php?id='.$id.'&action=edit" class="butAction">'.$langs->trans('Modify').'</a>';
    //         print '<a href="./card.php?id='.$id.'&action=delete" class="butActionDelete">'.$langs->trans('Delete').'</a>';
    //         print '<a href="./list.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
    //         print '<a style="float:right;margin-left:40px;"  href="./card.php?id='.$id.'&export=pdf" target="_blank" class="butAction">'.$langs->trans('paiePrintFile').'</a>';
    //     print '</td>';
    // print '</tr>';
    // print '</table>';
    
    
    print '</form>';

}

?>
