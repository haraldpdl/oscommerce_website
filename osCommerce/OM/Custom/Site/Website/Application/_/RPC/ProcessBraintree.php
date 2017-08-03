<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\_\RPC;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\{
    Braintree,
    Users
};

use osCommerce\OM\Core\Site\RPC\Controller as RPC;

class ProcessBraintree
{
    public static function execute()
    {
        $OSCOM_Session = Registry::get('Session');

        if (!$OSCOM_Session->hasStarted()) {
            $OSCOM_Session->start();
        }

        $result = [];

        if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $result['rpcStatus'] = RPC::STATUS_NO_ACCESS;
        }

        if (empty($result)) {
            $address = Users::getAddress($_SESSION[OSCOM::getSite()]['Account']['id'], 'billing');
            $address = reset($address);

            $total_raw = number_format(49, 2);
            $tax = null;

            if ($address['country_iso_2'] == 'DE') {
                $total_raw = number_format(49 * 1.19, 2);
                $tax = number_format(0.19 * 49, 2);
            }

            $data = [
                'paymentMethodNonce' => $_POST['nonce'],
                'amount' => $total_raw,
                'billing' => [
                    'countryCodeAlpha2' => $address['country_iso_2'],
                    'extendedAddress' => $address['street2'],
                    'firstName' => $address['firstname'],
                    'lastName' => $address['lastname'],
                    'locality' => $address['city'],
                    'postalCode' => $address['zip'],
                    'region' => $address['zone_code'] ?? $address['state'],
                    'streetAddress' => $address['street']
                ],
                'customFields' => [
                    'user_id' => $_SESSION[OSCOM::getSite()]['Account']['id']
                ]
            ];

            if (isset($tax)) {
                $data['taxAmount'] = $tax;
            }

            $error = false;

            try {
                $braintree_result = Braintree::doSale($data, [
                    'user_group' => 'member',
                    'module' => 'ambassador',
                    'action' => 'signup'
                ]);
            } catch (\Exception $e) {
                $error = true;
            }

            if (($error === false) && ($braintree_result->success === true)) {
                $result['rpcStatus'] = RPC::STATUS_SUCCESS;

                if ($_SESSION[OSCOM::getSite()]['Account']['group_id'] === Users::GROUP_MEMBER_ID) {
                    Users::save($_SESSION[OSCOM::getSite()]['Account']['id'], [
                        'group' => Users::GROUP_AMBASSADOR_ID
                    ]);
                } else {
                    $result['errorCode'] = 'non_member_group';
                }
            } else {
                $result['errorMessage'] = $braintree_result->message;
            }
        }

        if (!isset($result['rpcStatus'])) {
            $result['rpcStatus'] = RPC::STATUS_ERROR;
        }

        echo json_encode($result);
    }
}
