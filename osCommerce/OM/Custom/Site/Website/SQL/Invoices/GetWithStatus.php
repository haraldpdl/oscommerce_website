<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Invoices;

use osCommerce\OM\Core\Registry;

class GetWithStatus
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qinvoices = $OSCOM_PDO->get('website_invoices', '*', [
            'status' => $data['status']
        ], 'date_added');

        return $Qinvoices->fetchAll();
    }
}
