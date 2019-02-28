<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    HTML,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Shop\Address;

use osCommerce\OM\Core\Site\Website\{
    Braintree,
    Partner
};

class Extend
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        if (empty($_GET['Extend']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Extend'])) {
            Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_error_campaign_not_available'), 'error');

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner', 'SSL'));
        }

        $partner_campaign = Partner::getCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Extend']);

        if ((int)$partner_campaign['billing_country_id'] < 1) {
            Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_error_campaign_billing_not_available'), 'error');

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner', 'SSL'));
        }

        $partner_billing_address = json_decode($partner_campaign['billing_address'], true);

        if (!is_array($partner_billing_address) || empty($partner_billing_address['street_address'])) {
            Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_warning_campaign_billing_address_required'), 'warning');

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner&Billing=' . $_GET['Extend'], 'SSL'));
        }

        $OSCOM_Template->setValue('partner_campaign', $partner_campaign);

        $partner = Partner::get($_GET['Extend']);

        $OSCOM_Template->setValue('partner', $partner);
        $OSCOM_Template->setValue('partner_packages', Partner::getPackages($partner['code']));

        $address_formatted = Address::format($partner_billing_address, '<br>');

        if (!empty($partner_campaign['billing_vat_id'])) {
            $vatidbr = Address::getVatIdTitleAbr($partner_billing_address['country_id']);

            if (!empty($vatidbr)) {
                $address_formatted .= '<br>' . HTML::outputProtected($vatidbr) . ': ' . HTML::outputProtected($partner_campaign['billing_vat_id']);
            }
        }

        $OSCOM_Template->setValue('partner_billing_address_formatted', $address_formatted);

        $OSCOM_Template->setValue('braintree_get_client_token_url', OSCOM::getRPCLink(null, null, 'GetBraintreeClientToken&p=' . $partner['code'], 'SSL'));

        $application->setPageContent('partner_extend.html');

        $application->setPageTitle(OSCOM::getDef('partner_view_html_title', [
            ':partner_title' => $partner['title']
        ]));

        $OSCOM_Template->addHtmlElement('footer', '<script src="https://js.braintreegateway.com/web/dropin/' . Braintree::WEB_DROPIN_VERSION . '/js/dropin.min.js"></script><script src="https://js.braintreegateway.com/web/' . Braintree::WEB_VERSION . '/js/client.min.js"></script><script src="https://js.braintreegateway.com/web/' . Braintree::WEB_VERSION . '/js/three-d-secure.min.js"></script>');
    }
}
