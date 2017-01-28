<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner\Extend\Payment;

use osCommerce\OM\Core\ApplicationAbstract;
use osCommerce\OM\Core\Cache;
use osCommerce\OM\Core\Mail;
use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Registry;

use osCommerce\OM\Core\Site\Website\Braintree;
use osCommerce\OM\Core\Site\Website\Partner;
use osCommerce\OM\Core\Site\Website\PayPal;
use osCommerce\OM\Core\Site\Website\Users;

class Process
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_PDO = Registry::get('PDO');
        $OSCOM_Template = Registry::get('Template');

        $result = false;

        if (isset($_POST['payment_method_nonce']) && !empty($_POST['payment_method_nonce'])) {
            $result = static::doBraintree();
        } elseif (isset($_GET['token']) && !empty($_GET['token']) && isset($_SESSION[OSCOM::getSite()]['PartnerPayPalSecret'])) {
            $result = static::doPayPal();
        }

        if (($result === false) || !is_array($result)) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_partner_payment_general'), 'error');

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        $partner = $OSCOM_Template->getValue('partner');
        $partner_campaign = $OSCOM_Template->getValue('partner_campaign');
        $packages = $OSCOM_Template->getValue('partner_packages');

        $date_campaign = new \DateTime($partner_campaign['date_end']);

        $date_now = new \DateTime();

        $date_start = ($date_campaign < $date_now) ? $date_now : $date_campaign;

        $date_end = clone $date_start;
        $date_end->add(date_interval_create_from_date_string($packages[$result['plan']]['levels'][$result['level_id']]['duration'] . ' months'));

        $data = [
            'partner_id' => $partner['id'],
            'package_id' => Partner::getPackageId($result['plan']),
            'date_added' => 'now()',
            'date_start' => $date_start->format('Y-m-d H:i:s'),
            'date_end' => $date_end->format('Y-m-d H:i:s'),
            'cost' => $result['total']
        ];

        if ($result['payment_method'] == 'braintree') {
            $data['braintree_transaction_id'] = $result['transaction_id'];
        } elseif ($result['payment_method'] == 'paypal') {
            $data['paypal_transaction_id'] = $result['transaction_id'];
        }

        $OSCOM_PDO->save('website_partner_transaction', $data);

        Partner::updatePackageLevelStatus($result['level_id']);

        Cache::clear('website_partner-' . $partner['code']);
        Cache::clear('website_partner_promotions');
        Cache::clear('website_partners');
        Cache::clear('website_carousel_frontpage');

        $email_txt_file = $OSCOM_Template->getPageContentsFile('email_partner_extension.txt');
        $email_txt_tmpl = file_exists($email_txt_file) ? file_get_contents($email_txt_file) : null;

        $email_html_file = $OSCOM_Template->getPageContentsFile('email_partner_extension.html');
        $email_html_tmpl = file_exists($email_html_file) ? file_get_contents($email_html_file) : null;

        $OSCOM_Template->setValue('user_name', $_SESSION[OSCOM::getSite()]['Account']['name']);
        $OSCOM_Template->setValue('partnership_extension_plan', $packages[$result['plan']]['title']);
        $OSCOM_Template->setValue('partnership_extension_period', $date_start->format('jS M, Y') . ' - ' . $date_end->format('jS M, Y'));

        foreach (Partner::getCampaignAdmins($partner['code']) as $admin_id) {
            $admin = Users::get($admin_id);
            $OSCOM_Template->setValue('partner_admin_name', $admin['name'], true);

            $email_txt = null;
            $email_html = null;

            if (isset($email_txt_tmpl)) {
                $email_txt = $OSCOM_Template->parseContent($email_txt_tmpl);
            }

            if (isset($email_html_tmpl)) {
                $email_html = $OSCOM_Template->parseContent($email_html_tmpl);
            }

            if (!empty($email_txt) || !empty($email_html)) {
                $OSCOM_Mail = new Mail($admin['name'], $admin['email'], 'osCommerce', 'noreply@oscommerce.com', OSCOM::getDef('email_partner_extension_subject'));

                if (!empty($email_txt)) {
                    $OSCOM_Mail->setBodyPlain($email_txt);
                }

                if (!empty($email_html)) {
                    $OSCOM_Mail->setBodyHTML($email_html);
                }

                $OSCOM_Mail->send();
            }
        }

        $OSCOM_MessageStack->add('partner', OSCOM::getDef('success_partner_payment_processed'), 'success');

        OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'] . '&Payment&Success', 'SSL'));
    }

    protected static function doBraintree()
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        $partner = $OSCOM_Template->getValue('partner');
        $partner_campaign = $OSCOM_Template->getValue('partner_campaign');
        $packages = $OSCOM_Template->getValue('partner_packages');

        $plan = $_POST['plan'];
        $level_id = $_POST['duration'];

        if (!isset($packages[$plan]) || !isset($packages[$plan]['levels'][$level_id])) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_partner_unknown_plan'), 'error');

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        $total = $packages[$plan]['levels'][$level_id]['price_raw'];

        if ($packages[$plan]['levels'][$level_id]['tax'] > 0) {
            $total += $packages[$plan]['levels'][$level_id]['tax'];
        }

        $data = [
            'nonce' => $_POST['payment_method_nonce'],
            'amount' => $total,
            'company' => $partner['title']
        ];

        $error = false;

        try {
            $braintree_result = Braintree::doSale($data);
        } catch (\Exception $e) {
            $error = true;
        }

        if (($error === false) && ($braintree_result->success === true)) {
            return [
                'plan' => $plan,
                'level_id' => $level_id,
                'total' => $total,
                'payment_method' => 'braintree',
                'transaction_id' => $braintree_result->transaction->id
            ];
        }

        $message = OSCOM::getDef('error_partner_payment_general');

        if (isset($braintree_result->transaction)) {
            if (isset($braintree_result->transaction->gatewayRejectionReason)) {
                switch ($braintree_result->transaction->gatewayRejectionReason) {
                    case 'cvv':
                        $message = OSCOM::getDef('error_partner_payment_cvv');
                        break;

                    case 'avs':
                        $message = OSCOM::getDef('error_partner_payment_avs');
                        break;

                    case 'avs_and_cvv':
                        $message = OSCOM::getDef('error_partner_payment_cvv_avs');
                        break;
                }
            }
        }

        $OSCOM_MessageStack->add('partner', $message, 'error');

        OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
    }

    protected static function doPayPal()
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        if (!isset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']) || ($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['TOKEN'] != $_GET['token'])) {
            $params = [
                'METHOD' => 'GetExpressCheckoutDetails',
                'TOKEN' => $_GET['token']
            ];

            $_SESSION[OSCOM::getSite()]['PartnerPayPalResult'] = PayPal::makeCall($params);
        }

        $pass = false;

        if (isset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['ACK']) && in_array($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['ACK'], [
            'Success',
            'SuccessWithWarning'
        ])) {
            $pass = true;
        }

        if ($pass !== true) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_partner_payment_initialization'), 'error');

            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        if ($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['PAYMENTREQUEST_0_CUSTOM'] != $_SESSION[OSCOM::getSite()]['PartnerPayPalSecret']) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_partner_payment_verification'), 'error');

            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        list($partner_code, $plan, $level_id) = explode('-', $_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['L_PAYMENTREQUEST_0_NUMBER0'], 3);

        if ($partner_code != $_GET['Extend']) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_partner_payment_unkown_account'), 'error');

            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        $partner_campaign = $OSCOM_Template->getValue('partner_campaign');
        $packages = $OSCOM_Template->getValue('partner_packages');

        if (!isset($packages[$plan]) || !isset($packages[$plan]['levels'][$level_id])) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_partner_unknown_plan'), 'error');

            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        $total = $packages[$plan]['levels'][$level_id]['price_raw'];

        if ($packages[$plan]['levels'][$level_id]['tax'] > 0) {
            $total += $packages[$plan]['levels'][$level_id]['tax'];
        }

        if (($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['PAYMENTREQUEST_0_AMT'] != $total) || ($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['PAYMENTREQUEST_0_CURRENCYCODE'] != 'EUR')) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_partner_payment_total_mismatch'), 'error');

            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        $params = [
            'METHOD' => 'DoExpressCheckoutPayment',
            'TOKEN' => $_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['TOKEN'],
            'PAYERID' => $_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['PAYERID'],
            'PAYMENTREQUEST_0_AMT' => $total,
            'PAYMENTREQUEST_0_ITEMAMT' => $packages[$plan]['levels'][$level_id]['price_raw'],
            'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR'
        ];

        if ($packages[$plan]['levels'][$level_id]['tax'] > 0) {
            $params['PAYMENTREQUEST_0_TAXAMT'] = $packages[$plan]['levels'][$level_id]['tax'];
        }

        $r = PayPal::makeCall($params);

        if (!isset($r['ACK']) || !in_array($r['ACK'], [
            'Success',
            'SuccessWithWarning'
        ])) {
            if ($r['L_ERRORCODE0'] == '10486') {
                $paypal_url = 'https://www.' . (OSCOM::getConfig('paypal_server') != 'live' ? 'sandbox.' : '') . 'paypal.com/checkoutnow?useraction=commit&token=' . $_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['TOKEN'];

                OSCOM::redirect($paypal_url);
            }

            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_partner_payment_general'), 'error');

            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);
        unset($_SESSION[OSCOM::getSite()]['PartnerPayPalSecret']);

        return [
            'plan' => $plan,
            'level_id' => $level_id,
            'total' => $total,
            'payment_method' => 'paypal',
            'transaction_id' => $r['PAYMENTINFO_0_TRANSACTIONID']
        ];
    }
}
