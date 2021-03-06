<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\Registry;

class Invoices
{
    const STATUS_PENDING = 1;
    const STATUS_PAID = 2;
    const STATUS_LEGACY = 3;
    const STATUS_NEW = 4;
    const STATUS_RENEW = 5;

    public static function hasInvoices(int $user_id): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'user_id' => $user_id
        ];

        return $OSCOM_PDO->call('HasUserInvoice', $data);
    }

    public static function getAll(int $user_id): array
    {
        $OSCOM_Currency = Registry::get('Currency');
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'user_id' => $user_id
        ];

        $result = $OSCOM_PDO->call('GetUserInvoices', $data);

        foreach ($result as $k => $v) {
            $result[$k]['cost_formatted'] = $OSCOM_Currency->show($v['cost'], $OSCOM_Currency->getCode($v['currency_id']), null, false);
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

    public static function saveUser(array $params): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

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

        return $OSCOM_PDO->call('SaveUserInvoice', $data);
    }

    public static function getNew(): array
    {
        return static::getWithStatus(static::STATUS_NEW);
    }

    public static function getWithStatus(int $status_id): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'status' => $status_id
        ];

        return $OSCOM_PDO->call('GetWithStatus', $data);
    }

    public static function save(array $params): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

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

        return $OSCOM_PDO->call('Save', $data);
    }
}
