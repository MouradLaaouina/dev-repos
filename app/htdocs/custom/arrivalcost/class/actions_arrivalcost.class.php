<?php
/**
 * Hooks for ArrivalCost
 */
class ActionsArrivalcost
{
    /** @var DoliDB */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Add shortcut button on supplier order card
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        return 0;
    }

    /**
     * Show allocated total on the order card
     *
     * @param  CommandeFournisseur $order
     * @return string
     */
    private function renderOrderBadge($order)
    {
        global $conf, $langs;

        $sql = "SELECT SUM(allocated_ht) as total_ht";
        $sql .= " FROM ".$this->db->prefix()."arrivalcost_line";
        $sql .= " WHERE fk_supplier_order = ".((int) $order->id);
        $sql .= " AND entity = ".((int) $conf->entity);

        $total = 0;
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj && $obj->total_ht !== null) {
                $total = (float) $obj->total_ht;
            }
        }

        if ($total <= 0) {
            return '';
        }

        return '<span class="marginleftonly badge badge-status4">'.$langs->trans('ArrivalCostAllocatedShort').' '.price($total, 0, $langs, 0, 0, 0, $conf->currency).'</span>';
    }
}
