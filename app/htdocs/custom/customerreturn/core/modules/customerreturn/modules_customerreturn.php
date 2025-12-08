<?php
/**
 * Parent class for customer return document generators
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';

/**
 * Parent class for customer return document models
 */
abstract class ModeleNumRefCustomerReturn
{
    /**
     * @var string Error message
     */
    public $error = '';

    /**
     * Return if a model can be used or not
     *
     * @return boolean true if model can be used
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * Return description of numbering model
     *
     * @return string Description
     */
    abstract public function info();

    /**
     * Return next value
     *
     * @param Societe $objsoc Object third party
     * @param CustomerReturn $object Object to get next value for
     * @return string|int Next value, -1 if KO
     */
    abstract public function getNextValue($objsoc, $object);

    /**
     * Return version of numbering module
     *
     * @return string Version
     */
    public function getVersion()
    {
        return $this->version;
    }
}

/**
 * Parent class for customer return PDF document models
 */
abstract class ModelePDFCustomerReturn extends CommonDocGenerator
{
    /**
     * @var string Error message
     */
    public $error = '';

    /**
     * Return list of available PDF models
     *
     * @param DoliDB $db Database handler
     * @param int $maxfilenamelength Max length of filename
     * @return array List of available models
     */
    public static function liste_modeles($db, $maxfilenamelength = 0)
    {
        $type = 'customerreturn';
        $list = array();

        include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
        $list = getListOfModels($db, $type, $maxfilenamelength);

        return $list;
    }
}
