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

    public static function saveUser(array $params)
    {
        $data = [
            'invoice_number' => $params['invoice_number'],
            'date' => $params['date'],
            'title' => $params['title'],
            'cost' => $params['cost'],
            'currency_id' => $params['currency_id'],
            'status' => $params['status'],
            'user_id' => $params['user_id'],
            'partner_transaction_id' => $params['partner_transaction_id']
        ];

        return OSCOM::callDB('Website\SaveUserInvoice', $data, 'Site');
    }

    public static function getNew()
    {
        $data = [
            'status' => static::STATUS_NEW
        ];

        return OSCOM::callDB('Website\GetNewInvoices', $data, 'Site');
    }

    public static function save(array $params)
    {
        $data = [
            'id' => $params['id'] ?? null,
            'invoice_number' => $params['invoice_number'] ?? null,
            'transaction_number' => $params['transaction_number'] ?? null,
            'user_id' => $params['user_id'] ?? null,
            'title' => $params['title'] ?? null,
            'billing_address' => $params['billing_address'] ?? null,
            'items' => $params['items'] ?? null,
            'totals' => $params['totals'] ?? null,
            'cost' => $params['cost'] ?? null,
            'currency_id' => $params['currency_id'] ?? null,
            'language_id' => $params['language_id'] ?? null,
            'status' => $params['status'] ?? null,
            'api_transaction_id' => $params['api_transaction_id'] ?? null,
            'partner_transaction_id' => $params['partner_transaction_id'] ?? null,
            'module' => $params['module'] ?? null
        ];

        return OSCOM::callDB('Website\SaveInvoice', $data, 'Site');
    }
}
