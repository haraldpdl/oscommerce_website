<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Invoices;

use osCommerce\OM\Core\Registry;

class GetUserInvoices
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qinvoices = $OSCOM_PDO->get([
            'website_account_invoices i',
            'currencies c'
        ], [
            'i.*',
            'c.symbol_left as currency'
        ], [
            'i.user_id' => $data['user_id'],
            'i.currency_id' => [
                'rel' => 'c.currencies_id'
            ]
        ], 'i.date desc', null, [
            'cache' => [
                'key' => 'users-' . $data['user_id'] . '-invoices',
                'expire' => 1440
            ]
        ]);

        return $Qinvoices->fetchAll();
    }
}
