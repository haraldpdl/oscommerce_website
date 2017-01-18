<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\RPC;

use osCommerce\OM\Core\Hash;
use osCommerce\OM\Core\OSCOM;

use osCommerce\OM\Core\Site\Website\Partner;
use osCommerce\OM\Core\Site\Website\PayPal;

use osCommerce\OM\Core\Site\RPC\Controller as RPC;

class PartnerExtendPayment
{
    public static function execute()
    {
        $result = [];

        if (
            !isset($_SESSION[OSCOM::getSite()]['Account']) ||
            !isset($_GET['p']) ||
            empty($_GET['p']) ||
            !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['p'])
           ) {
            $result['rpcStatus'] = RPC::STATUS_NO_ACCESS;
        }

        if (!isset($result['rpcStatus'])) {
            $packages = Partner::getPackages();

            if (
                !isset($_POST['plan']) ||
                !array_key_exists($_POST['plan'], $packages) ||
                !isset($_POST['duration']) ||
                !array_key_exists($_POST['duration'], $packages[$_POST['plan']]['levels'])
               ) {
                $result['rpcStatus'] = RPC::STATUS_ERROR;
            }
        }

        if (!isset($result['rpcStatus'])) {
            $_SESSION[OSCOM::getSite()]['PartnerPayPalSecret'] = Hash::getRandomString(16, 'digits');

            $partner = Partner::getCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['p']);

            if (OSCOM::getConfig('enable_ssl') == 'true') {
                $base_url = OSCOM::getConfig('https_server') . OSCOM::getConfig('dir_ws_https_server');
            } else {
                $base_url = OSCOM::getConfig('http_server') . OSCOM::getConfig('dir_ws_http_server');
            }

            $params = [
                'METHOD' => 'SetExpressCheckout',
                'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
                'PAYMENTREQUEST_0_AMT' => $packages[$_POST['plan']]['levels'][$_POST['duration']]['price_raw'],
                'PAYMENTREQUEST_0_ITEMAMT' => $packages[$_POST['plan']]['levels'][$_POST['duration']]['price_raw'],
                'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                'PAYMENTREQUEST_0_DESC' => 'osCommerce Partnership',
                'L_PAYMENTREQUEST_0_NAME0' => $partner['title'] . ' ' . $packages[$_POST['plan']]['title'] . ' ' . $packages[$_POST['plan']]['levels'][$_POST['duration']]['title'],
                'L_PAYMENTREQUEST_0_AMT0' => $packages[$_POST['plan']]['levels'][$_POST['duration']]['price_raw'],
                'L_PAYMENTREQUEST_0_NUMBER0' => $_GET['p'] . '-' . $_POST['plan'] . '-' . $packages[$_POST['plan']]['levels'][$_POST['duration']]['duration'],
                'NOSHIPPING' => 1,
                'ALLOWNOTE' => 0,
                'SOLUTIONTYPE' => 'Sole',
                'BRANDNAME' => 'osCommerce',
                'PAYMENTREQUEST_0_CUSTOM' => $_SESSION[OSCOM::getSite()]['PartnerPayPalSecret'],
                'RETURNURL' => $base_url . 'index.php?' . OSCOM::getSiteApplication() . '&Partner&Extend=' . $_GET['p'] . '&Payment&Process',
                'CANCELURL' => $base_url . 'index.php?' . OSCOM::getSiteApplication() . '&Partner&Extend=' . $_GET['p'] . '&Payment&Cancel'
            ];

            if ($partner['billing_country_iso_code_2'] == 'DE') {
                $params['PAYMENTREQUEST_0_TAXAMT'] = $packages[$_POST['plan']]['levels'][$_POST['duration']]['price_raw'] * 0.19;
                $params['PAYMENTREQUEST_0_AMT'] += $params['PAYMENTREQUEST_0_TAXAMT'];
            }

            if (OSCOM::getConfig('enable_ssl') == 'true') {
                $params['HDRIMG'] = OSCOM::getConfig('https_server') . OSCOM::getConfig('dir_ws_https_server') . 'public/sites/Website/images/oscommerce.png';
            }

            $r = PayPal::makeCall($params);

            if (isset($r['ACK']) && in_array($r['ACK'], [
                'Success',
                'SuccessWithWarning'
            ])) {
                $result['rpcStatus'] = RPC::STATUS_SUCCESS;
                $result['token'] = $r['TOKEN'];
            }
        }

        if (!isset($result['rpcStatus'])) {
            $result['rpcStatus'] = RPC::STATUS_ERROR;
        }

        echo json_encode($result);
    }
}
