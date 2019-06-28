<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\{
    Hash,
    HttpRequest,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\RPC\Controller as RPC;

class PayPalStart
{
    public static function execute()
    {
        $OSCOM_PDO = Registry::get('PDO');

        $result = [
            'rpcStatus' => -100
        ];

        if (OSCOM::getRequestType() == 'SSL') {
            $api_credentials = [
                'live' => [
                    'server' => 'api.paypal.com',
                    'auth' => OSCOM::getConfig('app_paypal_live_client_id', 'Website') . ':' . OSCOM::getConfig('app_paypal_live_secret', 'Website'),
                    'partner_id' => OSCOM::getConfig('app_paypal_live_partner_id', 'Website')
                ],
                'sandbox' => [
                    'server' => 'api.sandbox.paypal.com',
                    'auth' => OSCOM::getConfig('app_paypal_sandbox_client_id', 'Website') . ':' . OSCOM::getConfig('app_paypal_sandbox_secret', 'Website'),
                    'partner_id' => OSCOM::getConfig('app_paypal_sandbox_partner_id', 'Website')
                ]
            ];

            if (isset($_GET['merchantId']) && (preg_match('/^[A-Za-z0-9]{32}$/', $_GET['merchantId']) === 1) && isset($_GET['secret']) && isset($_GET['merchantIdInPayPal'])) {
                $Qm = $OSCOM_PDO->prepare('select id, secret, return_url, account_type from :table_website_app_paypal_start where merchant_id = :merchant_id limit 1');
                $Qm->bindValue(':merchant_id', $_GET['merchantId']);
                $Qm->execute();

                if (($Qm->fetch() !== false) && (sha1($Qm->value('secret') . OSCOM::getConfig('app_paypal_salt', 'Website')) == $_GET['secret'])) {
                    $server = ($Qm->value('account_type') == 'live') ? 'live' : 'sandbox';

                    $result_grant = HttpRequest::getResponse([
                        'url' => 'https://' . $api_credentials[$server]['auth'] . '@' . $api_credentials[$server]['server'] . '/v1/oauth2/token',
                        'header' => [
                            'Accept: application/json'
                        ],
                        'parameters' => 'grant_type=client_credentials'
                    ]);

                    if (!empty($result_grant)) {
                        $result_grant = json_decode($result_grant, true);

                        if (is_array($result_grant)) {
                            if (isset($result_grant['access_token']) && isset($result_grant['token_type'])) {
                                $result_api = HttpRequest::getResponse([
                                    'url' => 'https://' . $api_credentials[$server]['server'] . '/v1/customer/partners/' . $api_credentials[$server]['partner_id'] . '/merchant-integrations/' . $_GET['merchantIdInPayPal'],
                                    'header' => [
                                        'Content-Type: application/json',
                                        'Authorization: ' . $result_grant['token_type'] . ' ' . $result_grant['access_token']
                                    ],
                                    'method' => 'get'
                                ]);

                                if (!empty($result_api)) {
                                    $result_api = json_decode($result_api, true);

                                    if (is_array($result_api)) {
                                        if (isset($result_api['api_credentials']) && isset($result_api['api_credentials']['signature']) && isset($result_api['api_credentials']['signature']['api_user_name']) && isset($result_api['api_credentials']['signature']['api_password']) && isset($result_api['api_credentials']['signature']['signature'])) {
                                            $Qupdate = $OSCOM_PDO->prepare('update :table_website_app_paypal_start set account_id = :account_id, api_username = :api_username, api_password = :api_password, api_signature = :api_signature, date_set = now() where id = :id');
                                            $Qupdate->bindValue(':account_id', $_GET['merchantIdInPayPal']);
                                            $Qupdate->bindValue(':api_username', $result_api['api_credentials']['signature']['api_user_name']);
                                            $Qupdate->bindValue(':api_password', $result_api['api_credentials']['signature']['api_password']);
                                            $Qupdate->bindValue(':api_signature', $result_api['api_credentials']['signature']['signature']);
                                            $Qupdate->bindInt(':id', $Qm->valueInt('id'));
                                            $Qupdate->execute();

                                            OSCOM::redirect($Qm->value('return_url'));
                                        } elseif (isset($result_api['name'])) {
                                            $result['rpcStatus'] = -110;

                                            trigger_error('PayPalStart RETRIEVE CALL; ' . $result_api['name']);
                                        } else {
                                            $result['rpcStatus'] = -110;

                                            trigger_error('PayPalStart RETRIEVE CALL GENERAL' . "\n" . print_r($result_api, true));
                                        }
                                    } else {
                                        $result['rpcStatus'] = -110;

                                        trigger_error('PayPalStart RETRIEVE CALL NONARRAY');
                                    }
                                } else {
                                    $result['rpcStatus'] = -110;

                                    trigger_error('PayPalStart RETRIEVE CALL EMPTY');
                                }
                            } else {
                                $result['rpcStatus'] = -110;

                                trigger_error('PayPalStart RETRIEVE GRANT; ' . $result_grant['error'] . ': ' . $result_grant['error_description']);
                            }
                        } else {
                            trigger_error('PayPalStart RETRIEVE GRANT NONARRAY');
                        }
                    } else {
                        trigger_error('PayPalStart RETRIEVE GRANT EMPTY');
                    }
                }
            } elseif (isset($_GET['action']) && ($_GET['action'] == 'retrieve')) {
                if (isset($_POST['merchant_id']) && (preg_match('/^[A-Za-z0-9]{32}$/', $_POST['merchant_id']) === 1) && isset($_POST['secret'])) {
                    $Qm = $OSCOM_PDO->prepare('select id, secret, account_type, account_id, api_username, api_password, api_signature from :table_website_app_paypal_start where merchant_id = :merchant_id limit 1');
                    $Qm->bindValue(':merchant_id', $_POST['merchant_id']);
                    $Qm->execute();

                    if (($Qm->fetch() !== false) && (sha1($Qm->value('secret') . OSCOM::getConfig('app_paypal_salt', 'Website')) == $_POST['secret'])) {
                        $result = [
                            'rpcStatus' => RPC::STATUS_SUCCESS,
                            'account_type' => $Qm->value('account_type'),
                            'account_id' => $Qm->value('account_id'),
                            'api_username' => $Qm->value('api_username'),
                            'api_password' => $Qm->value('api_password'),
                            'api_signature' => $Qm->value('api_signature')
                        ];

                        $Qnull = $OSCOM_PDO->prepare('update :table_website_app_paypal_start set return_url = :return_url, account_id = :account_id, api_username = :api_username, api_password = :api_password, api_signature = :api_signature, date_retrieved = now() where id = :id');
                        $Qnull->bindNull(':return_url');
                        $Qnull->bindNull(':account_id');
                        $Qnull->bindNull(':api_username');
                        $Qnull->bindNull(':api_password');
                        $Qnull->bindNull(':api_signature');
                        $Qnull->bindInt(':id', $Qm->valueInt('id'));
                        $Qnull->execute();
                    }
                }
            } elseif (isset($_POST['return_url']) && (preg_match('/^https?:\/\/(.*)$/i', $_POST['return_url']) === 1) && isset($_POST['type']) && in_array($_POST['type'], ['live', 'sandbox'])) {
                $server = ($_POST['type'] == 'live') ? 'live' : 'sandbox';

                $result_grant = HttpRequest::getResponse([
                    'url' => 'https://' . $api_credentials[$server]['auth'] . '@' . $api_credentials[$server]['server'] . '/v1/oauth2/token',
                    'header' => [
                        'Accept: application/json'
                    ],
                    'parameters' => 'grant_type=client_credentials'
                ]);

                if (!empty($result_grant)) {
                    $result_grant = json_decode($result_grant, true);

                    if (is_array($result_grant)) {
                        if (isset($result_grant['access_token']) && isset($result_grant['token_type'])) {
                            while (true) {
                                $merchant_id = Hash::getRandomString(32);

                                $Qcheck = $OSCOM_PDO->prepare('select merchant_id from :table_website_app_paypal_start where merchant_id = :merchant_id limit 1');
                                $Qcheck->bindValue(':merchant_id', $merchant_id);
                                $Qcheck->execute();

                                if ($Qcheck->fetch() === false) {
                                    break;
                                }
                            }

                            $secret = Hash::getRandomString(32);

                            $Qcreate = $OSCOM_PDO->prepare('insert into :table_website_app_paypal_start (merchant_id, secret, return_url, account_type, ip_address, date_added) values (:merchant_id, :secret, :return_url, :account_type, :ip_address, now())');
                            $Qcreate->bindValue(':merchant_id', $merchant_id);
                            $Qcreate->bindValue(':secret', $secret);
                            $Qcreate->bindValue(':return_url', $_POST['return_url']);
                            $Qcreate->bindValue(':account_type', $_POST['type']);
                            $Qcreate->bindValue(':ip_address', sprintf('%u', ip2long(OSCOM::getIPAddress())));
                            $Qcreate->execute();

                            $data = [
                                'customer_data' => [
                                    'customer_type' => 'MERCHANT'
                                ],
                                'requested_capabilities' => [
                                    [
                                        'capability' => 'API_INTEGRATION',
                                        'api_integration_preference' => [
                                            'partner_id' => $api_credentials[$server]['partner_id'],
                                            'classic_api_integration_type' => 'FIRST_PARTY_INTEGRATED',
                                            'classic_first_party_details' => 'SIGNATURE'
                                        ]
                                    ]
                                ],
                                'web_experience_preference' => [
                                    'partner_logo_url' => 'https://www.oscommerce.com/public/sites/Website/images/oscommerce.png',
                                    'return_url' => 'https://www.oscommerce.com/index.php?RPC&Website&Index&PayPalStart&v=2&merchantId=' . $merchant_id . '&secret=' . sha1($secret . OSCOM::getConfig('app_paypal_salt', 'Website'))
                                ],
                                'collected_consents' => [
                                    [
                                        'type' => 'SHARE_DATA_CONSENT',
                                        'granted' => true
                                    ]
                                ],
                                'products' => [
                                    'EXPRESS_CHECKOUT'
                                ]
                            ];

                            $data_orig = $data;

                            if (isset($_POST['email']) && !empty($_POST['email']) && (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) !== false)) {
                                $data['customer_data']['person_details']['email_address'] = $_POST['email'];
                            }

                            if (isset($_POST['firstname']) && !empty($_POST['firstname']) && isset($_POST['surname'])) {
                                $data['customer_data']['person_details']['name']['given_name'] = $_POST['firstname'];
                                $data['customer_data']['person_details']['name']['surname'] = $_POST['surname'];
                            }

                            if (isset($_POST['site_name']) && !empty($_POST['site_name'])) {
                                $data['customer_data']['business_details']['names'][0]['type'] = 'LEGAL';
                                $data['customer_data']['business_details']['names'][0]['name'] = $_POST['site_name'];
                            }

                            if (isset($_POST['site_url']) && !empty($_POST['site_url'])) {
                                $data['customer_data']['business_details']['website_urls'][0] = $_POST['site_url'];
                            }

                            if (isset($_POST['site_currency']) && !empty($_POST['site_currency']) && (preg_match('/^[A-Z]{3}$/', $_POST['site_currency']) === 1)) {
                                $data['customer_data']['primary_currency_code'] = $_POST['site_currency'];
                            }

                            $tries = 1;

                            callInitialize:

                            $result_api = HttpRequest::getResponse([
                                'url' => 'https://' . $api_credentials[$server]['server'] . '/v1/customer/partner-referrals',
                                'header' => [
                                    'Content-Type: application/json',
                                    'Authorization: ' . $result_grant['token_type'] . ' ' . $result_grant['access_token']
                                ],
                                'parameters' => json_encode($data)
                            ]);

                            if (!empty($result_api)) {
                                $result_api = json_decode($result_api, true);

                                if (is_array($result_api)) {
                                    if (isset($result_api['links'])) {
                                        foreach ($result_api['links'] as $l) {
                                            if (isset($l['href']) && isset($l['rel']) && ($l['rel'] == 'action_url')) {
                                                $result = [
                                                    'rpcStatus' => RPC::STATUS_SUCCESS,
                                                    'merchant_id' => $merchant_id,
                                                    'redirect_url' => $l['href'],
                                                    'secret' => sha1($secret . OSCOM::getConfig('app_paypal_salt', 'Website'))
                                                ];

                                                break;
                                            }
                                        }
                                    // if pref-fill data is invalid, try again without any pre-fill values
                                    } elseif (isset($result_api['name']) && ($result_api['name'] == 'VALIDATION_ERROR') && ($tries === 1)) {
                                        $tries += 1;

                                        $data = $data_orig;

                                        goto callInitialize;
                                    } elseif (isset($result_api['name'])) {
                                        $result['rpcStatus'] = -110;

                                        trigger_error('PayPalStart INIT CALL; ' . $result_api['name']);
                                    } else {
                                        $result['rpcStatus'] = -110;

                                        trigger_error('PayPalStart INIT CALL GENERAL' . "\n" . print_r($result_api, true));
                                    }
                                } else {
                                    trigger_error('PayPalStart INIT CALL NONARRAY');
                                }
                            } else {
                                trigger_error('PayPalStart INIT CALL EMPTY');
                            }
                        } else {
                            $result['rpcStatus'] = -110;

                            trigger_error('PayPalStart INIT GRANT; ' . $result_grant['error'] . ': ' . $result_grant['error_description']);
                        }
                    } else {
                        trigger_error('PayPalStart INIT GRANT NONARRAY');
                    }
                } else {
                    trigger_error('PayPalStart INIT GRANT EMPTY');
                }
            }
        }

        if (isset($_GET['v']) && is_numeric($_GET['v']) && ($_GET['v'] === '2')) {
            header('Cache-Control: max-age=10800, must-revalidate');
            header_remove('Pragma');
            header('Content-Type: application/javascript');

            echo json_encode($result);
        } else {
            $result_squashed = [];

            foreach ($result as $k => $v) {
                $result_squashed[] = $k . '=' . $v;
            }

            echo implode("\n", $result_squashed);
        }
    }
}
