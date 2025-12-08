<?php

if ($action == 'add' && $request_method === 'POST') {

    global $conf;

    $periodyear     = GETPOST('periodyear','int');
    $periodmonth    = GETPOST('periodmonth','int');
    $period = $periodyear.'-'.sprintf("%02d", $periodmonth).'-01';

    if(!GETPOST('fk_user')){
        setEventMessage($langs->trans("ChooseAnEmployee"), 'errors');
        header('Location: ./card.php?action=add');
        exit;
    }
    
    $fk_user = GETPOST('fk_user','int');
    $insert = array(
        'fk_user'   => $fk_user
        ,'period'   => $period
        ,'nbdaywork'   => 26
        ,'ref'      => $db->escape(GETPOST('ref', 'alphanohtml'))
        ,'label'    => $db->escape(GETPOST('label', 'alphanohtml'))
        ,'comment'  => $db->escape(GETPOST('comment', 'alphanohtml'))
        ,'entity'   => $conf->entity
    );

    $insert['datepay'] = $db->idate($datepay);
    $insert['mode_reglement_id'] = GETPOST('mode_reglement_id','int');
    $insert['fk_author'] = $user->id;

    $employee = new User($db);
    $employee->fetch($fk_user);


    
    if(isset($employee->array_options['options_paiedolibarrmatricule'])) {
        $insert['matricule'] = $employee->array_options['options_paiedolibarrmatricule'];
    }
    if(isset($employee->array_options['options_paiedolibarrnbrenfants'])) {
        $insert['nbrenfants'] = $employee->array_options['options_paiedolibarrnbrenfants'];
    }
    if(isset($employee->array_options['options_paiedolibarrnbdaywork'])) {
        $insert['nbdaywork'] = $employee->array_options['options_paiedolibarrnbdaywork'];
    }
    if(isset($employee->array_options['options_paiedolibarrsituation_f'])) {
        $insert['situation_f'] = $employee->array_options['options_paiedolibarrsituation_f'];
    }

    if(isset($employee->array_options['options_paiedolibarrcertificat'])) {
        $insert['certificat'] = $employee->array_options['options_paiedolibarrcertificat'];
    }

    $ret = $extrafields->setOptionalsFromPost(null, $object);
    // var_dump($employee);
    // var_dump($insert);
    // var_dump($object);
    $avance = $object->create(1,$insert);
    $object->fetch($avance);

    if ($avance > 0) {
        $otherdata = $object->calculatePaieRules($avance);
        setEventMessages($langs->transnoentities("RecordCreatedSuccessfully"), null, 'mesgs');
        // die;
        header('Location: ./card.php?id='. $avance.'&action=edit');
        // header('Location: ./card.php?id='. $avance);
        exit;
    }
    else {
        setEventMessages($object->errors, null, 'errors');
        header('Location: card.php?action=create');
        exit;
    }
}

if($action == "create"){

    global $conf;
    
    $periodyear     = GETPOST('year','int');
    $periodmonth    = GETPOST('month','int');

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="competcpaiedolibarr card_paiedolibarr">';
    print '<input type="hidden" name="token" value="'.(isset($_SESSION['newtoken']) ? $_SESSION['newtoken'] : '').'">';
    print '<input type="hidden" name="action" value="add" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';

    print '<div class="">';
    print '<div class="">';
        print '<table class="border morepaddingtd" width="100%">';

        $periodyear = $periodyear ? $periodyear : date('Y');
        $periodmonth = $periodmonth ? $periodmonth : ((int) date('m') - 1);

        print '<tr>';
            print '<td class="titlefieldcreate">'.$langs->trans('paieofmonth').'</td>';
            print '<td >';
            print $formother->selectyear($periodyear,'periodyear', $_useempty = 0, $_min_year = 10, $_max_year = 0).$formother->select_month($periodmonth,'periodmonth','',1,'maxwidth100imp');
            print '</td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefieldcreate" id="titletdpaiedolibarr">'.$langs->trans('paiedolibarr_employe').'</td>';
            print '<td id="paieemployees">';
            print '<span id="users">';
            $excludes = array();
            // $excludes = $object->usersToExclude(0);
            // print $form->select_dolusers('', 'fk_user', 0, $excludes, 0, '', 0, 0, 0, 0, '', 0, '', 'maxwidth300');
            print '</span>';
            print '</td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefieldcreate">'.$langs->trans('paiedolibarr_ref').'</td>';
            // print '<td ><input type="text" class="quatrevingtseizepercent minwidth300" id="paieref" name="ref" value="'.$langs->trans('PAIESLIP').'" autocomplete="off"/>';
            print '<td ><input type="text" class="quatrevingtseizepercent minwidth300" id="paieref" name="ref" value="" autocomplete="off"/>';
            print '</td>';
        print '</tr>';

        


       
        $mountyear = $langs->trans("Month".sprintf("%02d", $periodmonth))." ".$periodyear;

        print '<tr>';
            print '<td class="titlefieldcreate">'.$langs->trans('paiesname').'</td>';
            print '<td >';
            print '<input value="'.$langs->trans('Fiche_de_salaire').' - '.$mountyear.'" type="text" class="quatrevingtseizepercent minwidth300" id="paielabel" name="label"  autocomplete="off"/>';
            print '</td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefieldcreate">'.$langs->trans('paiedolibarr_comment').'</td>';
            print '<td><textarea name="comment" id="comment" class="centpercent" rows="5" wrap="soft">';
            // print $item->comment;
            print '</textarea></td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefieldcreate">'.$langs->trans('PaymentMode').'</td>';
            print '<td >';
            $form->select_types_paiements(2, 'mode_reglement_id');
            print '</td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefieldcreate">'.$langs->trans('paiesdatepay').'</td>';
            print '<td>';
            $now = dol_now();

            print $form->selectDate($object->datepay ? $object->datepay : $datepay, 'datepay', 0, 0, 0, '', 1, 1);
            print '</td>';
        print '</tr>';
        
        // include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';
        // Other options
        $parameters = array();
        $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (empty($reshook)) {
            print $object->showOptionals($extrafields, 'create');
        }

        print '</table>';

        print '<br>';
   
    print '</div>';

    print '<div style="clear:both"></div>';
    print '</div>';

   
    // Actions
    print '<table class="" width="100%">';
        print '<tr>';
            print '<td colspan="2" align="center" >';
            print '<br>';
            print '<input type="submit" class="button confirmvalidatebutton" name="save" value="'.$langs->trans('Validate').'">';
            print '<input type="button" class="button" value="'.$langs->trans('Cancel').'" onclick="javascript:history.go(-1)">';
            // print '<input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
        print '</tr>';
    print '</table>';


    print '</form>';

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function() {
            $('#periodyear,#periodmonth').change(function() {
                datapaiedolibarr('users');
            });
            triggeruserschange();
            $('#periodmonth').trigger('change');
        });

        function remove_tr_paie(x){
            var y = $(x).parent('td').parent('tr');
            y.remove();
        }

        function triggeruserschange(){
            $('#paieemployees select').change(function() {
                datapaiedolibarr('details');
            });
        }

        function datapaiedolibarr(action){
            var fk_user     = $('#paieemployees select').val();
            var periodyear  = $('#periodyear').val();
            var periodmonth = $('#periodmonth').val();

            $.ajax({
                url:'<?php echo dol_escape_js(dol_buildpath("/paiedolibarr/check.php",1)); ?>',
                type:"POST",
                data:{'fk_user':fk_user,'periodyear':periodyear,'periodmonth':periodmonth,'action':action},
                success:function(ajaxr){
                    var result = $.parseJSON(ajaxr);
                    if(action == 'details'){
                        $('#paielabel').val(result.label);
                        $('#paieref').val(result.ref);
                        // calculatepaie(result.salary);

                        jQuery('#datepay').val(result.datepay_date);
                        
                        jQuery('#datepayday').val(result.datepay_day);
                        jQuery('#datepaymonth').val(periodmonth);
                        jQuery('#datepayyear').val(periodyear);


                    }else{
                        $('#paieemployees #users').html(result.users);
                        datapaiedolibarr('details');
                        triggeruserschange();
                    }
                }
            });
        }
    </script>
    <?php
}