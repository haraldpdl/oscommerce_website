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

use osCommerce\OM\Core\Site\Website\Partner;

use osCommerce\OM\Core\Site\Shop\Address;

class Billing
{
    const COUNTRIES_WITH_ZONES = ['AU', 'CA', 'DE', 'US'];

    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        if (empty($_GET['Billing']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Billing'])) {
            Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_error_campaign_not_available'), 'error');

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner', 'SSL'));
        }

        $partner = Partner::get($_GET['Billing']);

        $OSCOM_Template->setValue('partner', $partner);

        $OSCOM_Template->setValue('partner_campaign', Partner::getCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Billing']));

        $partner_billing_address = json_decode($OSCOM_Template->getValue('partner_campaign')['billing_address'], true);

        if (isset($partner_billing_address['zone_id']) && ((int)$partner_billing_address['zone_id'] > 0)) {
            $partner_billing_address['zone_code'] = Address::getZoneCode($partner_billing_address['zone_id']);
        }

        if (empty($partner_billing_address['state']) && isset($partner_billing_address['zone_id']) && isset($partner_billing_address['country_id']) && !in_array(Address::getCountryIsoCode2($partner_billing_address['country_id']), static::COUNTRIES_WITH_ZONES)) {
            $partner_billing_address['state'] = Address::getZoneName($partner_billing_address['zone_id']);
        }

        $OSCOM_Template->setValue('partner_billing_address', $partner_billing_address);

        $countries = [
            [
                'id' => '',
                'text' => OSCOM::getDef('select_option_please_select')
            ]
        ];

        foreach (Address::getCountries() as $c) {
            $countries[$c['id']] = [
                'id' => $c['iso_2'],
                'text' => $c['name']
            ];
        }

        $countries_field = HTML::selectMenu('country', $countries, Address::getCountryIsoCode2($partner_billing_address['country_id']), 'id="pCountry" class="form-control"');

        $OSCOM_Template->setValue('field_countries', $countries_field);

        $zones = [];

        foreach (Address::getZones(static::COUNTRIES_WITH_ZONES) as $z) {
            $zones[$countries[$z['country_id']]['id']][] = [
                'code' => $z['code'],
                'title' => $z['name']
            ];
        }

        $OSCOM_Template->setValue('select_zones', $zones);

        $application->setPageContent('partner_billing.html');

        $application->setPageTitle(OSCOM::getDef('partner_view_html_title', [
            ':partner_title' => $partner['title']
        ]));
    }
}
