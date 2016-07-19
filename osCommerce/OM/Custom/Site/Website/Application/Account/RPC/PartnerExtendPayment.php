<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2015 osCommerce; http://www.oscommerce.com
 * @license BSD; http://www.oscommerce.com/bsdlicense.txt
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
            if (
                !isset($_POST['plan']) ||
                !in_array($_POST['plan'], ['silver', 'gold']) ||
                !isset($_POST['duration']) ||
                !in_array($_POST['duration'], ['1', '3', '6', '12', '18', '24'])
               ) {
                $result['rpcStatus'] = RPC::STATUS_ERROR;
            }
        }

        if (!isset($result['rpcStatus'])) {
            $_SESSION[OSCOM::getSite()]['PartnerPayPalSecret'] = Hash::getRandomString(16, 'digits');

            $partner = Partner::getCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['p']);
            $product = Partner::getProductPlan($_POST['plan'], $_POST['duration']);

            if (OSCOM::getConfig('enable_ssl') == 'true') {
                $base_url = OSCOM::getConfig('https_server') . OSCOM::getConfig('dir_ws_https_server');
            } else {
                $base_url = OSCOM::getConfig('http_server') . OSCOM::getConfig('dir_ws_http_server');
            }

            $params = [
                'METHOD' => 'SetExpressCheckout',
                'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
                'PAYMENTREQUEST_0_AMT' => $product['price'],
                'PAYMENTREQUEST_0_ITEMAMT' => $product['price'],
                'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                'PAYMENTREQUEST_0_DESC' => 'osCommerce Partnership Extension',
                'L_PAYMENTREQUEST_0_NAME0' => $partner['title'] . ' ' . $product['plan'] . ' ' . $product['duration'],
                'L_PAYMENTREQUEST_0_AMT0' => $product['price'],
                'L_PAYMENTREQUEST_0_NUMBER0' => $_GET['p'] . '-' . $_POST['plan'] . '-' . $_POST['duration'],
                'NOSHIPPING' => 1,
                'ALLOWNOTE' => 0,
                'SOLUTIONTYPE' => 'Sole',
                'BRANDNAME' => 'osCommerce',
                'PAYMENTREQUEST_0_CUSTOM' => $_SESSION[OSCOM::getSite()]['PartnerPayPalSecret'],
                'RETURNURL' => $base_url . 'index.php?' . OSCOM::getSiteApplication() . '&Partner&Extend=' . $_GET['p'] . '&Payment&Process',
                'CANCELURL' => $base_url . 'index.php?' . OSCOM::getSiteApplication() . '&Partner&Extend=' . $_GET['p'] . '&Payment&Cancel'
            ];

            if ($partner['billing_country_iso_code_2'] == 'DE') {
                $params['PAYMENTREQUEST_0_TAXAMT'] = $product['price'] * 0.19;
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
