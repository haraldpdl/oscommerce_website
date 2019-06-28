<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\_\RPC;

use osCommerce\OM\Core\{
    OSCOM,
    Registry,
    Sanitize
};

use osCommerce\OM\Core\Site\Website\Application\_\Action\Ambassadors;

use osCommerce\OM\Core\Site\Shop\Address;

use osCommerce\OM\Core\Site\Website\{
    Braintree,
    Users
};

use osCommerce\OM\Core\Site\RPC\{
    Controller as RPC,
    Exception as RPCException
};

class GetBraintreeClientToken
{
    public static function execute()
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_Session = Registry::get('Session');

        if (!$OSCOM_Session->hasStarted()) {
            $OSCOM_Session->start();
        }

        $OSCOM_Language->loadIniFile('pages/ambassadors.php');

        $result = [];

        try {
            if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
                throw new RPCException(RPC::STATUS_NO_ACCESS);
            }

            $cFirstName = Sanitize::simple($_POST['firstname'] ?? null);
            $cLastName = Sanitize::simple($_POST['lastname'] ?? null);
            $cStreet = Sanitize::simple($_POST['street'] ?? null);
            $cStreet2 = Sanitize::simple($_POST['street2'] ?? null);
            $cCity = Sanitize::simple($_POST['city'] ?? null);
            $cZip = Sanitize::simple($_POST['zip'] ?? null);
            $cCountry = Sanitize::simple($_POST['country'] ?? null);
            $cZone = Sanitize::simple($_POST['zone'] ?? null);

            if (empty($cFirstName) || empty($cLastName) || empty($cStreet) || empty($cCity) || empty($cZip) || empty($cCountry)) {
                throw new RPCException(RPC::STATUS_ERROR);
            }

            if (!Address::countryExists($cCountry)) {
                throw new RPCException(RPC::STATUS_ERROR);
            }

            $country_id = Address::getCountryId($cCountry);
            $country_title = Address::getCountryName($country_id);

            if (in_array($cCountry, Ambassadors::COUNTRIES_WITH_ZONES)) {
                foreach (Address::getZones($country_id) as $z) {
                    if ($z['code'] == $cZone) {
                        $cZoneCode = $z['code'];

                        break;
                    }
                }

                if (!isset($cZoneCode)) {
                    throw new RPCException(RPC::STATUS_ERROR);
                }
            }

            $address_public_id = Users::getAddressPublicId($_SESSION[OSCOM::getSite()]['Account']['id'], 'billing');

            Users::saveAddress($_SESSION[OSCOM::getSite()]['Account']['id'], [
                'firstname' => $cFirstName,
                'lastname' => $cLastName,
                'street' => $cStreet,
                'street2' => $cStreet2,
                'zip' => $cZip,
                'city' => $cCity,
                'state' => !isset($cZoneCode) ? $cZone : null,
                'country_id' => $country_id,
                'zone_id' => isset($cZoneCode) ? Address::getZoneId($country_id, $cZoneCode) : null
            ], 'billing', $address_public_id);

            $result['rpcStatus'] = RPC::STATUS_SUCCESS;

            $result['addressFormatted'] = Address::format([
                'firstname' => $cFirstName,
                'lastname' => $cLastName,
                'state' => $cZone,
                'zone_code' => $cZoneCode ?? null,
                'country_title' => $country_title,
                'country_id' => $country_id,
                'street_address' => $cStreet,
                'street_address_2' => $cStreet2,
                'city' => $cCity,
                'postcode' => $cZip
            ], '<br>');

            $result['items'] = [
                [
                    'title' => OSCOM::getDef('purchase_item_title', [
                        ':name' => $_SESSION[OSCOM::getSite()]['Account']['name']
                    ]),
                    'cost' => $OSCOM_Language->formatNumber(Users::AMBASSADOR_LEVEL_PRICE, 2) . ' €',
                    'cost_raw' => number_format(Users::AMBASSADOR_LEVEL_PRICE, 2)
                ]
            ];

            $result['totals'] = [
                'total' => [
                    'title' => OSCOM::getDef('purchase_item_total'),
                    'cost' => $result['items'][0]['cost'],
                    'cost_raw' => $result['items'][0]['cost_raw']
                ]
            ];

            if ($cCountry == 'DE') {
                $result['items'][0]['tax']['DE19MWST'] = $OSCOM_Language->formatNumber(0.19 * Users::AMBASSADOR_LEVEL_PRICE, 2) . ' €';
                $result['items'][0]['tax_raw']['DE19MWST'] = number_format(0.19 * Users::AMBASSADOR_LEVEL_PRICE, 2);

                $result['totals']['total']['cost'] = $OSCOM_Language->formatNumber(1.19 * Users::AMBASSADOR_LEVEL_PRICE, 2) . ' €';
                $result['totals']['total']['cost_raw'] = number_format(1.19 * Users::AMBASSADOR_LEVEL_PRICE, 2);

                $result['totals'] = [
                    'tax_DE19MWST' => [
                        'title' => OSCOM::getDef('purchase_tax_DE19MWST_title'),
                        'cost' => $result['items'][0]['tax']['DE19MWST'],
                        'cost_raw' => $result['items'][0]['tax_raw']['DE19MWST']
                    ]
                ] + $result['totals']; // preprend 'tax' to $result['totals'] array
            }

            $result['token'] = Braintree::getClientToken();
        } catch (RPCException $e) {
            $code = $e->getCode();

            if (isset($code)) {
                $result['rpcStatus'] = $code;
            }
        }

        if (!isset($result['rpcStatus'])) {
            $result['rpcStatus'] = RPC::STATUS_ERROR;
        }

        echo json_encode($result);
    }
}
