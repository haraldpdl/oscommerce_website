<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Invoices;

use osCommerce\OM\Core\Registry;

class SaveUserInvoice
{
    public static function execute(array $data): bool
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
