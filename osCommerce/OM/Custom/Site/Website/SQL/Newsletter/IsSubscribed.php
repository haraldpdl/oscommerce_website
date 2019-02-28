<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Newsletter;

use osCommerce\OM\Core\Registry;

class IsSubscribed
{
    public static function execute(array $data): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        return $OSCOM_PDO->get('website_newsletters_subscribers', 1, [
            'email' => $data['email'],
            'list_id' => $data['list_id']
        ], null, 1)->fetch() !== false;
    }
}
