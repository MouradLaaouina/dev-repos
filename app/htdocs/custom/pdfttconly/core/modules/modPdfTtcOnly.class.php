<?php
/**
 * Module descriptor for PDF TTC Only
 * Displays only TTC (total including tax) in PDFs, hiding HT and VAT
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modPdfTtcOnly extends DolibarrModules
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

        $this->numero = 122189700;
        $this->rights_class = 'pdfttconly';
        $this->family = "other";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Modèles PDF personnalisés (TTC uniquement, contact uniquement)";
        $this->descriptionlong = "Ce module ajoute des modèles PDF personnalisés : affichage TTC uniquement (devis, commandes, BL) et affichage du nom du contact uniquement (factures).";
        $this->editor_name = 'Custom';
        $this->editor_url = '';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'pdf';

        // Module parts
        $this->module_parts = array(
            'hooks' => array(
                'pdfgeneration'
            ),
            // Register PDF models
            'models' => 1
        );

        // Config pages
        $this->config_page_url = array("setup.php@pdfttconly");

        // Dependencies
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();

        // Constants
        $this->const = array(
            array('PDFTTCONLY_ENABLED_PROPAL', 'chaine', '1', 'Enable TTC only for proposals', 0, 'current', 1),
            array('PDFTTCONLY_ENABLED_ORDER', 'chaine', '1', 'Enable TTC only for orders', 0, 'current', 1),
            array('PDFTTCONLY_ENABLED_SHIPMENT', 'chaine', '1', 'Enable TTC only for shipments', 0, 'current', 1),
        );

        // Tabs
        $this->tabs = array();

        // Dictionaries
        $this->dictionaries = array();

        // Boxes
        $this->boxes = array();

        // Permissions
        $this->rights = array();

        // Menus
        $this->menu = array();
    }

    /**
     * Function called when module is enabled
     *
     * @param string $options Options when enabling module
     * @return int 1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $conf;

        $result = $this->_load_tables('/pdfttconly/sql/');
        if ($result < 0) {
            return -1;
        }

        // Add PDF models to document_model table
        // Note: types must match what Dolibarr uses in modules_*.php:
        // - propal for proposals (propale/modules_propale.php)
        // - order for orders (commande/modules_commande.php)
        // - shipping for shipments (expedition/modules_expedition.php)
        $this->_addDocumentModel('azur_ttc', 'propal', 'Azur TTC - Sans HT ni TVA');
        $this->_addDocumentModel('einstein_ttc', 'order', 'Einstein TTC - Sans HT ni TVA');
        $this->_addDocumentModel('espadon_ttc', 'shipping', 'Espadon TTC - Sans HT ni TVA');
        $this->_addDocumentModel('crabe_contact', 'invoice', 'Crabe Contact - Nom du contact uniquement');

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
        // Remove PDF models from document_model table
        $this->_removeDocumentModel('azur_ttc', 'propal');
        $this->_removeDocumentModel('einstein_ttc', 'order');
        $this->_removeDocumentModel('espadon_ttc', 'shipping');
        $this->_removeDocumentModel('crabe_contact', 'invoice');

        $sql = array();

        return $this->_remove($sql, $options);
    }

    /**
     * Add a document model to the database
     *
     * @param string $name Model name
     * @param string $type Document type (propal, commande, expedition)
     * @param string $label Model label
     * @return int 1 if OK, -1 if KO
     */
    private function _addDocumentModel($name, $type, $label)
    {
        global $conf;

        // Check if model already exists
        $sql = "SELECT rowid FROM ".$this->db->prefix()."document_model";
        $sql .= " WHERE nom = '".$this->db->escape($name)."'";
        $sql .= " AND type = '".$this->db->escape($type)."'";
        $sql .= " AND entity IN (0, ".((int) $conf->entity).")";

        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) > 0) {
            return 1; // Already exists
        }

        // Insert new model
        $sql = "INSERT INTO ".$this->db->prefix()."document_model (";
        $sql .= "nom, entity, type, libelle";
        $sql .= ") VALUES (";
        $sql .= "'".$this->db->escape($name)."', ";
        $sql .= ((int) $conf->entity).", ";
        $sql .= "'".$this->db->escape($type)."', ";
        $sql .= "'".$this->db->escape($label)."'";
        $sql .= ")";

        if ($this->db->query($sql)) {
            return 1;
        }
        return -1;
    }

    /**
     * Remove a document model from the database
     *
     * @param string $name Model name
     * @param string $type Document type
     * @return int 1 if OK, -1 if KO
     */
    private function _removeDocumentModel($name, $type)
    {
        global $conf;

        $sql = "DELETE FROM ".$this->db->prefix()."document_model";
        $sql .= " WHERE nom = '".$this->db->escape($name)."'";
        $sql .= " AND type = '".$this->db->escape($type)."'";
        $sql .= " AND entity IN (0, ".((int) $conf->entity).")";

        if ($this->db->query($sql)) {
            return 1;
        }
        return -1;
    }
}
