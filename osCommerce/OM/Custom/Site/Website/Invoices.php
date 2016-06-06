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

    public static function logDownload(string $invoice, int $user_id)
    {
        $data = [
            'id' => static::get($invoice, $user_id, 'id'),
            'user_id' => $user_id,
            'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress()))
        ];

        return OSCOM::callDB('Website\LogInvoiceDownload', $data, 'Site');
    }
}
