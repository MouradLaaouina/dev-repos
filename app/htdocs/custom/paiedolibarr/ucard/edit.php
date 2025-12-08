<?php

if ($action == 'update' && $request_method === 'POST') {

    // $periodyear     = GETPOST('periodyear','int');
    // $periodmonth    = GETPOST('periodmonth','int');
    // $period = $periodyear.'-'.sprintf("%02d", $periodmonth).'-01';



    $payrules      = GETPOST('payrules', 'array');
    $deletedrules  = GETPOST('deleted_rules', 'array');

    $payrules = is_array($payrules) ? $payrules : array();
    $deletedrules = is_array($deletedrules) ? $deletedrules : array();

    $totbrut = $totcotisation = 0;

    // d($payrules);

    $data = array();

    if($payrules || $deletedrules){
        $otherdata = $object->editcalculatePaieRules($payrules, $deletedrules);
    }

    // $data = $object->getNetBrutNetAPayer($id, $substitutionarray);
    

    $data['ref']        = $db->escape(GETPOST('ref', 'alphanohtml'));
    $data['label']      = $db->escape(GETPOST('label', 'alphanohtml'));
    $data['comment']    = $db->escape(GETPOST('comment', 'alphanohtml'));

    $data['situation_f']= $db->escape(GETPOST('situation_f', 'alphanohtml'));
    $data['nbrenfants'] = GETPOST('nbrenfants', 'int');
    $data['nbdayabsence'] = GETPOST('nbdayabsence', 'int');
    $data['nbdaywork'] = GETPOST('nbdaywork', 'int');
    // $data['matricule']  = $db->escape(GETPOST('matricule', 'alphanohtml'));
    $data['certificat']  = $db->escape(GETPOST('certificat', 'alphanohtml'));
    $data['worksite']  = $db->escape(GETPOST('worksite', 'alphanohtml'));

    $data['datepay'] = $db->idate($datepay);
    $data['mode_reglement_id'] = GETPOST('mode_reglement_id','int');
    $data['fk_lastedit'] = $user->id;

    $object->fetch($id);
    $ret = $extrafields->setOptionalsFromPost(null, $object);
    $isvalid = $object->update($id, $data);
    
    if ($isvalid > 0) {

        if($object->fk_salary){

            $salary = new Salary($db);
            $salary->fetch($object->fk_salary);

            $salary->amount = price2num($object->netapayer);
            $salary->datesp = $db->jdate($object->period);
            $salary->dateep = $datepay;

            $result = $salary->update($user);
        }

        setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
        header('Location: ./card.php?id='. $id );
        exit;
    } 
    else {
        setEventMessages('', $object->error, 'errors');
        if($object->error)
            header('Location: ./card.php?id='. $id);
        else
            header('Location: ./card.php?id='. $id .'&action=edit');
        exit;
    }
}


if($action == "edit"){
    $actionbut = '';
    // $object->fetchAll('','',0,0,' and rowid = '.$id);
    $object->fetch($id);
    $item = $object;
    
    $linkback = '<a href="./list.php?page='.$page.'">'.$langs->trans("BackToList").'</a>';
    print $paiedolibarr->showNavigations($object, $linkback);

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="competcpaiedolibarr card_paiedolibarr">';
    print '<input type="hidden" name="token" value="'.(isset($_SESSION['newtoken']) ? $_SESSION['newtoken'] : '').'">';
    print '<input type="hidden" name="action" value="update" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<input type="hidden" name="entity" value="'.$item->entity.'" />';

    $periods = explode('-', $item->period);
    $periodyear = $periods[0] + 0;
    $periodmonth = $periods[1];


    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';

        print '<table class="border" width="100%">';

        $periods = explode('-', $item->period);
        $periodyear = $periods[0] + 0;
        $periodmonth = $periods[1];
        $period = $langs->trans("Month".sprintf("%02d", $periodmonth))." ".$periodyear;

        print '<tr>';
            print '<td class="titlefieldcreate">'.$langs->trans('paieofmonth').'</td>';
            print '<td >';
            print $period;
            print '</td>';
        print '</tr>';
        
        $userpay->fetch($item->fk_user);

        print '<tr>';
            print '<td class="titlefieldcreate" id="titletdpaiedolibarr">'.$langs->trans('paiedolibarr_employe').'</td>';
            print '<td id="useroradherent">';
            print '<span id="users">';
            print $userpay->getNomUrl(1);
            print '</span>';
            print '</td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefieldcreate">'.$langs->trans('paiedolibarr_ref').'</td>';
            print '<td ><input type="text" class="quatrevingtseizepercent minwidth300" id="ref" name="ref" value="'.$item->ref.'" autocomplete="off"/>';
            print '</td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefieldcreate">'.$langs->trans('paiesname').'</td>';
            print '<td >';
            print '<input value="'.trim($item->label).'" type="text" class="quatrevingtseizepercent minwidth300" id="label" name="label"  autocomplete="off"/>';
            print '</td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefieldcreate">'.$langs->trans('paiedolibarr_comment').'</td>';
            print '<td><textarea name="comment" id="comment" class="centpercent" rows="5" wrap="soft">';
            print $item->comment;
            print '</textarea></td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefieldcreate">'.$langs->trans('PaymentMode').'</td>';
            print '<td >';
            $form->select_types_paiements($item->mode_reglement_id, 'mode_reglement_id');
            print '</td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefieldcreate">'.$langs->trans('paiesdatepay').'</td>';
            print '<td >';
            $now = dol_now();

            print $form->selectDate($object->datepay ? $object->datepay : $datepay, 'datepay', 0, 0, 0, '', 1, 1);
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

        // Other options
        $parameters = array();
        $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (empty($reshook)) {
            print $object->showOptionals($extrafields, 'create');
        }

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

    $linku = dol_buildpath('/user/card.php?id='.$item->fk_user.'&action=edit',1);
    $edituser = '';
    $edituser .= '<span class="marginleftonly">';
    $edituser .= '<a class="editfielda" target="_blank" href="'.$linku.'">';
    $edituser .= img_picto('Edit', 'edit', '');
    $edituser .= '</a>';
    $edituser .= '</span>';

    print '<div class="fichehalfright">';
        print '<table class="border" width="100%">';

            // print '<tr>';
            //     print '<td class="titlefieldcreate">'.$langs->trans('paiematricule').'</td>';
            //     print '<td ><input type="text" size="8" class="" id="matricule" name="matricule" value="'.$item->matricule.'" autocomplete="off"/>';
            //     print '</td>';
            // print '</tr>';
            print '<tr>';
                print '<td class="titlefieldcreate width200">'.$langs->trans('paiesituation_f').'</td>';
                print '<td>';
                $slct = '<select class="" name="situation_f" id="situation_f">';
                    // $slct .= '<option value="0">&nbsp;</option>';
                    $slct .= '<option value="C">'.$langs->trans('paieCelibataire').'</option>';
                    $slct .= '<option value="M">'.$langs->trans('paieMarie').'</option>';
                    $slct .= '<option value="D">'.$langs->trans('paieDivorce').'</option>';
                    // $slct .= '<option value="CPLE">'.$langs->trans('paieCouple').'</option>';
                $slct .= '</select>';
                $slct = str_replace('value="'.$item->situation_f.'"', 'value="'.$item->situation_f.'" selected', $slct);
                print $slct;
                print '</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefieldcreate">'.$langs->trans('paienbrenfants').'</td>';
                print '<td ><input type="number" size="8" class="" id="nbrenfants" name="nbrenfants" value="'.$item->nbrenfants.'" autocomplete="off"/>';
                print '</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefieldcreate">'.$langs->trans('paienbdaywork').'</td>';
                print '<td ><input type="number" size="8" class="" id="nbdaywork" name="nbdaywork" value="'.$item->nbdaywork.'" autocomplete="off"/>';
                print '</td>';
            print '</tr>';
            print '<tr>';
                print '<td class="titlefieldcreate">'.$langs->trans('paienbdayabsence').'</td>';
                print '<td ><input type="number" size="8" class="" id="nbdayabsence" name="nbdayabsence" value="'.$item->nbdayabsence.'" autocomplete="off"/>';
                print '</td>';
            print '</tr>';

            print '<tr>';
                print '<td class="titlefieldcreate">'.$langs->trans('paiematricule').'</td>';
                print '<td>';
                if(isset($userpay->array_options['options_paiedolibarrmatricule'])){
                    print $userpay->array_options['options_paiedolibarrmatricule'];
                }
                print $edituser;
                print '</td>';
            print '</tr>';
            
            print '<tr>';
                print '<td class="titlefieldcreate">'.$langs->trans('paiecin').'</td>';
                print '<td>';
                if(isset($userpay->array_options['options_paiedolibarrcin'])){
                    print $userpay->array_options['options_paiedolibarrcin'];
                }
                print $edituser;
                print '</td>';
            print '</tr>';

            print '<tr>';
                print '<td class="titlefieldcreate">'.$langs->trans('paiecnss').'</td>';
                print '<td>';
                if(isset($userpay->array_options['options_paiedolibarrcnss'])){
                    print $userpay->array_options['options_paiedolibarrcnss'];
                }
                print $edituser;
                print '</td>';
            print '</tr>';

            print '<tr>';
                print '<td class="titlefieldcreate">'.$langs->trans('paiecimr').'</td>';
                print '<td>';
                if(isset($userpay->array_options['options_paiedolibarrcimr'])){
                    print $userpay->array_options['options_paiedolibarrcimr'];
                }
                print $edituser;
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
                print $edituser;
                print '</td>';
            print '</tr>';

        print '</table>';
        // print '<hr class="paiehr">';
        print '<br>';
    print '</div>';

    print '<div style="clear:both"></div>';
    print '</div>';

    print '<br>';
    print '<br>';

    // Actions
    if(!$action_rule) {
        $actionbut = '<table class="" width="100%">';
            $actionbut .= '<tr>';
                $actionbut .= '<td colspan="2" align="center">';
                $actionbut .= '<input type="submit" value="'.$langs->trans('Validate').'" name="bouton" class="button confirmvalidatebutton" />';
                // $actionbut .= '<input type="button" class="button" value="'.$langs->trans('Cancel').'" onclick="javascript:history.go(-1)">';
                $actionbut .= '<a style=""  href="./card.php?id='.$id.'" class="button">'.$langs->trans('Show').'</a>';
                $actionbut .=  '<a style="float:right;margin-left:40px;"  href="./card.php?id='.$id.'&export=pdf" target="_blank" class="butAction badge-status4">'.$langs->trans('paiePrintFile').'</a>';

                // $actionbut .= '<a style=""  href="./card.php?id='.$id.'&export=pdf" target="_blank" class="butAction">'.$langs->trans('paiePrintFile').'</a>';

                $actionbut .= '</td>';

            $actionbut .= '</tr>';
        $actionbut .= '</table>';
    }

    print $actionbut;


    print '<div class="paiecalculdesalaire">';
    print '<div class="titre">';
    print $langs->trans('paiecalculdesalaire');
    print '</div>';

    $cts = '';
    $cts .= '<div class="div-table-responsive tablesalaire">';
    $cts .= '<table class="tagtable liste listwithfilterbefore bodytable" id="paielines" style="width:100%">'; 

    $buttadd = '';
    // $buttadd .= '<a type="button" class="buttonrule add" onclick="add_tr_rule(this);"> <i class="fa fa-plus"></i> </a>';


    $thead = '';
    $thead .= '<thead>';
        $thead .= '<tr class="liste_titre">';
        $thead .= '<th class="center td_action">'.$buttadd.'</th>'; 
        $thead .= '<th class="left">'.$langs->trans('paieDesignation').'</th>'; 
        $thead .= '<th class="paieemptyline"></th>'; 
        // $thead .= '<th class="left">'.$langs->trans('Type').'</th>';
        $thead .= '<th class="right">'.$langs->trans('paieBase').'</th>';
        // $thead .= '<th class="right">'.$langs->trans('paieTaux').' (%)</th>';
        $thead .= '<th class="right">'.$langs->trans('NbreOuTaux').'</th>';
        // $thead .= '<th class="right">'.$langs->trans('paieTotal').'</th>';
        $thead .= '</tr>';
    $thead .= '</thead>';

    $cts .= $thead;

    $cts .= '<tbody>';

    $arrondiauto = $conf->global->PAIEDOLIBARR_PAIE_ENABLE_ARRONDI_AUTO;

    $payrules = $object->getRulesOfPaie($item->rowid);
    $i = 1;
    $firstc = $firsto = 0;
    // d($payrules);
    if($payrules){
        foreach ($payrules as $key => $rule) {
            $clas = '';

            $clsclc = ($rule->code != 'SALARY' && $rule->amounttype == 'CALCULATED' || ($rule->category == '99ARRONDI' && $arrondiauto)) ? 'recalculer' : '';

            $cts .= '<tr class="oddeven '.$clsclc.'" data-id="'.$i.'" data-ruleid="'.$rule->rowid.'" title="">';

                $cts .= '<td class="td_action" align="center">';
                    if($rule->code != 'SALARY') {
                        $cts .= '<a type="button" class="buttonrule remove" onclick="remove_tr_paie(this);" title="'.$langs->trans('Delete').'">';
                        $cts .= '<i class="fa fa-remove" aria-hidden="true"></i><span class="buttonrule-text">X</span>';
                        $cts .= '</a>';
                    }
                $cts .= '</td>';

                $cts .= '<td class="td_label left">'; 
                    // $cts .= '<input type="text" name="payrules['.$rule->rowid.'][label]" value="'.$rule->label.'" class="designation"/>'; 
                    $cts .= '<input type="hidden" name="payrules['.$rule->rowid.'][label]" value="'.$rule->label.'" class="designation"/>'; 
                    $cts .= $rule->label; 
                $cts .= '</td>';
            
                $cts .= '<th class="paieemptyline"></th>';

                $title = '';
                $reado = '';

                // $cts .= '<td>';
                // $cts .= '<span class="amounttype">';
                //     $disabledtype = ($rule->code == 'SALARY') ? true : false;
                //     $cts .= $rules->selectAmounttype($rule->amounttype, 'amounttype', 0, $disabledtype);
                // $cts .= '</span>';
                // $cts .= '</td>';


                // Base
                $cts .= '<td class="td_amount classfortooltip right" >'; 

                if($clsclc && $rule->code != 'SALARY') {
                    // $reado = 'readonly';
                    $cts .= '<span class="calculatautomaticlyspan">';
                    $cts .= $langs->trans('paieCalcule_automatiquement');
                    $cts .= '</span>';
                }
                if($rule->code == 'SALARY')
                    $salarybydegre=$paiedolibarr->getSalaryByDegre($object->fk_user);

                $cts .= '<input type="number" min="0" '.$reado.' name="payrules['.$rule->rowid.'][amount]" size="50" step="0.01" class="amount" value="'.(price2num($rule->amount ? $rule->amount : (($rule->code == 'SALARY' || $rule->code == 'USER_SALARY') ? ($salarybydegre ? $salarybydegre : $userpay->salary) : 0))).'"/>'; 
                $cts .= '</td>';

                // Taux
                $cts .= '<td class="td_taux classfortooltip right" >'; 
                    $tauxval = price2num($rule->taux ? $rule->taux : 0);

                    $typeinput = ($rule->code == 'SALARY') ? 'hidden' : 'number';
                    $cts .= '<input type="'.$typeinput.'" min="0" '.$reado.' name="payrules['.$rule->rowid.'][taux]" size="6" step="0.01" class="taux" value="'.$tauxval.'"/>'; 
                    if($typeinput == 'hidden') {
                        $cts .= '<span style="padding-right: 17px;">100</span>'; 
                    }

                    // if($tauxval < 100)
                    $cts .= '<span class="tauxtxt">'.$tauxval.'</span>'; 

                    $cts .= '<input type="hidden" name="payrules['.$rule->rowid.'][amounttype]" value="'.$rule->amounttype.'" />';
                    $cts .= '<input type="hidden" name="payrules['.$rule->rowid.'][code]" value="'.$rule->code.'" />';
                    $cts .= '<input type="hidden" name="payrules['.$rule->rowid.'][formule_base]" value="'.$rule->formule_base.'" />';
                    $cts .= '<input type="hidden" name="payrules['.$rule->rowid.'][formule_taux]" value="'.$rule->formule_taux.'" />';
                    $cts .= '<input type="hidden" name="payrules['.$rule->rowid.'][category]" value="'.$rule->category.'" />';
                $cts .= '</td>';

            $cts .= '</tr>';

            $i++;
        }
    }

    $cts .= '</tbody>';

    if(!$action_rule)
        $cts .= $thead;

    $cts .= '</table>'; 
    $cts .= '</div>';

    print $cts;

    print '</div>';

























    if($action_rule == 'create') {

        $newrules = $object->getNewRules($object->id);


        $more = '';
        $linktogo='';
        $more .= '<br>';
        $more .= '<div class="tagtable paddingtopbottomonly centpercent noborderspacing newrule_formconfirm" id="newrule_formconfirm">';
        $more .= '<form method="POST" action="'.$linktogo.'" class="notoptoleftroright">'."\n";
        $more .= '<input type="hidden" name="token" value="'.(isset($_SESSION['newtoken']) ? $_SESSION['newtoken'] : '').'">';
        $more .= '<input type="hidden" name="action" value="edit">'."\n";
        $more .= '<input type="hidden" name="rule" value="add">'."\n";
        $more .= '<input type="hidden" name="id" value="'.$id.'" />';

        $more .= '<table class="valid centpercent">';
            
            $more .= '<tr class="oddeven_">';

            $more .= '<td class="left">';

            // $arr_rules = $form->selectarray('newrules', $newrules, 0, 0, 0, 0, '', 0, 0, 0, '', 'width400', 1);
            $more .= $rules->selectRules('newrules', $newrules, array(), 'width400');
            $more .= '<input class="button valignmiddle confirmvalidatebutton" type="submit" value="'.$langs->trans("Validate").'">';
            // $more .= '<input type="submit" class="button button-cancel" value="'.$langs->trans("Cancel").'" name="cancel" />';
            $more .= '<a style=""  href="./card.php?id='.$id.'" class="button">'.$langs->trans('Cancel').'</a>';
            $more .= '</td>';
            $more .= '</tr>'."\n";

        $more .= '</table>';

        $more .= "</form>\n";

        $more .= '</div>';

        $more .= '<br>';

        print $more;
    }


    if(!$action_rule) {
        print '<br>';
        print '<a style=""  href="./card.php?id='.$id.'&action=edit&rule=create" class="button">'.$langs->trans('NewPaieRule2').'</a>';
    }

    print '<br>';
    print $actionbut;
    print '<br>';

    print '</form>';
        
}

?>
