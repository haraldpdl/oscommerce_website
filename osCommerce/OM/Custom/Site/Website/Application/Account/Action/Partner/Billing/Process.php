<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner\Billing;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM,
    Registry,
    Sanitize
};

use osCommerce\OM\Core\Site\Shop\Address;

use osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner\Billing;

use osCommerce\OM\Core\Site\Website\Partner;

class Process
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        $data = [];
        $error = false;

        $public_token = Sanitize::simple($_POST['public_token'] ?? null);

        if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_form_protect_general'), 'error');

            return false;
        }

        $pCompany = Sanitize::simple($_POST['company'] ?? null);
        $pFirstName = Sanitize::simple($_POST['firstname'] ?? null);
        $pLastName = Sanitize::simple($_POST['lastname'] ?? null);
        $pStreet = Sanitize::simple($_POST['street'] ?? null);
        $pStreet2 = Sanitize::simple($_POST['street2'] ?? null);
        $pCity = Sanitize::simple($_POST['city'] ?? null);
        $pZip = Sanitize::simple($_POST['zip'] ?? null);
        $pCountry = Sanitize::simple($_POST['country'] ?? null);
        $pZone = Sanitize::simple($_POST['zone_code'] ?? null);
        $pState = Sanitize::simple($_POST['state'] ?? null);
        $pZoneId = null;
        $pVatId = Sanitize::simple($_POST['vat_id'] ?? null);

        if (empty($pStreet) || empty($pCountry)) {
            $error = true;
        }

        if ($error === false) {
            if (Address::countryExists($pCountry)) {
                $country_id = Address::getCountryId($pCountry);

                if (in_array($pCountry, Billing::COUNTRIES_WITH_ZONES)) {
                    foreach (Address::getZones($country_id) as $z) {
                        if ($z['code'] == $pZone) {
                            $pZoneId = $z['id'];

                            break;
                        }
                    }

                    if (!isset($pZoneId)) {
                        $error = true;
                    }
                }
            } else {
                $error = true;
            }
        }

        if ($error === false) {
            $address = [
                'gender' => null,
                'company' => !empty($pCompany) ? $pCompany : null,
                'firstname' => !empty($pFirstName) ? $pFirstName : null,
                'lastname' => !empty($pLastName) ? $pLastName : null,
                'street_address' => $pStreet,
                'street_address_2' => !empty($pStreet2) ? $pStreet2 : null,
                'suburb' => null,
                'postcode' => $pZip,
                'city' => $pCity,
                'state' => !isset($pZoneId) && !empty($pState) ? $pState : null,
                'country_id' => $country_id,
                'zone_id' => $pZoneId ?? null,
                'telephone' => null,
                'fax' => null,
                'other_info' => null
            ];

            $data['billing_address'] = json_encode($address);
            $data['billing_vat_id'] = $pVatId;

            $partner = $OSCOM_Template->getValue('partner');

            if (Partner::save($_SESSION[OSCOM::getSite()]['Account']['id'], $partner['code'], $data)) {
                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_success_billing_save'), 'success');
            }

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner&Billing=' . $partner['code'], 'SSL'));
        }

        $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_billing_missing_address_fields'), 'error');

        $OSCOM_Template->setValue('form_verify_fields_js', true);
    }
}
