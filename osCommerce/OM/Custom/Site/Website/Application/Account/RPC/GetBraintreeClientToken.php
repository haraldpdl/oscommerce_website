<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\RPC;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\{
    Braintree,
    Partner
};

use osCommerce\OM\Core\Site\RPC\{
    Controller as RPC,
    Exception as RPCException
};

class GetBraintreeClientToken
{
    public static function execute()
    {
        $OSCOM_Currency = Registry::get('Currency');
        $OSCOM_Session = Registry::get('Session');

        if (!$OSCOM_Session->hasStarted()) {
            $OSCOM_Session->start();
        }

        $result = [];

        try {
            if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
                throw new RPCException(RPC::STATUS_NO_ACCESS);
            }

            if (!isset($_GET['p']) || empty($_GET['p']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['p'])) {
                throw new RPCException(RPC::STATUS_NO_ACCESS);
            }

            if (!isset($_GET['c']) || empty($_GET['c']) || !$OSCOM_Currency->exists($_GET['c'])) {
                throw new RPCException(RPC::STATUS_NO_ACCESS);
            }

            $partner_campaign = Partner::getCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['p']);

            $OSCOM_Currency->setSelected($_GET['c']);

            $result['rpcStatus'] = RPC::STATUS_SUCCESS;

            $result['currency'] = $OSCOM_Currency->getDefault();

            $result['token'] = Braintree::getClientToken([
                'user_group' => 'partner',
                'module' => 'partnership',
                'action' => 'extension'
            ], $result);

            $result['braintree_web_dropin_url'] = 'https://js.braintreegateway.com/web/dropin/' . Braintree::WEB_DROPIN_VERSION . '/js/dropin.min.js';

            $braintree_google_merchant_id = OSCOM::getConfig('braintree_google_merchant_id');

            if (!empty($braintree_google_merchant_id)) {
                $result['googleMerchantId'] = $braintree_google_merchant_id;
            }

            $result['packages'] = Partner::getPackages($_GET['p']);
            $result['plan'] = $partner_campaign['pkg_code'];
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
