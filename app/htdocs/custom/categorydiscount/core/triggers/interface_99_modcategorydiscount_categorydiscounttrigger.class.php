<?php
/**
 * Trigger file for CategoryDiscount module
 */

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

dol_include_once('/categorydiscount/class/categorydiscountrule.class.php');

class Interfacecategorydiscounttrigger
{
    /** @var DoliDB */
    protected $db;
    public $error;
    public $errors = array();

    public function __construct($db)
    {
        $this->db = $db;
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->description = "Triggers for CategoryDiscount module";
        $this->version = '1.0';
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
        switch ($action) {
            case 'LINEORDER_INSERT':
            case 'LINEBILL_INSERT':
            case 'LINEPROPAL_INSERT':
                $this->handleLineInsert($action, $object, $user, $conf);
                break;
        }

        return 0;
    }

    /**
     * Apply discount when an eligible line is created
     *
     * @param string $action
     * @param CommonObjectLine $line
     * @param User $user
     * @param Conf $conf
     * @return void
     */
    private function handleLineInsert($action, $line, $user, $conf)
    {
        if (empty($line->fk_product)) {
            return;
        }

        $socId = $this->resolveSocId($action, $line);
        if (!$socId) {
            return;
        }

        $ruleHelper = new CategoryDiscountRule($this->db);
        $entity = !empty($line->entity) ? (int) $line->entity : $conf->entity;
        $match = $ruleHelper->fetchBestRuleForProduct($socId, $line->fk_product, $entity);
        if (!$match) {
            return;
        }

        $newDiscount = (float) $match['discount_percent'];
        if ($newDiscount <= 0) {
            return;
        }

        // Do not override a higher manual discount
        if (!empty($line->remise_percent) && (float) $line->remise_percent >= $newDiscount - 0.0001) {
            return;
        }

        $this->applyDiscountOnLine($action, $line, $newDiscount, $user, $entity);
    }

    /**
     * Resolve thirdparty id from line + action
     *
     * @param string $action
     * @param CommonObjectLine $line
     * @return int
     */
    private function resolveSocId($action, $line)
    {
        if (!empty($line->fk_soc)) {
            return (int) $line->fk_soc;
        }
        if (!empty($line->socid)) {
            return (int) $line->socid;
        }

        $parentId = 0;
        $parent = null;

        if ($action === 'LINEORDER_INSERT' && !empty($line->fk_commande)) {
            $parent = new Commande($this->db);
            if ($parent->fetch($line->fk_commande) > 0) {
                $parentId = $parent->id;
            }
        } elseif ($action === 'LINEBILL_INSERT' && !empty($line->fk_facture)) {
            $parent = new Facture($this->db);
            if ($parent->fetch($line->fk_facture) > 0) {
                $parentId = $parent->id;
            }
        } elseif ($action === 'LINEPROPAL_INSERT' && !empty($line->fk_propal)) {
            $parent = new Propal($this->db);
            if ($parent->fetch($line->fk_propal) > 0) {
                $parentId = $parent->id;
            }
        }

        if ($parent && $parentId > 0 && !empty($parent->socid)) {
            return (int) $parent->socid;
        }

        return 0;
    }

    /**
     * Apply the discount on the line and refresh totals
     *
     * @param string $action
     * @param CommonObjectLine $line
     * @param float $discount
     * @param User $user
     * @param int $entity
     * @return void
     */
    private function applyDiscountOnLine($action, $line, $discount, $user, $entity)
    {
        global $mysoc;

        // Ensure rowid is set for update()
        if (empty($line->rowid) && !empty($line->id)) {
            $line->rowid = $line->id;
        }

        $line->remise_percent = $discount;
        $line->product_type = isset($line->product_type) ? $line->product_type : (!empty($line->fk_product_type) ? $line->fk_product_type : 0);

        $tabprice = calcul_price_total(
            $line->qty,
            $line->subprice,
            $line->remise_percent,
            $line->tva_tx,
            $line->localtax1_tx,
            $line->localtax2_tx,
            0,
            'HT',
            $line->info_bits,
            $line->product_type,
            $mysoc
        );

        $line->total_ht = (float) $tabprice[0];
        $line->total_tva = (float) $tabprice[1];
        $line->total_ttc = (float) $tabprice[2];
        $line->total_localtax1 = (float) $tabprice[9];
        $line->total_localtax2 = (float) $tabprice[10];
        $line->multicurrency_total_ht = isset($tabprice[16]) ? (float) $tabprice[16] : 0;
        $line->multicurrency_total_tva = isset($tabprice[17]) ? (float) $tabprice[17] : 0;
        $line->multicurrency_total_ttc = isset($tabprice[18]) ? (float) $tabprice[18] : 0;

        // Avoid re-triggering by skipping triggers on the line update
        $line->update($user, 1);

        // Refresh document totals
        if ($action === 'LINEORDER_INSERT' && !empty($line->fk_commande)) {
            $commande = new Commande($this->db);
            if ($commande->fetch($line->fk_commande) > 0) {
                $commande->update_price(1);
            }
        } elseif ($action === 'LINEBILL_INSERT' && !empty($line->fk_facture)) {
            $facture = new Facture($this->db);
            if ($facture->fetch($line->fk_facture) > 0) {
                $facture->update_price(1);
            }
        } elseif ($action === 'LINEPROPAL_INSERT' && !empty($line->fk_propal)) {
            $propal = new Propal($this->db);
            if ($propal->fetch($line->fk_propal) > 0) {
                $propal->update_price(1);
            }
        }
    }
}
