<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class SaveInvoice
{
    public static function execute($data): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $invoice = [];

        if (isset($data['user_id'])) {
            $invoice['user_id'] = $data['user_id'];
        }

        if (isset($data['title'])) {
            $invoice['title'] = $data['title'];
        }

        if (isset($data['billing_address'])) {
            $invoice['billing_address'] = $data['billing_address'];
        }

        if (isset($data['items'])) {
            $invoice['purchase_items'] = $data['items'];
        }

        if (isset($data['totals'])) {
            $invoice['order_total_items'] = $data['totals'];
        }

        if (isset($data['cost'])) {
            $invoice['cost'] = $data['cost'];
        }

        if (isset($data['currency_id'])) {
            $invoice['currency_id'] = $data['currency_id'];
        }

        if (isset($data['language_id'])) {
            $invoice['language_id'] = $data['language_id'];
        }

        if (isset($data['status'])) {
            $invoice['status'] = $data['status'];
        }

        if (isset($data['invoice_number'])) {
            $invoice['invoice_number'] = $data['invoice_number'];
        }

        if (isset($data['transaction_number'])) {
            $invoice['transaction_number'] = $data['transaction_number'];
        }

        if (isset($data['api_transaction_id'])) {
            $invoice['api_transaction_id'] = $data['api_transaction_id'];
        }

        if (isset($data['partner_transaction_id'])) {
            $invoice['partner_transaction_id'] = $data['partner_transaction_id'];
        }

        $where = null;

        if (isset($data['id'])) {
            $where = [
                'id' => $data['id']
            ];
        }

        return $OSCOM_PDO->save('website_invoices', $invoice, $where) === 1;
    }
}
