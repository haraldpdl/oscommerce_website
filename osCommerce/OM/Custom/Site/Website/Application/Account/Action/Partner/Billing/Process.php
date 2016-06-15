<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner\Billing;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Partner;

class Process
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        $data = [];
        $error = false;

        $public_token = isset($_POST['public_token']) ? trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['public_token'])) : '';

        if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_form_protect_general'), 'error');

            return false;
        }

        if (isset($_POST['address'])) {
            $address = trim($_POST['address']);

            if (strlen($address) > 255) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_billing_address_length'));
            } else {
                $data['billing_address'] = $address;
            }
        }

        if (isset($_POST['vat_id']) ) {
            $vat_id = trim(str_replace(["\r\n", "\n", "\r"], '', $_POST['vat_id']));

            if (strlen($vat_id) > 32) {
                $error = true;

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_billing_vat_id_length'));
            } else {
                $data['billing_vat_id'] = $vat_id;
            }
        }

        if (!empty($data) && ($error === false)) {
            $partner = $OSCOM_Template->getValue('partner_campaign');

            if (Partner::save($_SESSION[OSCOM::getSite()]['Account']['id'], $partner['code'], $data)) {
                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_success_billing_save'), 'success');
            }

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner&Billing=' . $partner['code'], 'SSL'));
        }
    }
}
