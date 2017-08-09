<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\OSCOM;

class Invoices
{
    const STATUS_PENDING = 1;
    const STATUS_PAID = 2;
    const STATUS_LEGACY = 3;
    const STATUS_NEW = 4;

    const STATUS_PUBLIC = [
        self::STATIC_PENDING,
        self::STATIC_PAID
    ];

    public static function hasInvoices(int $user_id)
    {
        $data = [
            'user_id' => $user_id
        ];

        return OSCOM::callDB('Website\UserHasInvoices', $data, 'Site');
    }

    public static function getAll(int $user_id)
    {
        $data = [
            'user_id' => $user_id
        ];

        $result = OSCOM::callDB('Website\GetInvoices', $data, 'Site');

        foreach ($result as $k => $v) {
            $result[$k]['cost_formatted'] = $v['currency'] . ' ' . number_format($v['cost'], 2);
            $result[$k]['date_formatted'] = date_format(new \DateTime($v['date']), 'jS M Y');

            switch ($v['status']) {
                case '1':
                  $result[$k]['status_code'] = 'pending';
                  break;

                case '2':
                  $result[$k]['status_code'] = 'paid';
                  break;

                default:
                  $result[$k]['status_code'] = '';
                  break;
            }
        }

        return $result;
    }

    public static function get(string $invoice, int $user_id, string $key = null)
    {
        foreach (static::getAll($user_id) as $i) {
            if ($i['number'] == $invoice) {
                if (isset($key)) {
                    return $i[$key];
                }

                return $i;
            }
        }

        return false;
    }

    public static function exists(string $invoice, int $user_id)
    {
        foreach (static::getAll($user_id) as $i) {
            if ($i['number'] == $invoice) {
                return true;
            }
        }

        return false;
    }

    public static function save(array $params)
    {
        $data = [
            'invoice_number' => $params['invoice_number'] ?? null,
            'transaction_number' => $params['transaction_number'] ?? null,
            'user_id' => $params['user_id'],
            'title' => $params['title'],
            'billing_address' => $params['billing_address'],
            'items' => $params['items'],
            'totals' => $params['totals'],
            'cost' => $params['cost'],
            'currency_id' => $params['currency_id'],
            'language_id' => $params['language_id'],
            'status' => $params['status'],
            'api_transaction_id' => $params['api_transaction_id'] ?? null,
            'partner_transaction_id' => $params['partner_transaction_id'] ?? null
        ];

        return OSCOM::callDB('Website\SaveInvoice', $data, 'Site');
    }
}
