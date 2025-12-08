<?php 
dol_include_once('/core/lib/admin.lib.php');

class paiedolibarr_salaryscall extends Commonobject{ 

    public $errors = array();
    public $rowid;
    public $code;
    public $label;
    public $category;
    public $amount;
    public $taux;
    public $formule_base;
    public $formule_taux;
    public $formule;
    public $fk_rule;
    public $engras;

    public $element='paiedolibarr_salaryscall';
    public $table_element='paiedolibarr_salaryscall';
    

    public function __construct($db){ 
        global $langs;

        $this->db = $db;
        
        return 1;
    }
    

    public function create($insert)
    {

        $sql_column = '';
        $sql_value = '';
        
        $sql  = "INSERT INTO " . MAIN_DB_PREFIX .get_class($this)." ( ";

        foreach ($insert as $column => $value) {
            $alias = (is_numeric($value)) ? "" : "'";
            if($value != ""){
                $sql_column .= " , `".$column."`";
                $sql_value .= " , ".$alias.$value.$alias;
            }
        }

        $sql .= substr($sql_column, 2)." ) VALUES ( ".substr($sql_value, 2)." )";
        // echo $sql;die;
        $resql = $this->db->query($sql);

        // d($sql,0);
        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
            // d($this->errors);
            return 0;
        } 
        // return $this->db->db->insert_id;
        return $this->db->last_insert_id(MAIN_DB_PREFIX.'paiedolibarr_salaryscall');
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
                $sql .= '`'. $key. '` = '. $val .',';
            }

        $sql  = substr($sql, 0, -1);
        $sql .= ' WHERE rowid = ' . $id;
        // echo $sql;die;
        $resql = $this->db->query($sql);

        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' : '. $this->db->lasterror();
            return -1;
        } 
        return 1;
    }

    public function delete($echo_sql=0)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = 'DELETE FROM ' . MAIN_DB_PREFIX .get_class($this).' WHERE rowid = ' . $this->rowid;
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
        $sql .= " WHERE 1=1";

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
                $line->id               =  $obj->rowid;
                $line->rowid            =  $obj->rowid;
                $line->period           =  $obj->period;
                $line->fk_user          =  $obj->fk_user;
                $line->ref              =  $obj->ref;
                $line->label            =  $obj->label;
                // ....

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
        $sql .= ' AND 1 = 1 ';
        $resql = $this->db->query($sql);
        if ($resql) {
            $numrows = $this->db->num_rows($resql);
            
            if ($numrows) {
                $obj                    =  $this->db->fetch_object($resql);
                $this->id               =  $obj->rowid;
                $this->rowid            =  $obj->rowid;
                $this->period           =  $obj->period;
                $this->fk_user          =  $obj->fk_user;
                $this->ref              =  $obj->ref;
                $this->label            =  $obj->label;

                // ....
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

    
} 

?>