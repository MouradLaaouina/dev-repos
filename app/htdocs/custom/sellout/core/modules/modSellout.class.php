<?php
/**
 * Module descriptor for Sellout
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module Sellout
 */
class modSellout extends DolibarrModules
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;

        $this->numero = 122189200; // ensure unique
        $this->rights_class = 'sellout';
        $this->family = 'crm';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = 'Sellout logging module';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'generic';
        // Use default auto-discovery of API class: /sellout/class/api_sellout.class.php
        $this->module_parts = array(
            'api' => 1,
            'moduleforexternal' => 0
        );

        $this->dirs = array('/sellout');
        $this->config_page_url = array();

        $this->depends = array('modApi', 'modSociete', 'modProduct');
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array();

        $this->const = array();
        $this->boxes = array();

        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Lire les ventes sellout';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Créer des ventes sellout';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;

        $this->menu = array();
        $r = 0;

        // Top menu
        $this->menu[$r] = array(
            'fk_menu' => 0,
            'type' => 'top',
            'titre' => 'Sellout',
            'mainmenu' => 'sellout',
            'url' => '/sellout/index.php',
            'langs' => '',
            'position' => 1000,
            'enabled' => '$conf->sellout->enabled',
            'perms' => '$user->rights->sellout->read',
            'target' => '',
            'user' => 2
        );
        $r++;

        // Left menu
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu=sellout',
            'type' => 'left',
            'titre' => 'Sellout',
            'leftmenu' => 'sellout_list',
            'url' => '/sellout/index.php',
            'langs' => '',
            'position' => 1001,
            'enabled' => '$conf->sellout->enabled',
            'perms' => '$user->rights->sellout->read',
            'target' => '',
            'user' => 2
        );
    }

    /**
     * Function called when module is enabled
     *
     * @param string $options Options
     * @return int 1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $this->cleanMenus();

        $result = $this->_load_tables('/sellout/sql/');

        if ($result < 0) {
            return -1;
        }

        $this->ensureLocationColumns();

        return $this->_init(array(), $options);
    }

    /**
     * Function called when module is disabled
     *
     * @param string $options Options
     * @return int 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        global $conf;

        $this->cleanMenus();

        // Drop constants if any
        dolibarr_del_const($this->db, $this->const_name, $conf->entity);

        return $this->_remove($options);
    }

    /**
     * Ensure location columns exist on sellout table (for upgrades)
     *
     * @return void
     */
    private function ensureLocationColumns()
    {
        $fields = array(
            'location_label' => "ALTER TABLE ".MAIN_DB_PREFIX."sellout_sale ADD location_label VARCHAR(255) NULL",
            'location_latitude' => "ALTER TABLE ".MAIN_DB_PREFIX."sellout_sale ADD location_latitude DECIMAL(10,8) NULL",
            'location_longitude' => "ALTER TABLE ".MAIN_DB_PREFIX."sellout_sale ADD location_longitude DECIMAL(11,8) NULL"
        );

        foreach ($fields as $col => $alterSql) {
            $check = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."sellout_sale LIKE '".$this->db->escape($col)."'";
            $res = $this->db->query($check);
            if ($res && $this->db->num_rows($res) == 0) {
                $this->db->query($alterSql);
            }
        }
    }

    /**
     * Remove menus for this module to avoid duplicates during re-enable
     *
     * @return void
     */
    private function cleanMenus()
    {
        $sql = "DELETE FROM ".$this->db->prefix()."menu";
        $sql .= " WHERE module = '".$this->db->escape($this->name)."'";
        $sql .= " OR url LIKE '/sellout/%'";
        $this->db->query($sql);
    }
}
