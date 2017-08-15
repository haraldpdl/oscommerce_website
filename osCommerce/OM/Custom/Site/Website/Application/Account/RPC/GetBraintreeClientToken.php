<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\RPC;

use osCommerce\OM\Core\OSCOM;

use osCommerce\OM\Core\Site\Website\{
    Braintree,
    Partner
};

use osCommerce\OM\Core\Site\RPC\Controller as RPC;

class GetBraintreeClientToken
{
    public static function execute()
    {
        $result = [];

        if (
            !isset($_SESSION[OSCOM::getSite()]['Account']) ||
            !isset($_GET['p']) ||
            empty($_GET['p']) ||
            !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['p'])
           ) {
            $result['rpcStatus'] = RPC::STATUS_NO_ACCESS;
        }

        if (!isset($result['rpcStatus'])) {
            $result['token'] = Braintree::getClientToken();
            $result['rpcStatus'] = RPC::STATUS_SUCCESS;
        }

        if (!isset($result['rpcStatus'])) {
            $result['rpcStatus'] = RPC::STATUS_ERROR;
        }

        echo json_encode($result);
    }
}
