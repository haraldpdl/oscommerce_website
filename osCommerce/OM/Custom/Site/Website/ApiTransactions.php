<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\Registry;

class ApiTransactions
{
    public static function get(int $id): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'id' => $id
        ];

        return $OSCOM_PDO->call('GetApiTransaction', $data);
    }
}
