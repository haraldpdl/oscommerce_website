<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\HttpRequest;
use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Registry;

if (!class_exists('\Braintree')) {
    include(OSCOM::BASE_DIRECTORY . 'Custom/Site/Website/External/Braintree/lib/autoload.php');
}

class Braintree
{
    protected static $has_setup = false;

    public static function setupCredentials()
    {
        $server = OSCOM::getConfig('braintree_server');

        \Braintree\Configuration::environment($server);
        \Braintree\Configuration::merchantId(OSCOM::getConfig('braintree_' . $server . '_merchant_id'));
        \Braintree\Configuration::publicKey(OSCOM::getConfig('braintree_' . $server . '_public_key'));
        \Braintree\Configuration::privateKey(OSCOM::getConfig('braintree_' . $server . '_private_key'));

        static::$has_setup = true;
    }

    public static function getClientToken()
    {
        if (static::$has_setup === false) {
            static::setupCredentials();
        }

        $server = OSCOM::getConfig('braintree_server');

        $client_token = \Braintree\ClientToken::generate([
            'merchantAccountId' => OSCOM::getConfig('braintree_' . $server . '_merchant_account_id')
        ]);

        return $client_token;
    }

    public static function doSale($params)
    {
        if (static::$has_setup === false) {
            static::setupCredentials();
        }

        $server = OSCOM::getConfig('braintree_server');

        $data = [
            'paymentMethodNonce' => $params['nonce'],
            'amount' => $params['amount'],
            'merchantAccountId' => OSCOM::getConfig('braintree_' . $server . '_merchant_account_id'),
            'options' => [
                'submitForSettlement' => true
            ]
        ];

        if (isset($params['company'])) {
            $data['customer']['company'] = $params['company'];
        }

        $response = \Braintree\Transaction::sale($data);

        $result = -1;

        if (is_object($response) && isset($response->success) && ($response->success === true)) {
            $result = 1;
        }

        if ($result === 1) {
            $log = [
                'id' => $response->transaction->id,
                'status' => $response->transaction->status,
                'type' => $response->transaction->type,
                'currency' => $response->transaction->currencyIsoCode,
                'amount' => $response->transaction->amount,
                'merchant_account_id' => $response->transaction->merchantAccountId,
                'date_create' => $response->transaction->createdAt->format('Y-m-d H:i:s T'),
                'company' => $response->transaction->customer['company'] ?? null
            ];
        } else {
            $log = [
                'message' => $response->message,
                'id' => $response->transaction->id ?? null,
                'status' => $response->transaction->status ?? null,
                'type' => $response->params['transaction']['type'],
                'currency' => $response->transaction->currencyIsoCode ?? null,
                'amount' => $response->params['transaction']['amount'],
                'merchant_account_id' => $response->params['transaction']['merchantAccountId'],
                'date_create' => isset($response->transaction->createdAt) ? $response->transaction->createdAt->format('Y-m-d H:i:s T') : null,
                'company' => $response->params['transaction']['customer']['company'] ?? null
            ];
        }

        static::log($result, $data, $log);

        return $response;
    }

    protected static function log($result, $request, $response)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $filter = ['paymentMethodNonce'];

        foreach ($request as $key => $value) {
            if ((strpos($key, '_nh-dns') !== false) || in_array($key, $filter)) {
                $request[$key] = '**********';
            }
        }

        $request_string = json_encode($request, JSON_PRETTY_PRINT);

        foreach ($response as $key => $value) {
            if ((strpos($key, '_nh-dns') !== false) || in_array($key, $filter)) {
                $response[$key] = '**********';
            }
        }

        $response_string = json_encode($response, JSON_PRETTY_PRINT);

        $OSCOM_PDO->save('website_api_transaction_log', [
            'app' => 'braintree',
            'user_group' => 'partner',
            'user_id' => isset($_SESSION[OSCOM::getSite()]['Account']['id']) ? $_SESSION[OSCOM::getSite()]['Account']['id'] : null,
            'module' => 'partnership',
            'action' => 'extension',
            'result' => $result,
            'server' => (OSCOM::getConfig('braintree_server') == 'production') ? 1 : -1,
            'request' => $request_string,
            'response' => $response_string,
            'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress())),
            'date_added' => 'now()'
        ]);
    }
}
