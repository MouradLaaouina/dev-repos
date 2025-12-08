<?php
/* Copyright (C) 2024  Codex */

/**
 *    \file       clientshadow/core/modules/modclientshadow.class.php
 *    \ingroup    clientshadow
 *    \brief      Description and activation file for module ClientShadow
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module ClientShadow
 */
class modclientshadow extends DolibarrModules
{
    /**
     *   Constructor. Define names, constants, directories, boxes, permissions
     *
     *   @param      DoliDB  $db  Database handler
     */
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;

        $this->numero      = 122189001; // ensure unique
        $this->rights_class = 'clientshadow';
        $this->family       = 'financial';
        $this->name         = preg_replace('/^mod/i', '', get_class($this));
        $this->description  = 'ModuleClientshadowDesc';
        $this->langfiles    = array('clientshadow@clientshadow');
        $this->editor_name  = 'Codex';
        $this->editor_url   = 'https://openai.com';
        $this->version      = '1.0.0';
        $this->const_name   = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special      = 0;
        $this->picto        = 'object_bill';
        $this->module_parts = array(
            'triggers'      => 1,
            'hooks'         => array('thirdpartycard', 'thirdpartycomm', 'invoicecard', 'ordersuppliercard', 'ordercard', 'shippingcard', 'stockproductcard'),
            'class'         => 1,
            'tpl'           => 0
        );

        $this->dirs = array('/clientshadow');
        $this->config_page_url = array('clientshadow_setup.php@clientshadow');

        $this->depends   = array('modSociete', 'modFacture', 'modExpedition', 'modCommande');
        $this->conflictwith = array();

        $this->rights = array();
        $r = 0;
        $this->rights[$r][0] = 122189001;
        $this->rights[$r][1] = 'Gerer le mode shadow des clients';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'manage';
        $r++;
        $this->rights[$r][0] = 122189002;
        $this->rights[$r][1] = 'Convertir un client B vers A (sortir du mode shadow)';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'convert';

        $this->menu = array();

        $this->const = array(array(
            'CLIENTSHADOW_DEFAULT_TYPE',
            'chaine',
            'A',
            'Type par défaut appliqué aux nouveaux tiers',
            0,
            $conf->entity
        ));

        $this->menu = array();
    }

    /**
     * Function called when module is enabled.
     *
     * @param  string $options Options when enabling the module ('', 'noboxes')
     * @return int             1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $this->cleanMenus();

        global $conf;

        $result = $this->_load_tables('/clientshadow/sql/');
        if ($result < 0) {
            return -1;
        }

        $this->addClientShadowExtracField();
        $this->ensureDocumentColumns();

        return $this->_init(array(), $options);
    }

    /**
     * Function called when module is disabled.
     *
     * @param  string $options Options when disabling the module ('', 'noboxes')
     * @return int             1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        global $conf;

        $this->cleanMenus();
        dolibarr_del_const($this->db, 'CLIENTSHADOW_DEFAULT_TYPE', $conf->entity);
        dolibarr_del_const($this->db, $this->const_name, $conf->entity);

        return $this->_remove($options);
    }

    /**
     * Add the extrafield on thirdparties
     *
     * @return void
     */
    private function addClientShadowExtracField()
    {
        global $conf, $langs;

        $langs->load('clientshadow@clientshadow');

        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);

        $attributes = $extrafields->fetch_name_optionals_label('societe');
        if (!isset($attributes['clientshadow_type'])) {
            $options = array(
                'A'=>$langs->trans('clientshadowTypeA'),
                'B'=>$langs->trans('clientshadowTypeB')
            );
            $extrafields->addExtraField(
                'clientshadow_type',
                'Client Shadow Type',
                'select',
                1,
                '',
                'societe',
                0,
                0,
                '',
                array('options'=>$options),
                1,
                '',
                $conf->entity
            );
        }
    }

    /**
     * Ensure shadow columns exist on documents
     *
     * @return void
     */
    private function ensureDocumentColumns()
    {
        $tables = array('commande', 'expedition', 'facture');
        foreach ($tables as $tablename) {
            $sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX.$tablename." LIKE 'shadow_mode'";
            $res = $this->db->query($sql);
            if ($res && $this->db->num_rows($res) == 0) {
                $alter = "ALTER TABLE ".MAIN_DB_PREFIX.$tablename." ADD shadow_mode TINYINT(1) NOT NULL DEFAULT 0";
                $this->db->query($alter);
            }
        }
    }

    /**
     * Remove module menus to avoid duplicates and unblock uninstall
     *
     * @return void
     */
    private function cleanMenus()
    {
        $sql = "DELETE FROM ".$this->db->prefix()."menu";
        $sql .= " WHERE module = '".$this->db->escape($this->name)."'";
        $sql .= " OR url LIKE '/clientshadow/%'";
        $this->db->query($sql);
    }
}
