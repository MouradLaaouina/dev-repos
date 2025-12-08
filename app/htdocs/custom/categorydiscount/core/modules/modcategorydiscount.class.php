<?php
/* Copyright (C) 2024  Codex */

/**
 *  \file       categorydiscount/core/modules/modcategorydiscount.class.php
 *  \ingroup    categorydiscount
 *  \brief      Description and activation file for module CategoryDiscount
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module CategoryDiscount
 */
class modcategorydiscount extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;

        $this->numero = 122189010; // ensure unique
        $this->rights_class = 'categorydiscount';
        $this->family = 'crm';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = 'ModuleCategorydiscountDesc';
        $this->langfiles = array('categorydiscount@categorydiscount');
        $this->editor_name = 'Codex';
        $this->editor_url = 'https://openai.com';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'discount';
        $this->module_parts = array(
            'triggers' => 1,
            'class' => 1,
            'api' => 1
        );

        $this->dirs = array('/categorydiscount');
        $this->config_page_url = array('categorydiscount_setup.php@categorydiscount');

        $this->depends = array('modSociete', 'modProduct');
        $this->conflictwith = array();

        $this->rights = array();
        $r = 0;
        $this->rights[$r][0] = 122189010;
        $this->rights[$r][1] = 'Configurer les remises par catégorie produit';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'write';

        $this->menu = array();
        $m = 0;
        $this->menu[$m] = array(
            'fk_menu' => 'fk_mainmenu=products',
            'type' => 'left',
            'titre' => 'CategoryDiscountSetup',
            'url' => '/categorydiscount/admin/categorydiscount_setup.php',
            'langs' => 'categorydiscount@categorydiscount',
            'position' => 1100,
            'enabled' => '$conf->categorydiscount->enabled',
            'perms' => '$user->rights->categorydiscount->write',
            'target' => '',
            'user' => 2
        );
    }

    /**
     * Function called when module is enabled.
     *
     * @param string $options Options when enabling the module ('', 'noboxes')
     * @return int            1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $this->cleanMenus();

        $result = $this->_load_tables('/categorydiscount/sql/');
        if ($result < 0) {
            return -1;
        }

        return $this->_init(array(), $options);
    }

    /**
     * Function called when module is disabled.
     *
     * @param string $options Options when disabling the module ('', 'noboxes')
     * @return int            1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        global $conf;

        $this->cleanMenus();

        // Drop constants of the module
        dolibarr_del_const($this->db, $this->const_name, $conf->entity);

        return $this->_remove($options);
    }

    /**
     * Remove menus for this module (avoid duplicates on reinstall)
     *
     * @return void
     */
    private function cleanMenus()
    {
        $sql = "DELETE FROM ".$this->db->prefix()."menu";
        $sql .= " WHERE module = '".$this->db->escape($this->name)."'";
        $sql .= " OR url LIKE '/categorydiscount/%'";
        $this->db->query($sql);
    }
}
