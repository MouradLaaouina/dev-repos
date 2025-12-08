<?php
/**
 * Utility helpers for ClientShadow module
 */
class ClientShadow
{
    /** @var DoliDB */
    private $db;

    /** @var array */
    private $elementToTable = array(
        'societe'   => 'societe',
        'commande'  => 'commande',
        'order'     => 'commande',
        'facture'   => 'facture',
        'invoice'   => 'facture',
        'shipping'  => 'expedition',
        'expedition'=> 'expedition',
        'propal'    => 'propal'
    );

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Return the client type (A or B) stored in extrafields
     *
     * @param Societe $thirdparty
     * @return string
     */
    public function getThirdpartyType($thirdparty)
    {
        if (empty($thirdparty->array_options)) {
            $thirdparty->fetch_optionals();
        }
        if (!empty($thirdparty->array_options['options_clientshadow_type'])) {
            return $thirdparty->array_options['options_clientshadow_type'];
        }

        return 'A';
    }

    /**
     * Determine if a thirdparty or object must run in shadow mode
     *
     * @param CommonObject $object
     * @return bool
     */
    public function isShadowObject($object)
    {
        if (isset($object->shadow_mode)) {
            return (bool) $object->shadow_mode;
        }

        $stored = $this->getShadowFlagFromDb($object);

        if ($stored !== null) {
            $object->shadow_mode = (int) $stored;
            return (bool) $stored;
        }

        $originValue = $this->getShadowModeFromOrigin($object);
        if ($originValue !== null) {
            $object->shadow_mode = (int) $originValue;
            return (bool) $originValue;
        }

        if (!empty($object->thirdparty)) {
            $typeB = $this->getThirdpartyType($object->thirdparty) === 'B';
            $object->shadow_mode = $typeB ? 1 : 0;
            return $typeB;
        }

        if (!empty($object->socid)) {
            dol_include_once('/societe/class/societe.class.php');
            $thirdparty = new Societe($this->db);
            if ($thirdparty->fetch($object->socid) > 0) {
                $typeB = $this->getThirdpartyType($thirdparty) === 'B';
                $object->shadow_mode = $typeB ? 1 : 0;
                return $typeB;
            }
        }

        return false;
    }

    /**
     * Mark a document as shadow by setting column shadow_mode
     *
     * @param string        $table
     * @param CommonObject  $object
     * @return void
     */
    public function flagShadow($table, $object, $value = 1)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX.$table." SET shadow_mode = ".((int) $value)." WHERE rowid = ".((int) $object->id);
        $this->db->query($sql);
    }

    /**
     * Reverse stock movement after a shipment validation
     *
     * @param Expedition $shipment
     * @param User       $user
     * @return void
     */
    public function neutralizeShipmentStock($shipment, $user)
    {
        if (empty($shipment->lines)) {
            $shipment->fetch_lines();
        }

        dol_include_once('/product/class/product.class.php');
        foreach ($shipment->lines as $line) {
            if (empty($line->fk_product) || empty($line->qty)) {
                continue;
            }

            $product = new Product($this->db);
            if ($product->fetch($line->fk_product) > 0) {
                $product->correct_stock(
                    $user,
                    $line->qty,
                    0,
                    $shipment->entrepot_id,
                    $shipment->ref.' shadow revert'
                );
            }
        }
    }

    /**
     * Convert a client from B to A and convert its pending documents
     *
     * @param Societe $thirdparty
     * @param User    $user
     * @return int
     */
    public function convertToTypeA($thirdparty, $user)
    {
        $thirdparty->array_options['options_clientshadow_type'] = 'A';
        $thirdparty->update($thirdparty->id, $user);

        // Mark previous documents as historical shadow
        $tables = array('commande', 'expedition', 'facture');
        foreach ($tables as $table) {
            $sql = "UPDATE ".MAIN_DB_PREFIX.$table." SET shadow_mode = 2 WHERE fk_soc = ".((int) $thirdparty->id)." AND shadow_mode = 1";
            $this->db->query($sql);
        }

        return 1;
    }

    /**
     * Persist the shadow flag for an object
     *
     * @param CommonObject $object
     * @param int|null     $value
     * @return void
     */
    public function saveShadowMode($object, $value = null)
    {
        if ($value === null && isset($object->shadow_mode)) {
            $value = (int) $object->shadow_mode;
        }

        if ($value === null || empty($object->id)) {
            return;
        }

        $table = $this->getTableNameForObject($object);
        if (empty($table)) {
            return;
        }

        $this->flagShadow($table, $object, (int) $value);
    }

    /**
     * Get stored value from DB
     *
     * @param CommonObject $object
     * @return int|null
     */
    public function getShadowFlagFromDb($object)
    {
        if (empty($object->id)) {
            return null;
        }

        $table = $this->getTableNameForObject($object);
        if (empty($table)) {
            return null;
        }

        $sql = "SELECT shadow_mode FROM ".MAIN_DB_PREFIX.$table." WHERE rowid = ".((int) $object->id);
        $res = $this->db->query($sql);
        if ($res) {
            $obj = $this->db->fetch_object($res);
            if ($obj) {
                return (int) $obj->shadow_mode;
            }
        }

        return null;
    }

    /**
     * Guess table name for object
     *
     * @param CommonObject $object
     * @return string|null
     */
    private function getTableNameForObject($object)
    {
        if (!empty($object->table_element)) {
            return $object->table_element;
        }

        if (!empty($object->element) && isset($this->elementToTable[$object->element])) {
            return $this->elementToTable[$object->element];
        }

        return null;
    }

    /**
     * Inspect origin documents
     *
     * @param CommonObject $object
     * @return int|null
     */
    private function getShadowModeFromOrigin($object)
    {
        if (!empty($object->origin) && !empty($object->origin_id)) {
            $value = $this->getShadowValueByElement($object->origin, $object->origin_id);
            if ($value !== null) {
                return $value;
            }
        }

        if (empty($object->linkedObjectsIds) && method_exists($object, 'fetchObjectLinked')) {
            $object->fetchObjectLinked();
        }

        if (!empty($object->linkedObjectsIds) && is_array($object->linkedObjectsIds)) {
            foreach ($object->linkedObjectsIds as $element => $ids) {
                foreach ($ids as $id) {
                    $value = $this->getShadowValueByElement($element, $id);
                    if ($value !== null) {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get stored value from table by element code
     *
     * @param string $element
     * @param int    $id
     * @return int|null
     */
    private function getShadowValueByElement($element, $id)
    {
        if (isset($this->elementToTable[$element])) {
            $table = $this->elementToTable[$element];
            $sql = "SELECT shadow_mode FROM ".MAIN_DB_PREFIX.$table." WHERE rowid = ".((int) $id);
            $res = $this->db->query($sql);
            if ($res) {
                $obj = $this->db->fetch_object($res);
                if ($obj) {
                    return (int) $obj->shadow_mode;
                }
            }
        }

        return null;
    }

    /**
     * Public accessor for origin computation
     *
     * @param CommonObject $object
     * @return int|null
     */
    public function getOriginShadowValue($object)
    {
        return $this->getShadowModeFromOrigin($object);
    }

    /**
     * Compute shadow/non shadow totals for orders & invoices
     *
     * @param int $socid
     * @return array
     */
    public function getDocumentTotals($socid)
    {
        $socid = (int) $socid;
        if (!$socid) {
            return array();
        }

        $tables = array(
            'commande' => array(
                'label'        => 'ClientShadowStatOrders',
                'whereStatus'  => 'fk_statut IN (0,1,2)'
            ),
            'facture'  => array(
                'label'        => 'ClientShadowStatInvoices',
                'whereStatus'  => 'fk_statut IN (0,1,2)'
            )
        );

        $results = array();
        foreach ($tables as $table => $conf) {
            $results[$table] = array(
                'label' => $conf['label'],
                'data'  => array(
                    0 => 0,
                    1 => 0
                )
            );

            $sql = "SELECT shadow_mode, SUM(total_ht) as amount FROM ".MAIN_DB_PREFIX.$table;
            $sql .= " WHERE fk_soc = ".$socid;
            $sql .= " AND entity IN (".getEntity($table).")";
            if (!empty($conf['whereStatus'])) {
                $sql .= " AND ".$conf['whereStatus'];
            }
            $sql .= " GROUP BY shadow_mode";

            $res = $this->db->query($sql);
            if ($res) {
                while ($obj = $this->db->fetch_object($res)) {
                    $key = (int) $obj->shadow_mode;
                    $results[$table]['data'][$key] = (float) $obj->amount;
                }
                $this->db->free($res);
            }
        }

        return $results;
    }

    /**
     * Return total TTC of shadow invoices considered as outstanding
     *
     * @param int $socid
     * @return float
     */
    public function getShadowOutstandingInvoices($socid)
    {
        $socid = (int) $socid;
        if (!$socid) {
            return 0;
        }

        $sql = "SELECT SUM(total_ttc) as amount FROM ".MAIN_DB_PREFIX."facture";
        $sql .= " WHERE shadow_mode = 1";
        $sql .= " AND fk_soc = ".$socid;
        $sql .= " AND fk_statut IN (0, 1, 2)";
        $sql .= " AND entity IN (".getEntity('invoice').")";

        $res = $this->db->query($sql);
        if ($res) {
            $obj = $this->db->fetch_object($res);
            if ($obj && !empty($obj->amount)) {
                return (float) $obj->amount;
            }
        }

        return 0;
    }
}
