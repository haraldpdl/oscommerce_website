<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Newsletter;

use osCommerce\OM\Core\Registry;

class GetPendingSubscription
{
    public static function execute(array $data): ?array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $result = $OSCOM_PDO->get('website_newsletters_pending', '*', [
            'pending_key' => $data['key']
        ], null, 1)->fetch();

        if (is_array($result)) {
            return $result;
        }

        return null;
    }
}
