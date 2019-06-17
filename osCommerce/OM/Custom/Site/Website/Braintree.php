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

use osCommerce\OM\Core\Site\Website\Invoices;

class Braintree
{
    const WEB_VERSION = '3.46.0';
    const WEB_DROPIN_VERSION = '1.18.0';

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

    public static function doSale($params, $log_params, array $invoice = null)
    {
        if (static::$has_setup === false) {
            static::setupCredentials();
        }

        $server = OSCOM::getConfig('braintree_server');

        $data = [
            'merchantAccountId' => OSCOM::getConfig('braintree_' . $server . '_merchant_account_id'),
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

    protected static function log(array $params, int $result, array $request, array $response): int
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
            'user_group' => $params['user_group'],
            'user_id' => isset($_SESSION[OSCOM::getSite()]['Account']['id']) ? $_SESSION[OSCOM::getSite()]['Account']['id'] : null,
            'module' => $params['module'],
            'action' => $params['action'],
            'result' => $result,
            'server' => (OSCOM::getConfig('braintree_server') == 'production') ? 1 : -1,
            'request' => $request_string,
            'response' => $response_string,
            'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress())),
            'date_added' => 'now()'
        ]);

        return $OSCOM_PDO->lastInsertId();
    }
}
