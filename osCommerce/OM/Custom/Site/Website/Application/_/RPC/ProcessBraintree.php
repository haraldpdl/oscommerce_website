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
    Registry
};

use osCommerce\OM\Core\Site\Website\{
    Braintree,
    BraintreeException,
    Invision,
    Invoices,
    Users
};

use osCommerce\OM\Core\Site\Apps\Cache;

use osCommerce\OM\Core\Site\RPC\{
    Controller as RPC,
    Exception as RPCException
};

class ProcessBraintree
{
    public static function execute()
    {
        $OSCOM_Currency = Registry::get('Currency');
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

            $address = Users::getAddress($_SESSION[OSCOM::getSite()]['Account']['id'], 'billing');
            $address = reset($address);

            $items = [
                [
                    'title' => OSCOM::getDef('purchase_item_title_raw', [
                        ':name' => $_SESSION[OSCOM::getSite()]['Account']['name']
                    ]),
                    'cost' => $OSCOM_Currency->raw(Users::AMBASSADOR_LEVEL_PRICE)
                ]
            ];

            $totals = [];

            $total = [
                'title' => OSCOM::getDef('purchase_item_total'),
                'cost' => $items[0]['cost']
            ];

            if ($address['country_iso_2'] == 'DE') {
                $items[0]['tax']['DE19MWST'] = $OSCOM_Currency->raw(0.19 * $items[0]['cost'], null, null, false);

                $total['cost'] = $OSCOM_Currency->raw(1.19 * $items[0]['cost'], null, null, false);

                $totals['tax'] = [
                    'DE19MWST' => [
                        'title' => OSCOM::getDef('purchase_tax_DE19MWST_title'),
                        'cost' => $items[0]['tax']['DE19MWST']
                    ]
                ];
            }

            $totals['total'] = $total;

            $data = [
                'paymentMethodNonce' => $_POST['nonce'],
                'amount' => $totals['total']['cost'],
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

            if (isset($totals['tax'])) {
                $data['taxAmount'] = $totals['tax']['DE19MWST']['cost'];
            }

            $braintree_result = Braintree::doSale($data, [
                'user_group' => 'member',
                'module' => 'ambassador',
                'action' => 'signup'
            ], [
                'user_id' => $_SESSION[OSCOM::getSite()]['Account']['id'],
                'title' => OSCOM::getDef('purchase_title'),
                'billing_address' => $address,
                'items' => $items,
                'totals' => $totals,
                'cost' => $totals['total']['cost'],
                'currency_id' => $OSCOM_Currency->get('id'),
                'language_id' => $OSCOM_Language->getID(),
                'status' => Invoices::STATUS_NEW,
                'module' => 'Ambassador'
            ]);

            if ($braintree_result->success !== true) {
                throw new BraintreeException($braintree_result->message);
            }

            $result['rpcStatus'] = RPC::STATUS_SUCCESS;

            $profile = [
                'customFields' => [
                    Users::CUSTOMFIELD_AMBASSADOR_LEVEL_ID => (int)($_SESSION[OSCOM::getSite()]['Account']['amb_level'] ?? 0) + 1
                ],
                'clubs' => [
                    Invision::CLUB_AMBASSADORS_ID
                ]
            ];

            if ($_SESSION[OSCOM::getSite()]['Account']['group_id'] === Users::GROUP_MEMBER_ID) {
                $profile['group'] = Users::GROUP_AMBASSADOR_ID;
            } else {
                $result['errorCode'] = 'non_member_group';

                trigger_error('Braintree [Ambassador; ' . $_SESSION[OSCOM::getSite()]['Account']['name'] . ' (' . $_SESSION[OSCOM::getSite()]['Account']['id'] . ')]: Member group ID ' . $_SESSION[OSCOM::getSite()]['Account']['group_id'] . ' does not match initial group ID ' . Users::GROUP_MEMBER_ID);
            }

            Users::save($_SESSION[OSCOM::getSite()]['Account']['id'], $profile);

            $result['amb_profile'] = [
                'name' => $_SESSION[OSCOM::getSite()]['Account']['name'],
                'profile_url' => $_SESSION[OSCOM::getSite()]['Account']['profile_url'],
                'photo_url' => $_SESSION[OSCOM::getSite()]['Account']['photo_url']
            ];

            $OSCOM_Cache = new Cache('ambassadors-newest-NS');
            $OSCOM_Cache->delete();
        } catch (RPCException $e) {
            $code = $e->getCode();

            if (isset($code)) {
                $result['rpcStatus'] = $code;
            }
        } catch (BraintreeException $e) {
            trigger_error('Braintree [Ambassador; ' . $_SESSION[OSCOM::getSite()]['Account']['name'] . ' (' . $_SESSION[OSCOM::getSite()]['Account']['id'] . ')]: ' . $e->getMessage());

            $result['errorMessage'] = $e->getMessage();
        }

        if (!isset($result['rpcStatus'])) {
            $result['rpcStatus'] = RPC::STATUS_ERROR;
        }

        echo json_encode($result);
    }
}
