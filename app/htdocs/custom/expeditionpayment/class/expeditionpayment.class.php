<?php
/**
 * Expedition payment business object
 */

class ExpeditionPayment
{
    public $db;
    public $error = '';
    public $errors = array();

    public $id;
    public $ref;
    public $entity;
    public $fk_soc;
    public $amount;
    public $datep;
    public $mode;
    public $num_payment;
    public $note;
    public $status = 1;
    public $fk_user_creat;
    public $fk_user_valid;
    public $fk_user_cancel;
    public $lines = array(); // array of stdClass {fk_expedition, amount}

    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = 1;
    }

    public function create($user)
    {
        global $conf;

        $this->errors = array();

        if ($this->amount <= 0) {
            $this->errors[] = 'Amount must be positive';
            return -1;
        }
        if (empty($this->fk_soc)) {
            $this->errors[] = 'Missing thirdparty';
            return -1;
        }
        if (empty($this->lines)) {
            $this->errors[] = 'No expedition linked';
            return -1;
        }

        $now = dol_now();
        $this->ref = $this->generateRef();
        $this->entity = $conf->entity;
        $this->datep = $this->datep ?: $now;

        $this->db->begin();

        $sql = "INSERT INTO ".$this->db->prefix()."expedition_payment(";
        $sql .= "entity, ref, fk_soc, amount, datep, mode, num_payment, note, status, fk_user_creat, datec";
        $sql .= ") VALUES(";
        $sql .= (int) $this->entity.", ";
        $sql .= "'".$this->db->escape($this->ref)."', ";
        $sql .= (int) $this->fk_soc.", ";
        $sql .= (float) $this->amount.", ";
        $sql .= "'".$this->db->idate($this->datep)."', ";
        $sql .= ($this->mode ? "'".$this->db->escape($this->mode)."'" : "NULL").", ";
        $sql .= ($this->num_payment ? "'".$this->db->escape($this->num_payment)."'" : "NULL").", ";
        $sql .= ($this->note ? "'".$this->db->escape($this->note)."'" : "NULL").", ";
        $sql .= (int) $this->status.", ";
        $sql .= (int) $user->id.", ";
        $sql .= "'".$this->db->idate($now)."'";
        $sql .= ")";

        $res = $this->db->query($sql);
        if (!$res) {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            return -1;
        }

        $this->id = $this->db->last_insert_id($this->db->prefix().'expedition_payment');

        foreach ($this->lines as $line) {
            $ins = "INSERT INTO ".$this->db->prefix()."expedition_payment_expedition(fk_payment, fk_expedition, amount, entity)";
            $ins .= " VALUES(";
            $ins .= (int) $this->id.", ".((int) $line->fk_expedition).", ".((float) $line->amount).", ".((int) $this->entity).")";
            if (!$this->db->query($ins)) {
                $this->db->rollback();
                $this->error = $this->db->lasterror();
                return -1;
            }
        }

        $this->db->commit();
        return $this->id;
    }

    public function fetch($id)
    {
        $sql = "SELECT * FROM ".$this->db->prefix()."expedition_payment WHERE rowid=".(int) $id;
        $res = $this->db->query($sql);
        if ($res && $this->db->num_rows($res) > 0) {
            $obj = $this->db->fetch_object($res);
            $this->id = $obj->rowid;
            $this->ref = $obj->ref;
            $this->entity = $obj->entity;
            $this->fk_soc = $obj->fk_soc;
            $this->amount = (float) $obj->amount;
            $this->datep = $this->db->jdate($obj->datep);
            $this->mode = $obj->mode;
            $this->num_payment = $obj->num_payment;
            $this->note = $obj->note;
            $this->status = (int) $obj->status;
            $this->fk_user_creat = $obj->fk_user_creat;
            $this->fk_user_valid = $obj->fk_user_valid;
            $this->fk_user_cancel = $obj->fk_user_cancel;
            $this->lines = $this->fetchLines($this->id);
            return 1;
        }
        return 0;
    }

    public function fetchByExpedition($expeditionId)
    {
        $list = array();
        $sql = "SELECT ep.rowid";
        $sql .= " FROM ".$this->db->prefix()."expedition_payment as ep";
        $sql .= " INNER JOIN ".$this->db->prefix()."expedition_payment_expedition as l ON l.fk_payment = ep.rowid";
        $sql .= " WHERE l.fk_expedition = ".((int) $expeditionId);
        $sql .= " AND ep.status != 9";
        $res = $this->db->query($sql);
        if ($res) {
            while ($obj = $this->db->fetch_object($res)) {
                $pay = new self($this->db);
                if ($pay->fetch($obj->rowid) > 0) {
                    $list[] = $pay;
                }
            }
        }
        return $list;
    }

    /**
     * Sum of validated payments for one expedition
     *
     * @param int $expeditionId
     * @return float
     */
    public function sumForExpedition($expeditionId)
    {
        $sql = "SELECT SUM(l.amount) as total";
        $sql .= " FROM ".$this->db->prefix()."expedition_payment as ep";
        $sql .= " INNER JOIN ".$this->db->prefix()."expedition_payment_expedition as l ON l.fk_payment = ep.rowid";
        $sql .= " WHERE l.fk_expedition = ".((int) $expeditionId);
        $sql .= " AND ep.status = 1";

        $res = $this->db->query($sql);
        if ($res) {
            $obj = $this->db->fetch_object($res);
            return (float) $obj->total;
        }
        return 0;
    }

    /**
     * Sum of validated payments for a thirdparty
     *
     * @param int $socid
     * @return float
     */
    public function sumForThirdparty($socid)
    {
        $sql = "SELECT SUM(amount) as total";
        $sql .= " FROM ".$this->db->prefix()."expedition_payment";
        $sql .= " WHERE fk_soc = ".((int) $socid);
        $sql .= " AND status = 1";

        $res = $this->db->query($sql);
        if ($res) {
            $obj = $this->db->fetch_object($res);
            return (float) $obj->total;
        }
        return 0;
    }

    private function fetchLines($paymentId)
    {
        $out = array();
        $sql = "SELECT fk_expedition, amount FROM ".$this->db->prefix()."expedition_payment_expedition";
        $sql .= " WHERE fk_payment = ".((int) $paymentId);
        $res = $this->db->query($sql);
        if ($res) {
            while ($obj = $this->db->fetch_object($res)) {
                $line = new stdClass();
                $line->fk_expedition = (int) $obj->fk_expedition;
                $line->amount = (float) $obj->amount;
                $out[] = $line;
            }
        }
        return $out;
    }

    public function cancel($user)
    {
        $now = dol_now();
        $sql = "UPDATE ".$this->db->prefix()."expedition_payment";
        $sql .= " SET status = 9, fk_user_cancel = ".((int) $user->id).", date_cancel = '".$this->db->idate($now)."'";
        $sql .= " WHERE rowid = ".((int) $this->id);
        $res = $this->db->query($sql);
        if (!$res) {
            $this->error = $this->db->lasterror();
            return -1;
        }
        $this->status = 9;
        return 1;
    }

    private function generateRef()
    {
        return 'EP'.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
    }
}
