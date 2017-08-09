<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\OSCOM;

class ApiTransactions
{
    public static function get(int $id): array
    {
        $data = [
            'id' => $id
        ];

        return OSCOM::callDB('Website\GetApiTransaction', $data, 'Site');
    }
}
