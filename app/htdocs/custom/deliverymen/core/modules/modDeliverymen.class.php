<?php
/**
 * Module descriptor for Deliverymen
 * Manage delivery assignments to shipments
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modDeliverymen extends DolibarrModules
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        $this->numero = 122189900;
        $this->rights_class = 'deliverymen';
        $this->family = "products";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Gestion des livreurs et affectation aux expéditions";
        $this->descriptionlong = "Ce module permet d'affecter des livreurs (utilisateurs Dolibarr) aux bons d'expédition et d'exporter les états journaliers.";
        $this->editor_name = 'Custom';
        $this->editor_url = '';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'user';

        // Module parts
        $this->module_parts = array(
            'hooks' => array(
                'expeditioncard',
                'shipmentcard'
            )
        );

        // Config pages
        $this->config_page_url = array("setup.php@deliverymen");

        // Dependencies
        $this->depends = array('modExpedition');
        $this->requiredby = array();
        $this->conflictwith = array();

        // Constants
        $this->const = array();

        // Tabs - add tab on shipment card
        $this->tabs = array();

        // Dictionaries
        $this->dictionaries = array();

        // Boxes
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $r = 0;

        // Permission to read
        $this->rights[$r][0] = 1221899001;
        $this->rights[$r][1] = 'Voir les affectations livreurs';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1; // Default enabled
        $this->rights[$r][4] = 'read';
        $this->rights[$r][5] = '';
        $r++;

        // Permission to assign
        $this->rights[$r][0] = 1221899002;
        $this->rights[$r][1] = 'Affecter un livreur';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'assign';
        $this->rights[$r][5] = '';
        $r++;

        // Permission to export
        $this->rights[$r][0] = 1221899003;
        $this->rights[$r][1] = 'Exporter les états livreurs';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'export';
        $this->rights[$r][5] = '';
        $r++;

        // Menus
        $this->menu = array();
        $r = 0;

        // Main menu entry
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=products',
            'type' => 'left',
            'titre' => 'Livreurs',
            'prefix' => img_picto('', 'user', 'class="paddingright pictofixedwidth"'),
            'mainmenu' => 'products',
            'leftmenu' => 'deliverymen',
            'url' => '/custom/deliverymen/list.php',
            'langs' => 'deliverymen@deliverymen',
            'position' => 200,
            'enabled' => 'isModEnabled("deliverymen")',
            'perms' => '$user->hasRight("deliverymen", "read")',
            'user' => 0
        );

        // Daily report submenu
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=products,fk_leftmenu=deliverymen',
            'type' => 'left',
            'titre' => 'EtatJournalier',
            'mainmenu' => 'products',
            'leftmenu' => 'deliverymen_daily',
            'url' => '/custom/deliverymen/daily_report.php',
            'langs' => 'deliverymen@deliverymen',
            'position' => 201,
            'enabled' => 'isModEnabled("deliverymen")',
            'perms' => '$user->hasRight("deliverymen", "read")',
            'user' => 0
        );
    }

    /**
     * Function called when module is enabled
     *
     * @param string $options Options when enabling module
     * @return int 1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $result = $this->_load_tables('/deliverymen/sql/');
        if ($result < 0) {
            return -1;
        }

        $sql = array();
        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled
     *
     * @param string $options Options when disabling module
     * @return int 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
