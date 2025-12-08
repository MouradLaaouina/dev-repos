<?php
/**
 * Hooks for ExpeditionPayment
 */
class ActionsExpeditionpayment
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Add button on expedition card
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $ctx = isset($parameters['context']) ? $parameters['context'] : '';
        if (empty($ctx) || (strpos($ctx, 'expeditioncard') === false && strpos($ctx, 'globalcard') === false && strpos($ctx, 'shippingcard') === false && strpos($ctx, 'shipmentcard') === false)) {
            return 0;
        }

        if (empty($conf->expeditionpayment->enabled) || empty($user->rights->expeditionpayment->write)) {
            return 0;
        }

        $langs->load('bills');
        $url = dol_buildpath('/expeditionpayment/card.php', 1).'?expid='.(int) $object->id;
        $button = '<a class="butAction" href="'.$url.'">'.$langs->trans('RegisterPayment').'</a>';

        $helper = $this->getHelper();
        $paid = $helper->sumForExpedition($object->id);
        if ($paid > 0) {
            $badge = '<span class="badge badge-status4 marginleftonly">'.$langs->trans('Paid').': '.price($paid, 0, $langs, 0, 0, 0, $conf->currency).'</span>';
            $button .= $badge;
        }

        $hookmanager->resPrint .= $button;
        return 0;
    }

    /**
     * Show box on thirdparty card with total expedition payments
     */
    public function addMoreBoxStatsCustomer($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        if (empty($object->id) || empty($conf->expeditionpayment->enabled) || empty($user->rights->expeditionpayment->read)) {
            return 0;
        }

        $helper = $this->getHelper();
        $total = $helper->sumForThirdparty($object->id);
        if ($total <= 0) {
            return 0;
        }

        $langs->load('bills');
        $out = '<div class="box boxpaymentinfo">';
        $out .= '<div class="info">'.$langs->trans('ExpeditionPaymentMenu').': '.price($total, 0, $langs, 0, 0, 0, $conf->currency).'</div>';
        $out .= '</div>';

        $hookmanager->resPrint .= $out;
        return 0;
    }

    private function getHelper()
    {
        dol_include_once('/expeditionpayment/class/expeditionpayment.class.php');
        return new ExpeditionPayment($this->db);
    }

    /**
     * Add a row in object options (fallback if buttons are hidden)
     */
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $ctx = isset($parameters['context']) ? $parameters['context'] : '';
        if (empty($ctx) || (strpos($ctx, 'expeditioncard') === false && strpos($ctx, 'globalcard') === false)) {
            return 0;
        }

        if (empty($conf->expeditionpayment->enabled) || empty($user->rights->expeditionpayment->write)) {
            return 0;
        }

        $langs->load('bills');
        $url = dol_buildpath('/expeditionpayment/card.php', 1).'?expid='.(int) $object->id;

        print '<tr class="oddeven">';
        print '<td>'.$langs->trans('ExpeditionPaymentMenu').'</td>';
        print '<td colspan="3"><a class="butAction" href="'.$url.'">'.$langs->trans('RegisterPayment').'</a></td>';
        print '</tr>';

        return 0;
    }
}
