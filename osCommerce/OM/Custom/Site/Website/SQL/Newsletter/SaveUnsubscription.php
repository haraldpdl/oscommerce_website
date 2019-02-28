<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Newsletter;

use osCommerce\OM\Core\Registry;

class SaveUnsubscription
{
    public static function execute(array $data): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        return $OSCOM_PDO->save('website_newsletters_unsubscribers', [
            'sub_key' => $data['key'],
            'list_id' => $data['list_id'],
            'email' => $data['email'],
            'name' => $data['name'],
            'optin_time' => $data['optin_time'],
            'optin_ip' => $data['optin_ip'],
            'confirm_time' => $data['confirm_time'],
            'confirm_ip' => $data['confirm_ip'],
            'optout_req_time' => $data['optout_req_time'],
            'optout_req_ip' => $data['optout_req_ip'],
            'unsub_ip' => $data['ip_address'],
            'unsub_campaign_id' => $data['campaign_id'] ?? null,
            'unsub_reason_other' => $data['reason'] ?? null
        ]) === 1;
    }
}
