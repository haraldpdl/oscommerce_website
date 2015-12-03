<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2015 osCommerce; http://www.oscommerce.com
 * @license BSD; http://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\HttpRequest;
use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Registry;

class PayPal
{
    const API_VERSION = 123;

    public static function makeCall($params)
    {
        $url = 'https://api-3t.' . (OSCOM::getConfig('paypal_server') != 'live' ? 'sandbox.' : '') . 'paypal.com/nvp';

        $p = [
            'USER' => OSCOM::getConfig('paypal_' . OSCOM::getConfig('paypal_server') . '_user'),
            'PWD' => OSCOM::getConfig('paypal_' . OSCOM::getConfig('paypal_server') . '_password'),
            'SIGNATURE' => OSCOM::getConfig('paypal_' . OSCOM::getConfig('paypal_server') . '_signature'),
            'VERSION' => static::API_VERSION
        ];

        $params = array_merge($p, $params);

        $post = [];

        foreach ($params as $key => $value) {
            $value = utf8_encode(trim($value));

            $post[$key] = $value;
        }

        $p = [
            'url' => $url,
            'parameters' => http_build_query($post, '', '&'),
            'verify_ssl' => true
        ];

        $r = null;

        $response = HttpRequest::getResponse($p);
        parse_str($response, $r);

        $result = (isset($r['ACK']) && in_array($r['ACK'], ['Success', 'SuccessWithWarning'])) ? 1 : -1;

        static::log($result, $post, $r);

        return $r;
    }

    public static function log($result, $request, $response)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $filter = ['USER', 'PWD', 'SIGNATURE', 'ACCT', 'CVV2', 'ISSUENUMBER'];

        $request_string = '';

        if (is_array($request)) {
            foreach ($request as $key => $value) {
                if ((strpos($key, '_nh-dns') !== false) || in_array($key, $filter)) {
                    $value = '**********';
                }

                $request_string .= $key . ': ' . $value . "\n";
            }
        } else {
            $request_string = $request;
        }

        $response_string = '';

        if (is_array($response)) {
            foreach ($response as $key => $value) {
                if (is_array($value)) {
                    $value = http_build_query($value);
                } elseif ((strpos($key, '_nh-dns') !== false) || in_array($key, $filter)) {
                    $value = '**********';
                }

                $response_string .= $key . ': ' . $value . "\n";
            }
        } else {
            $response_string = $response;
        }

        $OSCOM_PDO->save('website_api_transaction_log', [
            'app' => 'paypal',
            'user_group' => 'partner',
            'user_id' => isset($_SESSION[OSCOM::getSite()]['Account']['id']) ? $_SESSION[OSCOM::getSite()]['Account']['id'] : null,
            'module' => 'partnership',
            'action' => 'extension',
            'result' => $result,
            'server' => (OSCOM::getConfig('paypal_server') == 'live') ? 1 : -1,
            'request' => trim($request_string),
            'response' => trim($response_string),
            'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress())),
            'date_added' => 'now()'
        ]);
    }
}
