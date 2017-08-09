<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetNewInvoices
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qinvoices = $OSCOM_PDO->get('website_invoices', '*', [
            'status' => $data['status']
        ], 'date_added');

        return $Qinvoices->fetchAll();
    }
}
