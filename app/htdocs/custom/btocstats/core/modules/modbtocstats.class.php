<?php
/* Copyright (C) 2024  Codex */

/**
 *  \file       btocstats/core/modules/modbtocstats.class.php
 *  \ingroup    btocstats
 *  \brief      Description and activation file for module BTOCStats
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module BTOCStats.
 */
class modbtocstats extends DolibarrModules
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

        $this->numero = 122189300; // ensure unique
        $this->rights_class = 'btocstats';
        $this->family = 'crm';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = 'ModuleBtocstatsDesc';
        $this->descriptionlong = 'ModuleBtocstatsDescLong';
        $this->langfiles = array('btocstats@btocstats');
        $this->editor_name = 'Codex';
        $this->editor_url = 'https://openai.com';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special = 0;
        $this->picto = 'stats';
        $this->module_parts = array(
            'triggers' => 0
        );

        $this->dirs = array('/btocstats');
        $this->config_page_url = array();

        $this->depends = array();
        $this->conflictwith = array();

        $this->rights = array();
        $r = 0;
        $this->rights[$r][0] = 122189301;
        $this->rights[$r][1] = 'BTOCStatsRead';
        $this->rights[$r][3] = 0; // not granted by default
        $this->rights[$r][4] = 'read';

        $r++;
        $this->rights[$r][0] = 122189302;
        $this->rights[$r][1] = 'BTOCStatsWrite';
        $this->rights[$r][3] = 0; // not granted by default
        $this->rights[$r][4] = 'write';
    }

    /**
     * Function called when module is enabled.
     *
     * @param string $options Options when enabling the module ('', 'noboxes')
     * @return int            1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $sql = array();
        $result = $this->_load_tables('/btocstats/sql/');
        if ($result < 0) return 0;

        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     *
     * @param string $options Options when disabling the module ('', 'noboxes')
     * @return int            1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        return $this->_remove($options);
    }
}
