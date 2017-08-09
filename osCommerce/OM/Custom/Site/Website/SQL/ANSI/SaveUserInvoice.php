<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class SaveUserInvoice
{
    public static function execute($data): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $invoice = [
            'number' => $data['invoice_number'],
            'date' => $data['date'],
            'title' => $data['title'],
            'cost' => $data['cost'],
            'currency_id' => $data['currency_id'],
            'status' => $data['status'],
            'user_id' => $data['user_id'],
            'partner_transaction_id' => $data['partner_transaction_id']
        ];

        return $OSCOM_PDO->save('website_account_invoices', $invoice) === 1;
    }
}
