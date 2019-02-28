<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Invoices;

use osCommerce\OM\Core\Registry;

class HasUserInvoice
{
    public static function execute(array $data): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qcheck = $OSCOM_PDO->get('website_account_invoices', [
            'id'
        ], [
            'user_id' => $data['user_id']
        ], null, 1, [
            'cache' => [
                'key' => 'users-' . $data['user_id'] . '-invoices-check',
                'expire' => 1440,
                'store_empty' => true
            ]
        ]);

        return $Qcheck->fetch() !== false;
    }
}
