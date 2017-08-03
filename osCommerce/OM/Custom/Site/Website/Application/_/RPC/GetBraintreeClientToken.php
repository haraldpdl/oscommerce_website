<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\_\RPC;

use osCommerce\OM\Core\{
    HTML,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Application\_\Action\Ambassadors;

use osCommerce\OM\Core\Site\Shop\Address;

use osCommerce\OM\Core\Site\Website\{
    Braintree,
    Users
};

use osCommerce\OM\Core\Site\RPC\Controller as RPC;

class GetBraintreeClientToken
{
    public static function execute()
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_Session = Registry::get('Session');

        if (!$OSCOM_Session->hasStarted()) {
            $OSCOM_Session->start();
        }

        $result = [];

        if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $result['rpcStatus'] = RPC::STATUS_NO_ACCESS;
        }

        if (empty($result)) {
            $error = false;

            $cFirstName = isset($_POST['firstname']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['firstname'])) : '';
            $cLastName = isset($_POST['lastname']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['lastname'])) : '';
            $cStreet = isset($_POST['street']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['street'])) : '';
            $cStreet2 = isset($_POST['street2']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['street2'])) : '';
            $cCity = isset($_POST['city']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['city'])) : '';
            $cZip = isset($_POST['zip']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['zip'])) : '';
            $cCountry = isset($_POST['country']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['country'])) : '';
            $cZone = isset($_POST['zone']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['zone'])) : '';

            if (empty($cFirstName) || empty($cLastName) || empty($cStreet) || empty($cCity) || empty($cZip) || empty($cCountry)) {
                $error = true;
            }

            if ($error === false) {
                if (Address::countryExists($cCountry)) {
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
                            $error = true;
                        }
                    }
                } else {
                    $error = true;
                }
            }

            if ($error === false) {
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

                $result['price'] = $result['total'] = $OSCOM_Language->formatNumber(49, 2);
                $result['total_raw'] = number_format(49, 2);

                if ($cCountry == 'DE') {
                    $result['tax'] = $OSCOM_Language->formatNumber(0.19 * 49, 2);
                    $result['tax_desc'] = '19% MwSt.:';
                    $result['total'] = $OSCOM_Language->formatNumber(49 * 1.19, 2);
                    $result['total_raw'] = number_format(49 * 1.19, 2);
                }

                $result['token'] = Braintree::getClientToken();
            }
        }

        if (!isset($result['rpcStatus'])) {
            $result['rpcStatus'] = RPC::STATUS_ERROR;
        }

        echo json_encode($result);
    }
}
