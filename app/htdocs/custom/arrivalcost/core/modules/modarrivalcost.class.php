<?php
/* Copyright (C) 2024  Codex */

/**
 *  \file       arrivalcost/core/modules/modarrivalcost.class.php
 *  \ingroup    arrivalcost
 *  \brief      Description and activation file for module ArrivalCost
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module ArrivalCost
 */
class modarrivalcost extends DolibarrModules
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

        $this->numero = 122189400; // ensure unique
        $this->rights_class = 'arrivalcost';
        $this->family = 'financial';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = 'ModuleArrivalcostDesc';
        $this->langfiles = array('arrivalcost@arrivalcost');
        $this->editor_name = 'Codex';
        $this->editor_url = 'https://openai.com';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'generic';
        $this->module_parts = array(
            'class' => 1
        );

        $this->dirs = array('/arrivalcost');
        $this->config_page_url = array('arrivalcost_setup.php@arrivalcost');

        $this->depends = array('modFournisseur', 'modProduct');
        $this->conflictwith = array();

        $this->rights = array();
        $r = 0;
        $this->rights[$r][0] = 122189400;
        $this->rights[$r][1] = 'Gerer les frais d arrivage';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'write';

        $this->const = array(
            array(
                'ARRIVALCOST_DEFAULT_MODE',
                'chaine',
                'amount',
                'Mode de repartition par defaut (amount ou qty)',
                0,
                $conf->entity
            )
        );

        $this->menu = array();
        $m = 0;

        $this->menu[$m] = array(
            'fk_menu' => 0,
            'type' => 'top',
            'titre' => 'ArrivalCostMenu',
            'mainmenu' => 'arrivalcost',
            'url' => '/arrivalcost/arrivalcost.php',
            'langs' => 'arrivalcost@arrivalcost',
            'position' => 1000,
            'enabled' => '$conf->arrivalcost->enabled',
            'perms' => '$user->rights->arrivalcost->write',
            'target' => '',
            'user' => 2
        );
        $m++;

        $this->menu[$m] = array(
            'fk_menu' => 'fk_mainmenu=arrivalcost',
            'type' => 'left',
            'titre' => 'ArrivalCostList',
            'leftmenu' => 'arrivalcost_left',
            'url' => '/arrivalcost/arrivalcost.php',
            'langs' => 'arrivalcost@arrivalcost',
            'position' => 1001,
            'enabled' => '$conf->arrivalcost->enabled',
            'perms' => '$user->rights->arrivalcost->write',
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

        $result = $this->_load_tables('/arrivalcost/sql/');
        if ($result < 0) {
            return -1;
        }

        dol_include_once('/arrivalcost/class/arrivalcost.class.php');
        $helper = new ArrivalCost($this->db);
        $helper->ensureSchemaUpgrade();
        $helper->ensureLineExtraFields();

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

        // Clean extrafields
        dol_include_once('/arrivalcost/class/arrivalcost.class.php');
        $helper = new ArrivalCost($this->db);
        $helper->deleteLineExtraFields();

        // Drop module constants explicitly
        dolibarr_del_const($this->db, 'ARRIVALCOST_DEFAULT_MODE', $conf->entity);

        $this->cleanMenus();

        $res = $this->_remove($options);
        if ($res < 0) {
            // Force disable even if clean-up fails
            dolibarr_del_const($this->db, $this->const_name, $conf->entity);
            return 1;
        }

        return $res;
    }

    /**
     * Remove existing menus for this module to avoid duplicates
     *
     * @return void
     */
    private function cleanMenus()
    {
        $sql = "DELETE FROM ".$this->db->prefix()."menu";
        $sql .= " WHERE module = '".$this->db->escape($this->name)."'";
        $sql .= " OR url LIKE '/arrivalcost/%'";
        $this->db->query($sql);
    }
}
