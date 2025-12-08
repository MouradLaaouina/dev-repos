<?php
/**
 * Module descriptor for ExpeditionPayment
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modexpeditionpayment extends DolibarrModules
{
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;

        $this->numero = 122189500; // unique
        $this->rights_class = 'expeditionpayment';
        $this->family = 'financial';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = 'ExpeditionPaymentDesc';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'payment';
        $this->module_parts = array(
            'class' => 1,
            'triggers' => 0
        );

        $this->dirs = array('/expeditionpayment');
        $this->config_page_url = array();

        $this->depends = array('modExpedition', 'modSociete');
        $this->conflictwith = array();

        $this->rights = array();
        $r = 0;
        $this->rights[$r][0] = 122189500;
        $this->rights[$r][1] = 'Lire les paiements expedition';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';
        $r++;
        $this->rights[$r][0] = 122189501;
        $this->rights[$r][1] = 'Créer des paiements expedition';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;
        $this->rights[$r][0] = 122189502;
        $this->rights[$r][1] = 'Annuler des paiements expedition';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'cancel';

        $this->module_parts['hooks'] = array('expeditioncard', 'globalcard', 'thirdpartycard');

        $this->menu = array();
        $m = 0;
        $this->menu[$m] = array(
            'fk_menu' => 'fk_mainmenu=ship',
            'type' => 'left',
            'titre' => 'ExpeditionPaymentMenu',
            'url' => '/expeditionpayment/list.php',
            'langs' => 'expeditionpayment@expeditionpayment',
            'position' => 1200,
            'enabled' => '$conf->expeditionpayment->enabled',
            'perms' => '$user->rights->expeditionpayment->read',
            'target' => '',
            'user' => 2
        );
    }

    public function init($options = '')
    {
        $this->cleanMenus();
        $result = $this->_load_tables('/expeditionpayment/sql/');
        if ($result < 0) {
            return -1;
        }
        return $this->_init(array(), $options);
    }

    public function remove($options = '')
    {
        global $conf;
        $this->cleanMenus();
        dolibarr_del_const($this->db, $this->const_name, $conf->entity);
        return $this->_remove($options);
    }

    private function cleanMenus()
    {
        $sql = "DELETE FROM ".$this->db->prefix()."menu WHERE module='".$this->db->escape($this->name)."' OR url LIKE '/expeditionpayment/%'";
        $this->db->query($sql);
    }
}
