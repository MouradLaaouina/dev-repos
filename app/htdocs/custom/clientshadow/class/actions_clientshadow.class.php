<?php
/**
 * Hook class for ClientShadow
 */
class ActionsClientShadow
{
    /** @var DoliDB */
    private $db;

    /** @var ClientShadow */
    private $helper;

    public function __construct($db)
    {
        $this->db = $db;
        dol_include_once('/clientshadow/class/clientshadow.class.php');
        $this->helper = new ClientShadow($db);
    }

    /**
     * Display badge on thirdparty card and document checkbox
     */
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;

        if (empty($parameters['context'])) {
            return 0;
        }

        $contexts = explode(':', $parameters['context']);
        if (!$this->matchDocumentContext($contexts)) {
            return 0;
        }

        $langs->load('clientshadow@clientshadow');

        $value = $this->getFormShadowValue($object);
        $checked = $value ? ' checked="checked"' : '';
        print '<tr class="oddeven clientshadow-row">';
        print '<td>'.$langs->trans('ClientShadowShadowMode').'</td>';
        print '<td>';
        print '<input type="checkbox" name="clientshadow_shadow_mode" value="1"'.$checked.'> ';
        print '<span class="small">'.$langs->trans('ClientShadowShadowDocs').'</span>';
        print '</td>';
        print '</tr>';

        return 0;
    }

    /**
     * Save checkbox value on create/update
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        if (empty($parameters['context'])) {
            return 0;
        }

        $contexts = explode(':', $parameters['context']);
        if (!$this->matchDocumentContext($contexts)) {
            return 0;
        }

        $value = GETPOST('clientshadow_shadow_mode', 'int');
        if ($value === '') {
            return 0;
        }

        $value = $value ? 1 : 0;
        $object->shadow_mode = $value;

        if (!empty($object->id)) {
            $this->helper->saveShadowMode($object, $value);
        }

        return 0;
    }

    private function matchDocumentContext($contexts = array())
    {
        $targets = array('ordercard', 'invoicecard', 'shippingcard');
        foreach ($targets as $target) {
            if (in_array($target, $contexts)) {
                return true;
            }
        }
        return false;
    }

    private function getFormShadowValue($object)
    {
        $value = GETPOST('clientshadow_shadow_mode', 'int');
        if ($value !== '') {
            return (int) $value;
        }

        if (isset($object->shadow_mode)) {
            return (int) $object->shadow_mode;
        }

        $stored = $this->helper->getShadowFlagFromDb($object);
        if ($stored !== null) {
            return (int) $stored;
        }

        $origin = $this->helper->getOriginShadowValue($object);
        if ($origin !== null) {
            return (int) $origin;
        }

        if (!empty($object->thirdparty)) {
            return $this->helper->getThirdpartyType($object->thirdparty) === 'B' ? 1 : 0;
        }

        return 0;
    }

    public function addMoreBoxStatsCustomer($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $conf;

        if (empty($object->id)) {
            return 0;
        }

        $langs->load('clientshadow@clientshadow');
        $stats = $this->helper->getDocumentTotals($object->id);

        $shadowOutstanding = $this->helper->getShadowOutstandingInvoices($object->id);
        $officialOutstanding = 0;
        if (method_exists($object, 'getOutstandingBills')) {
            $official = $object->getOutstandingBills('customer', 0);
            if (is_array($official) && isset($official['opened'])) {
                $officialOutstanding = (float) $official['opened'];
            }
        }
        $totalOutstanding = $officialOutstanding + $shadowOutstanding;

        $out = '<div class="box boxshadowinfo">';
        if (!empty($object->array_options['options_clientshadow_type']) && $object->array_options['options_clientshadow_type'] === 'B') {
            $out .= '<div class="info warning">'.img_warning().' '.$langs->trans('ClientShadowShadowMode').'</div>';
        }

        if (!empty($stats)) {
            $out .= '<table class="noborder centpercent">';
            $out .= '<tr class="liste_titre"><th>'.$langs->trans('ClientShadowShadowDocs').'</th><th>'.$langs->trans('clientshadowTypeA').'</th><th>'.$langs->trans('clientshadowTypeB').'</th></tr>';
            foreach ($stats as $statRow) {
                $out .= '<tr class="oddeven">';
                $out .= '<td>'.$langs->trans($statRow['label']).'</td>';
                $out .= '<td>'.price($statRow['data'][0], 0, $langs, 0, 0, 0, $conf->currency).'</td>';
                $out .= '<td>'.price($statRow['data'][1], 0, $langs, 0, 0, 0, $conf->currency).'</td>';
                $out .= '</tr>';
            }
            $out .= '</table>';
        }

        $out .= '<div class="small">'.dol_escape_htmltag($langs->trans(
            'ClientShadowOutstandingWithShadow',
            price($totalOutstanding, 0, $langs, 0, 0, 0, $conf->currency),
            price($officialOutstanding, 0, $langs, 0, 0, 0, $conf->currency),
            price($shadowOutstanding, 0, $langs, 0, 0, 0, $conf->currency)
        )).'</div>';
        $out .= '</div>';

        $hookmanager->resPrint .= $out;
        return 0;
    }
}
