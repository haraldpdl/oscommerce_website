<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetApiTransaction
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
