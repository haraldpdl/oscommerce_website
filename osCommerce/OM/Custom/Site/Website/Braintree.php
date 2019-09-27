<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
    OSCOM,
    Registry,
    TransactionId
};

use osCommerce\OM\Core\Site\Website\{
    Invoices,
    Users
};

class Braintree
{
    const WEB_DROPIN_VERSION = '1.20.1';

    /** @var string */
    protected static $environment;

    /** @var string */
    protected static $merchant_account_id;

    protected static function setupCredentials()
    {
        $OSCOM_Currency = Registry::get('Currency');

        static::$environment = OSCOM::getConfig('braintree_server');

        \Braintree\Configuration::environment(static::$environment);
        \Braintree\Configuration::merchantId(OSCOM::getConfig('braintree_' . static::$environment . '_merchant_id'));
        \Braintree\Configuration::publicKey(OSCOM::getConfig('braintree_' . static::$environment . '_public_key'));
        \Braintree\Configuration::privateKey(OSCOM::getConfig('braintree_' . static::$environment . '_private_key'));

        static::$merchant_account_id = OSCOM::getConfig('braintree_' . static::$environment . '_merchant_account_' . strtolower($OSCOM_Currency->getDefault()) . '_id');
    }

    public static function getClientToken(array $log_params, array $request)
    {
        if (!isset(static::$merchant_account_id)) {
            static::setupCredentials();
        }

        $client_token = \Braintree\ClientToken::generate([
            'merchantAccountId' => static::$merchant_account_id
        ]);

        static::log($log_params, (is_string($client_token) && !empty($client_token) ? 1 : -1), $request, ['message' => 'getClientToken' . (isset($request['currency']) ? '; ' . $request['currency'] : '') . (isset($request['totals']) ? '; ' . $request['totals']['total']['cost'] : '')], false);

        return $client_token;
    }

    public static function doSale(array $params, array $log_params, array $invoice = null)
    {
        if (!isset(static::$merchant_account_id)) {
            static::setupCredentials();
        }

        $OSCOM_Currency = Registry::get('Currency');

        $data = [
            'merchantAccountId' => static::$merchant_account_id,
            'options' => [
                'submitForSettlement' => true
            ]
        ];

        $data = array_merge($data, $params);

        if (!isset($data['orderId'])) {
            $data['orderId'] = TransactionId::get('btr');
        }

        $order_id = $data['orderId'];

        $response = \Braintree\Transaction::sale($data);

        $result = -1;

        if (is_object($response) && isset($response->success) && ($response->success === true)) {
            $result = 1;
        }

        if ($result === 1) {
            $log = [
                'message' => 'doSale; ' . $OSCOM_Currency->show($response->transaction->amount, $response->transaction->currencyIsoCode, null, false),
                'id' => $response->transaction->id,
                'payment_type' => $response->transaction->paymentInstrumentType,
                'order_id' => $response->transaction->orderId,
                'status' => $response->transaction->status,
                'type' => $response->transaction->type,
                'currency' => $response->transaction->currencyIsoCode,
                'amount' => $response->transaction->amount,
                'merchant_account_id' => $response->transaction->merchantAccountId,
                'date_create' => $response->transaction->createdAt->format('Y-m-d H:i:s T')
            ];

            if (isset($response->transaction->customer['company'])) {
                $log['company'] = $response->transaction->customer['company'];
            }
        } else {
            $log = [
                'message' => $response->message . (isset($response->transaction->gatewayRejectionReason) && !empty($response->transaction->gatewayRejectionReason) ? ' (' . $response->transaction->gatewayRejectionReason . ')' : ''),
                'id' => $response->transaction->id ?? null,
                'payment_type' => $response->transaction->paymentInstrumentType ?? null,
                'order_id' => $response->params['transaction']['orderId'],
                'status' => $response->transaction->status ?? null,
                'type' => $response->params['transaction']['type'],
                'currency' => $response->transaction->currencyIsoCode ?? null,
                'amount' => $response->params['transaction']['amount'],
                'merchant_account_id' => $response->params['transaction']['merchantAccountId'],
                'date_create' => isset($response->transaction->createdAt) ? $response->transaction->createdAt->format('Y-m-d H:i:s T') : null
            ];

            if (isset($response->params['transaction']['customer']['company'])) {
                $log['company'] = $response->params['transaction']['customer']['company'];
            }
        }

        $api_id = static::log($log_params, $result, $data, $log);

        if (($result === 1) && isset($invoice)) {
            Invoices::save([
                'transaction_number' => $order_id,
                'user_id' => $invoice['user_id'],
                'title' => $invoice['title'],
                'billing_address' => json_encode($invoice['billing_address'], JSON_PRETTY_PRINT),
                'items' => json_encode($invoice['items'], JSON_PRETTY_PRINT),
                'totals' => json_encode($invoice['totals'], JSON_PRETTY_PRINT),
                'cost' => $invoice['cost'],
                'currency_id' => $invoice['currency_id'],
                'language_id' => $invoice['language_id'],
                'status' => $invoice['status'],
                'api_transaction_id' => $api_id,
                'module' => $invoice['module'] ?? null
            ]);
        }

        return $response;
    }

    protected static function log(array $params, int $result, array $request, array $response, bool $store_in_db = true): ?int
    {
        $log_id = null;

        if ($store_in_db === true) {
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
                'user_group' => $params['user_group'],
                'user_id' => $_SESSION[OSCOM::getSite()]['Account']['id'] ?? null,
                'module' => $params['module'],
                'action' => $params['action'],
                'result' => $result,
                'server' => (static::$environment == 'production') ? 1 : -1,
                'request' => $request_string,
                'response' => $response_string,
                'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress())),
                'date_added' => 'now()'
            ]);

            $log_id = $OSCOM_PDO->lastInsertId();
        }

        trigger_error('OSCOM\Site\Website\Braintree::log(): ' . (isset($log_id) ? '[' . $log_id . ']' : '') . ' ' . (($result === 1) ? 'Success' : 'Error') . ': [User: ' . (isset($_SESSION[OSCOM::getSite()]['Account']['id']) ? Users::get($_SESSION[OSCOM::getSite()]['Account']['id'], 'name') . ' (' . $_SESSION[OSCOM::getSite()]['Account']['id'] . ')' : null) . '] ' . $params['module'] . ' ' . $params['action'] . (isset($response['message']) ? ' (' . $response['message'] . ')' : null));

        return $log_id;
    }
}
