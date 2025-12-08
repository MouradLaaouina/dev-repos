<?php
/**
 * Module descriptor for OrderPayment
 * Allows to register payments directly on sales orders without creating invoices
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modorderpayment extends DolibarrModules
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $conf, $langs;

        $this->db = $db;

        // Module unique id
        $this->numero = 122189600;

        // Family
        $this->family = "financial";

        // Module position in administration menu
        $this->module_position = '90';

        // Module label
        $this->name = preg_replace('/^mod/i', '', get_class($this));

        // Module description
        $this->description = "OrderPaymentDesc";

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string
        $this->version = '1.0.0';

        // Author
        $this->editor_name = 'Custom';
        $this->editor_url = '';

        // Url to the file with your last numberversion of this module
        $this->url_last_version = '';

        // Key used in llx_const table to save module status enabled/disabled
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

        // Name of image file used for this module (stored in htdocs/custom/orderpayment/img)
        $this->picto = 'payment';

        // Define some features supported by module
        $this->module_parts = array(
            'triggers' => 0,
            'login' => 0,
            'substitutions' => 0,
            'menus' => 0,
            'tpl' => 0,
            'barcode' => 0,
            'models' => 0,
            'theme' => 0,
            'css' => array(),
            'js' => array(),
            'hooks' => array(
                'ordercard',
                'thirdpartycard',
                'thirdpartycomm',
                'paiementcard',
                'paymentcard',
                'globalcard'
            ),
            'moduleforexternal' => 0,
        );

        // Data directories to create when module is enabled
        $this->dirs = array("/orderpayment/temp");

        // Config pages
        $this->config_page_url = array();

        // Dependencies
        $this->hidden = false;
        $this->depends = array('modCommande');
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array("orderpayment@orderpayment");

        // Constants
        $this->const = array();

        // Permission array used by this module
        if (!isset($conf->orderpayment) || !isset($conf->orderpayment->enabled)) {
            $conf->orderpayment = new stdClass();
            $conf->orderpayment->enabled = 0;
        }

        $this->rights = array();
        $this->rights_class = 'orderpayment';

        $r = 0;

        // Permission to read
        $this->rights[$r][0] = $this->numero + 0;
        $this->rights[$r][1] = 'Lire les paiements commandes';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';
        $this->rights[$r][5] = '';
        $r++;

        // Permission to create/modify
        $this->rights[$r][0] = $this->numero + 1;
        $this->rights[$r][1] = 'Créer/Modifier les paiements commandes';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $this->rights[$r][5] = '';
        $r++;

        // Permission to cancel
        $this->rights[$r][0] = $this->numero + 2;
        $this->rights[$r][1] = 'Annuler les paiements commandes';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'cancel';
        $this->rights[$r][5] = '';

        // Main menu entries
        $this->menu = array();
        $r = 0;

        // Menu in left menu under orders
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=commercial,fk_leftmenu=orders',
            'type' => 'left',
            'titre' => 'OrderPaymentList',
            'prefix' => img_picto('', 'payment', 'class="paddingright pictofixedwidth"'),
            'mainmenu' => 'commercial',
            'leftmenu' => 'orderpayment_list',
            'url' => '/orderpayment/list.php',
            'langs' => 'orderpayment@orderpayment',
            'position' => 1000 + $r,
            'enabled' => 'isModEnabled("orderpayment") && isModEnabled("commande")',
            'perms' => '$user->hasRight("orderpayment", "read")',
            'target' => '',
            'user' => 2,
        );

        // New payment menu
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=commercial,fk_leftmenu=orderpayment_list',
            'type' => 'left',
            'titre' => 'NewOrderPayment',
            'mainmenu' => 'commercial',
            'leftmenu' => 'orderpayment_new',
            'url' => '/orderpayment/card.php?action=create',
            'langs' => 'orderpayment@orderpayment',
            'position' => 1000 + $r,
            'enabled' => 'isModEnabled("orderpayment") && isModEnabled("commande")',
            'perms' => '$user->hasRight("orderpayment", "write")',
            'target' => '',
            'user' => 2,
        );

        // Tabs
        $this->tabs = array();
        $this->tabs[] = array('data' => 'order:+taborderpayments:TabOrderPayments:orderpayment@orderpayment:$user->hasRight("orderpayment", "read"):/orderpayment/orderpayment_tab.php?id=__ID__');
        $this->tabs[] = array('data' => 'thirdparty:+tabreturnedchecks:ReturnedChecks:orderpayment@orderpayment:$user->hasRight("orderpayment", "read"):/orderpayment/returnedchecks_tab.php?socid=__ID__');
    }

    /**
     * Function called when module is enabled
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return int             1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $conf;

        $result = $this->_load_tables('/orderpayment/sql/');
        if ($result < 0) {
            return -1;
        }

        // Add sanction columns if they don't exist (for existing installations)
        $this->addSanctionColumns();

        // Create extrafields during init
        // include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        // $extrafields = new ExtraFields($this->db);

        $sql = array();

        return $this->_init($sql, $options);
    }

    /**
     * Add sanction columns to order_payment table if they don't exist
     * Used by customercheck module for returned checks management
     */
    private function addSanctionColumns()
    {
        // Check if sanction_lifted column exists
        $sql = "SHOW COLUMNS FROM ".$this->db->prefix()."order_payment LIKE 'sanction_lifted'";
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) == 0) {
            // Add sanction columns
            $sqls = array(
                "ALTER TABLE ".$this->db->prefix()."order_payment ADD COLUMN sanction_lifted TINYINT(1) DEFAULT 0 NOT NULL",
                "ALTER TABLE ".$this->db->prefix()."order_payment ADD COLUMN date_sanction_lifted DATETIME DEFAULT NULL",
                "ALTER TABLE ".$this->db->prefix()."order_payment ADD COLUMN fk_user_sanction_lifted INTEGER DEFAULT NULL",
                "ALTER TABLE ".$this->db->prefix()."order_payment ADD COLUMN note_sanction TEXT DEFAULT NULL"
            );
            foreach ($sqls as $sql) {
                $this->db->query($sql);
            }
        }
    }

    /**
     * Function called when module is disabled
     *
     * @param string $options Options when disabling module ('', 'noboxes')
     * @return int             1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }
}
