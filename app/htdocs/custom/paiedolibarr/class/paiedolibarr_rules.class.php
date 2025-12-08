<?php 
dol_include_once('/core/lib/admin.lib.php');

class paiedolibarr_rules extends Commonobject{ 

    public $errors = array();
    public $rowid;
    public $numero;
    public $code;
    public $label;
    public $category;
    public $amounttype;
    public $amount;
    public $taux;
    public $ordercalcul;
    public $formule_base;
    public $formule_taux;
    public $formule;
    public $total;
    public $showdefault;
    public $showcumul;
    public $engras;
    

    public $element='paiedolibarr_rules';
    public $table_element='paiedolibarr_rules';

    public function __construct($db){ 
        global $langs;

        $this->db = $db;
        
        // $this->rulescategory = [
        //     'BASIQUE'           => $langs->trans('BASIQUE'),
        //     'BRUT'              => $langs->trans('BRUT'),
        //     'RETENUES'          => $langs->trans('RETENUES'),
        //     'INDEMNITES'        => $langs->trans('INDEMNITES'),
        //     'AUTRESRETENUES'    => $langs->trans('AUTRESRETENUES')
        // ];
        $this->amounttypes = [
            'FIX'           => $langs->trans('paieMontant_fixe'),
            'CALCULATED'    => $langs->trans('paieMontant_formule'),
        ];
        $this->defaultparts = [
            'S'               => $langs->trans('paieSalariale'),
            'P'               => $langs->trans('paiePatronale'),
        ];  
        $this->gainretenus = [
            'G'               => $langs->trans('paieGain'),
            'R'               => $langs->trans('paieRetenu'),
        ];    
        $this->gainretenussigne = [
            'G'               => '+',
            'R'               => '-',
        ];  
        return 1;
    }


 
    public function create($echo_sql=0,$insert)
    {

        $sql_column = '';
        $sql_value = '';
        
        $sql  = "INSERT INTO " . MAIN_DB_PREFIX .get_class($this)." ( ";

        foreach ($insert as $column => $value) {
            $alias = (is_numeric($value)) ? "" : "'";
            if($value != ""){
                $sql_column .= " , ".$column."";
                $sql_value .= " , ".$alias.$value.$alias;
            }
        }

        $sql .= substr($sql_column, 2)." ) VALUES ( ".substr($sql_value, 2)." )";
        // die($sql);
        $resql = $this->db->query($sql);

        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();

            echo $this->db->lasterror();
            echo '<br><br>';
            echo $sql;
            die;
            return 0;
        } 
        // return $this->db->db->insert_id;
        return $this->db->last_insert_id(MAIN_DB_PREFIX.'paiedolibarr_rules');
    }

    public function update($id, array $data,$echo_sql=0)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        if (!$id || $id <= 0)
            return false;

        $sql = 'UPDATE ' . MAIN_DB_PREFIX .get_class($this). ' SET ';

        if (count($data) && is_array($data))
            foreach ($data as $key => $val) {
                $val = is_numeric($val) ? $val : '"'. $val .'"';
                $val = ($val == '') ? 'NULL' : $val;
                $sql .= ''. $key. ' = '. $val .',';
            }

        $sql  = substr($sql, 0, -1);
        $sql .= ' WHERE rowid = ' . $id;

        // die($sql);
        
        $resql = $this->db->query($sql);

        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' : '. $this->db->lasterror();
            echo $this->db->lasterror();
            echo '<br><br>';
            echo $sql;
            die;
            return -1;
        } 
        return 1;
    }

    public function delete($echo_sql=0)
    {
        global $conf;
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql  = 'DELETE FROM ' . MAIN_DB_PREFIX .get_class($this).' WHERE rowid = ' . $this->rowid;
        $sql .= ' AND entity='.$conf->entity;
        $resql  = $this->db->query($sql);
        
        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' : '.$this->db->lasterror();
            return -1;
        } 

        return 1;
    }

    
    public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
    {
        global $conf;
        dol_syslog(__METHOD__, LOG_DEBUG);
        $sql = "SELECT * FROM ";
        $sql .= MAIN_DB_PREFIX .get_class($this);
        $sql .= " WHERE entity=".$conf->entity;
        if (!empty($filter)) {
            $sql .= " ".$filter;
        }
        if (!empty($sortfield)) {
            $sql .= $this->db->order($sortfield, $sortorder);
        }
        if (!empty($limit)) {
            if($offset==1)
                $sql .= " limit ".$limit;
            else
                $sql .= " limit ".$offset.",".$limit;               
        }
        $this->rows = array();
        $resql = $this->db->query($sql);

        if ($resql) {
            $num = $this->db->num_rows($resql);

            while ($obj = $this->db->fetch_object($resql)) {
                $line = new stdClass;
                $line->rowid            = $obj->rowid;
                $line->numero           = $obj->numero ? $obj->numero : $obj->rowid;
                $line->code             = $obj->code;
                $line->label            = $obj->label;
                $line->category         = $obj->category;
                $line->amounttype       = $obj->amounttype;
                $line->amount           = $obj->amount;
                $line->taux             = $obj->taux;
                $line->ordercalcul      = $obj->ordercalcul;
                $line->formule_base     = $obj->formule_base;
                $line->formule_taux     = $obj->formule_taux;
                $line->formule          = $obj->formule;
                $line->entity           = $obj->entity;
                $line->showcumul        = $obj->showcumul;
                $line->showdefault      = $obj->showdefault;


                $this->rows[]   = $line;
            }
            $this->db->free($resql);

            return $num;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

            return -1;
        }
    }


    public function fetch($id)
    {
        global $conf;
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX .get_class($this). ' WHERE rowid = ' . $id; 
        $sql .= " AND entity=".$conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $numrows = $this->db->num_rows($resql);
            
            if ($numrows) {
                $obj                    = $this->db->fetch_object($resql);
                $this->id               = $obj->rowid;
                $this->rowid            = $obj->rowid;
                $this->numero           = $obj->numero ? $obj->numero : $obj->rowid;
                $this->code             = $obj->code;
                $this->label            = $obj->label;
                $this->category         = $obj->category;
                $this->amounttype       = $obj->amounttype;
                $this->amount           = $obj->amount;
                $this->taux             = $obj->taux;
                $this->ordercalcul      = $obj->ordercalcul;
                $this->formule_base     = $obj->formule_base;
                $this->formule_taux     = $obj->formule_taux;
                $this->formule          = $obj->formule;
                $this->entity           = $obj->entity;
                $this->showdefault      = $obj->showdefault;
                $this->showcumul        = $obj->showcumul;

            }

            $this->db->free($resql);

            if ($numrows) {
                return 1 ;
            } else {
                return 0;
            }
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
            return -1;
        }
    }

    public function selectRules($htmlname, $array, $selected = array(), $morecss = '')
    {

        $out = '';
        $out .= '<select id="'.$htmlname.'" class="paie_select_rules'.($morecss ? ' '.$morecss : '').'" multiple name="'.$htmlname.'[]" >'."\n";
        if (is_array($array) && !empty($array)) {
            foreach ($array as $key => $value) {

                $out .= '<option value="'.$key.'"';
                // if (is_array($selected) && !empty($selected) && in_array((string) $key, $selected) && ((string) $key != '')) {
                //     $out .= ' selected';
                // }
                // $out .= ' data-html="'.dol_escape_htmltag($newval).'"';
                $out .= '>';
                $out .= dol_htmlentitiesbr($value);
                $out .= '</option>'."\n";
            }
        }
        $out .= '</select>'."\n";

        return $out;

    }

    public function selectAmounttype($slctd='', $name='amounttype', $showempty=0, $disabled = false)
    {
        global $langs;
        $amounttypes = $this->amounttypes;
        $select ='<select class="select_'.$name.'" name="'.$name.'"  '.($disabled ? 'disabled' : '').'>';
            if($showempty)
                $select .='<option value="0"></option>';
            foreach ($amounttypes as $keyr => $namer) {

                $slctdt = ($keyr == $slctd) ? 'selected' : '';
                $select .='<option value="'.$keyr.'" '.$slctdt.'>'.$namer.'</option>';
            }
        $select .='</select>';
        return $select;
    }

    public function selectDefaultpart($slctd='',$name='defaultpart', $showempty=0, $disabled='')
    {
        global $langs;
        $defaultparts = $this->defaultparts;
        $select ='<select class="select_'.$name.'" name="'.$name.'" '.$disabled.'>';
            if($showempty)
                $select .='<option value="0"></option>';
            foreach ($defaultparts as $keyr => $namer) {

                $slctdt = ($keyr == $slctd) ? 'selected' : '';
                $select .='<option value="'.$keyr.'" '.$slctdt.'>'.$namer.'</option>';
            }
        $select .='</select>';

        return $select;
    }

    public function selectGainretenu($slctd='',$name='gainretenu',$showempty=0, $disabled='')
    {
        global $langs;
        $gainretenus = $this->gainretenus;
        $select ='<select class="select_'.$name.'" name="'.$name.'" '.$disabled.'>';
            if($showempty)
                $select .='<option value="0"></option>';
            foreach ($gainretenus as $keyr => $namer) {

                $slctdt = ($keyr == $slctd) ? 'selected' : '';
                $select .='<option value="'.$keyr.'" '.$slctdt.'>'.$namer.'</option>';
            }
        $select .='</select>';
        return $select;
    }

    public function selectGainretenuSigne($slctd='',$name='gainretenu',$showempty=0, $disabled='',$onlysigne=false)
    {
        global $langs;
        $gainretenus = $this->gainretenussigne;
        $select ='<select class="select_'.$name.'" name="'.$name.'" '.$disabled.'>';
            if($showempty)
                $select .='<option value="0"></option>';
            foreach ($gainretenus as $keyr => $namer) {

                $slctdt = ($keyr == $slctd) ? 'selected' : '';
                $select .='<option value="'.$keyr.'" '.$slctdt.'>'.$namer.'</option>';
            }
        $select .='</select>';
        return $select;
    }

    
    
} 

?>