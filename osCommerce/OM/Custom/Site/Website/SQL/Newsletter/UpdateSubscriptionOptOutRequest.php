<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Newsletter;

use osCommerce\OM\Core\Registry;

class UpdateSubscriptionOptOutRequest
{
    public static function execute(array $data): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        return $OSCOM_PDO->save('website_newsletters_subscribers', [
            'optout_req_ip' => $data['ip_address'],
            'optout_req_time' => 'now()'
        ], [
            'email' => $data['email'],
            'list_id' => $data['list_id']
        ]) === 1;
    }
}
