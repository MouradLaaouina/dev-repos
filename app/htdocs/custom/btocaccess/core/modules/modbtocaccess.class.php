<?php
/* Copyright (C) 2024  Codex */

/**
 *  \file       btocaccess/core/modules/modbtocaccess.class.php
 *  \ingroup    btocaccess
 *  \brief      Description and activation file for module BTOCAccess
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module BTOCAccess.
 */
class modbtocaccess extends DolibarrModules
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

        $this->numero = 122189200; // ensure unique
        $this->rights_class = 'btocaccess';
        $this->family = 'crm';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = 'ModuleBtocaccessDesc';
        $this->descriptionlong = 'ModuleBtocaccessDescLong';
        $this->langfiles = array('btocaccess@btocaccess');
        $this->editor_name = 'Codex';
        $this->editor_url = 'https://openai.com';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special = 0;
        $this->picto = 'technic';
        $this->module_parts = array(
            'triggers' => 0
        );

        $this->dirs = array('/btocaccess');
        $this->config_page_url = array();

        $this->depends = array();
        $this->conflictwith = array();

        $this->rights = array();
        $r = 0;
        $this->rights[$r][0] = 122189201;
        $this->rights[$r][1] = 'BTOCAccessPermission';
        $this->rights[$r][3] = 0; // not granted by default
        $this->rights[$r][4] = 'access';
    }

    /**
     * Function called when module is enabled.
     *
     * @param string $options Options when enabling the module ('', 'noboxes')
     * @return int            1 if OK, 0 if KO
     */
    public function init($options = '')
    {
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
        return $this->_remove($options);
    }
}
