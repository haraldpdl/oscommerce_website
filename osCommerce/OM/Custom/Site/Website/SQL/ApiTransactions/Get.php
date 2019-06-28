<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ApiTransactions;

use osCommerce\OM\Core\Registry;

class Get
{
    public static function execute($data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qtrans = $OSCOM_PDO->get('website_api_transaction_log', '*', [
            'id' => $data['id']
        ]);

        return $Qtrans->fetch();
    }
}
