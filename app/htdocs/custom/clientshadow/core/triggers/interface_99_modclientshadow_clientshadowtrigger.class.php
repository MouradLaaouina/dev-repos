<?php
/**
 * Trigger file for ClientShadow module
 */
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

class Interfaceclientshadowtrigger
{
    /** @var DoliDB */
    protected $db;
    /** @var ClientShadow */
    protected $helper;
    private $shadowLogTableChecked = false;
    private $shadowLogTableExists = false;

    public $error;
    public $errors = array();

    public function __construct($db)
    {
        $this->db = $db;
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->description = "Triggers for ClientShadow module";
        $this->version = '1.0';

        dol_include_once('/clientshadow/class/clientshadow.class.php');
        $this->helper = new ClientShadow($this->db);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDesc()
    {
        return $this->description;
    }

    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Main trigger entrypoint
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
        if (!isset($object->shadow_mode)) {
            $origin = $this->helper->getOriginShadowValue($object);
            if ($origin !== null) {
                $object->shadow_mode = (int) $origin;
            }
        }

        switch ($action) {
            case 'COMPANY_CREATE':
                global $conf;
                $default = function_exists('getDolGlobalString') ? getDolGlobalString('CLIENTSHADOW_DEFAULT_TYPE', 'A') : (!empty($conf->global->CLIENTSHADOW_DEFAULT_TYPE) ? $conf->global->CLIENTSHADOW_DEFAULT_TYPE : 'A');
                $object->array_options['options_clientshadow_type'] = $default;
                $object->update($object->id, $user);
                break;

            case 'ORDER_CREATE':
                $shadow = $this->helper->isShadowObject($object) ? 1 : 0;
                $this->helper->flagShadow('commande', $object, $shadow);
                if ($shadow) {
                    $this->recordShadowDoc($object, 'commande', $object->total_ht);
                }
                break;

            case 'SHIPPING_VALIDATE':
                $shadow = $this->helper->isShadowObject($object) ? 1 : 0;
                $this->helper->flagShadow('expedition', $object, $shadow);
                if ($shadow) {
                    $this->helper->neutralizeShipmentStock($object, $user);
                    $this->recordShadowDoc($object, 'expedition', 0);
                }
                break;

            case 'BILL_CREATE':
                $shadow = $this->helper->isShadowObject($object) ? 1 : 0;
                $this->helper->flagShadow('facture', $object, $shadow);
                if ($shadow) {
                    $this->recordShadowDoc($object, 'facture', $object->total_ht);
                }
                break;

            case 'BILL_VALIDATE':
                if ($this->helper->isShadowObject($object)) {
                    $langs->load('clientshadow@clientshadow');
                    $this->error = $langs->trans('ClientShadowShadowInvoice');
                    $this->errors[] = $this->error;
                    return -1;
                }
                break;
        }

        return 0;
    }

    /**
     * Record entry into llx_clientshadow_shadowdoc
     *
     * @param CommonObject $object
     * @param string       $elementType
     * @param float        $amount
     * @return void
     */
    private function recordShadowDoc($object, $elementType, $amount)
    {
        global $conf;
        $socid = !empty($object->fk_soc) ? (int) $object->fk_soc : (!empty($object->socid) ? (int) $object->socid : 0);
        if (empty($socid)) {
            return;
        }

        if (!$this->shadowLogTableExists()) {
            return;
        }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."clientshadow_shadowdoc(entity, fk_object, element_type, fk_soc, amount) VALUES(".
            ((int) (!empty($object->entity) ? $object->entity : $conf->entity)).",".
            ((int) $object->id).",".
            "'".$this->db->escape($elementType)."',".
            $socid.",".
            ((float) $amount).
            ")";
        $res = $this->db->query($sql);
        if (!$res) {
            dol_syslog(__METHOD__.' recordShadowDoc error: '.$this->db->lasterror(), LOG_WARNING);
        }
    }

    private function shadowLogTableExists()
    {
        if ($this->shadowLogTableChecked) {
            return $this->shadowLogTableExists;
        }

        $sql = "SHOW TABLES LIKE '".$this->db->escape(MAIN_DB_PREFIX."clientshadow_shadowdoc")."'";
        $res = $this->db->query($sql);
        if ($res) {
            $this->shadowLogTableExists = (bool) $this->db->num_rows($res);
            $this->db->free($res);
        } else {
            $this->shadowLogTableExists = false;
        }
        $this->shadowLogTableChecked = true;

        return $this->shadowLogTableExists;
    }
}
