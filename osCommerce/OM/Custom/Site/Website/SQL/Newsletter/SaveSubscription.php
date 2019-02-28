<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Newsletter;

use osCommerce\OM\Core\Registry;

class SaveSubscription
{
    public static function execute(array $data): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        return $OSCOM_PDO->save('website_newsletters_subscribers', [
            'sub_key' => $data['key'],
            'list_id' => $data['list_id'],
            'email' => $data['email'],
            'name' => $data['name'],
            'optin_time' => $data['optin_time'],
            'optin_ip' => $data['optin_ip'],
            'confirm_ip' => $data['ip_address']
        ]) === 1;
    }
}
