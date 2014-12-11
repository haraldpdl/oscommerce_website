<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

  use osCommerce\OM\Core\Hash;
  use osCommerce\OM\Core\HttpRequest;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;
  use osCommerce\OM\Core\Site\RPC\Controller as RPC;

  class PayPalStart {
    public static function execute() {
      $OSCOM_PDO = Registry::get('PDO');

      $result = [ 'rpcStatus=-100' ];

      if ( isset($_GET['merchantId']) && (preg_match('/^[A-Za-z0-9]{32}$/', $_GET['merchantId']) === 1) && isset($_GET['secret']) ) {
        $Qm = $OSCOM_PDO->prepare('select id, secret, return_url, account_type from :table_website_app_paypal_start where merchant_id = :merchant_id limit 1');
        $Qm->bindValue(':merchant_id', $_GET['merchantId']);
        $Qm->execute();

        if ( ($Qm->fetch() !== false) && (sha1($Qm->value('secret') . OSCOM::getConfig('app_paypal_salt', 'Website')) == $_GET['secret']) ) {
          if ( $Qm->value('account_type') == 'live' ) {
            $api_server = 'api.paypal.com';
            $api_server_auth = OSCOM::getConfig('app_paypal_live_client_id', 'Website') . ':' . OSCOM::getConfig('app_paypal_live_secret', 'Website');
          } else {
            $api_server = 'api.sandbox.paypal.com';
            $api_server_auth = OSCOM::getConfig('app_paypal_sandbox_client_id', 'Website') . ':' . OSCOM::getConfig('app_paypal_sandbox_secret', 'Website');
          }

          $result_grant = HttpRequest::getResponse( [ 'url' => 'https://' . $api_server_auth . '@' . $api_server . '/v1/oauth2/token', 'parameters' => 'grant_type=client_credentials' ] );

          if ( !empty($result_grant) ) {
            $result_grant = json_decode($result_grant, true);

            $result_api = HttpRequest::getResponse( [ 'url' => 'https://' . $api_server . '/v1/identity/applications/@classic/owner/' . $_GET['merchantId'] . '/credentials', 'header' => [ 'Content-Type: application/json', 'Authorization: ' . $result_grant['token_type'] . ' ' . $result_grant['access_token'] ], 'method' => 'get' ] );

            if ( !empty($result_api) ) {
              $result_api = json_decode($result_api, true);

              if ( isset($result_api['api_credential']) && isset($result_api['api_credential']['api_username']) && isset($result_api['api_credential']['api_password']) && isset($result_api['api_credential']['api_signature']) ) {
                $Qupdate = $OSCOM_PDO->prepare('update :table_website_app_paypal_start set account_id = :account_id, api_username = :api_username, api_password = :api_password, api_signature = :api_signature, date_set = now() where id = :id');
                $Qupdate->bindValue(':account_id', $_GET['merchantIdInPayPal']);
                $Qupdate->bindValue(':api_username', $result_api['api_credential']['api_username']);
                $Qupdate->bindValue(':api_password', $result_api['api_credential']['api_password']);
                $Qupdate->bindValue(':api_signature', $result_api['api_credential']['api_signature']);
                $Qupdate->bindInt(':id', $Qm->valueInt('id'));
                $Qupdate->execute();

                OSCOM::redirect($Qm->value('return_url'));
              }
            }
          }
        }
      } elseif ( isset($_GET['action']) && ($_GET['action'] == 'retrieve') ) {
        if ( isset($_POST['merchant_id']) && (preg_match('/^[A-Za-z0-9]{32}$/', $_POST['merchant_id']) === 1) && isset($_POST['secret']) ) {
          $Qm = $OSCOM_PDO->prepare('select id, secret, account_type, account_id, api_username, api_password, api_signature from :table_website_app_paypal_start where merchant_id = :merchant_id limit 1');
          $Qm->bindValue(':merchant_id', $_POST['merchant_id']);
          $Qm->execute();

          if ( ($Qm->fetch() !== false) && (sha1($Qm->value('secret') . OSCOM::getConfig('app_paypal_salt', 'Website')) == $_POST['secret']) ) {
            $result = array('rpcStatus=' . RPC::STATUS_SUCCESS,
                            'account_type=' . $Qm->value('account_type'),
                            'account_id=' . $Qm->value('account_id'),
                            'api_username=' . $Qm->value('api_username'),
                            'api_password=' . $Qm->value('api_password'),
                            'api_signature=' . $Qm->value('api_signature'));

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
      } else {
        if ( isset($_POST['return_url']) && (preg_match('/^https?:\/\/(.*)$/i', $_POST['return_url']) === 1) && isset($_POST['type']) && in_array($_POST['type'], array('live', 'sandbox')) && (OSCOM::getRequestType() == 'SSL') ) {
          while ( true ) {
            $merchant_id = Hash::getRandomString('32');

            $Qcheck = $OSCOM_PDO->prepare('select merchant_id from :table_website_app_paypal_start where merchant_id = :merchant_id limit 1');
            $Qcheck->bindValue(':merchant_id', $merchant_id);
            $Qcheck->execute();

            if ( $Qcheck->fetch() === false ) {
              break;
            }
          }

          $secret = Hash::getRandomString('32');

          $Qcreate = $OSCOM_PDO->prepare('insert into :table_website_app_paypal_start (merchant_id, secret, return_url, account_type, ip_address, date_added) values (:merchant_id, :secret, :return_url, :account_type, :ip_address, now())');
          $Qcreate->bindValue(':merchant_id', $merchant_id);
          $Qcreate->bindValue(':secret', $secret);
          $Qcreate->bindValue(':return_url', $_POST['return_url']);
          $Qcreate->bindValue(':account_type', $_POST['type']);
          $Qcreate->bindValue(':ip_address', sprintf('%u', ip2long(OSCOM::getIPAddress())));
          $Qcreate->execute();

          $params = array('partnerId' => ($_POST['type'] == 'live') ? OSCOM::getConfig('app_paypal_live_partner_id', 'Website') : OSCOM::getConfig('app_paypal_sandbox_partner_id', 'Website'),
                          'productIntentID' => 'addipmt',
                          'integrationType' => 'F',
                          'subIntegrationType' => 'S',
                          'returnToPartnerUrl' => base64_encode('https://ssl.oscommerce.com/index.php?RPC&Website&Index&PayPalStart&secret=' . sha1($secret . OSCOM::getConfig('app_paypal_salt', 'Website'))),
                          'displayMode' => 'regular',
                          'receiveCredentials' => 'true',
                          'partnerLogoUrl' => 'https://ssl.oscommerce.com/public/sites/Website/images/oscommerce.png',
                          'merchantId' => $merchant_id);

          if ( $_POST['type'] == 'live' ) {
            $redirect_url = 'https://www.paypal.com/webapps/merchantboarding/webflow/externalpartnerflow?';
          } else {
            $redirect_url = 'https://www.sandbox.paypal.com/webapps/merchantboarding/webflow/externalpartnerflow?';
          }

          foreach ( $params as $key => $value ) {
            $redirect_url .= $key . '=' . urlencode($value) . '&';
          }

          $redirect_url = substr($redirect_url, 0, -1);

          $result = array('rpcStatus=' . RPC::STATUS_SUCCESS,
                          'merchant_id=' . $merchant_id,
                          'redirect_url=' . $redirect_url,
                          'secret=' . sha1($secret . OSCOM::getConfig('app_paypal_salt', 'Website')));
        }
      }

      echo implode("\n", $result);
    }
  }
?>
