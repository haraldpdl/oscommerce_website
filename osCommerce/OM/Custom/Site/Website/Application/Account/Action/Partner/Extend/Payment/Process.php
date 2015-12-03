<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2015 osCommerce; http://www.oscommerce.com
 * @license BSD; http://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner\Extend\Payment;

use osCommerce\OM\Core\ApplicationAbstract;
use osCommerce\OM\Core\Cache;
use osCommerce\OM\Core\Mail;
use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Registry;

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

        if (!isset($_GET['token']) || empty($_GET['token']) || !isset($_SESSION[OSCOM::getSite()]['PartnerPayPalSecret'])) {
            $OSCOM_MessageStack->add('partner', 'Error: The payment could not be processed. Please try again.', 'error');

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

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
            $OSCOM_MessageStack->add('partner', 'Error: The initialization of the transaction could not be verified. Please try again.', 'error');

            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        if ($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['PAYMENTREQUEST_0_CUSTOM'] != $_SESSION[OSCOM::getSite()]['PartnerPayPalSecret']) {
            $OSCOM_MessageStack->add('partner', 'Error: The transaction could not be verified. Please try again.', 'error');

            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        list($partner_code, $plan, $duration) = explode('-', $_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['L_PAYMENTREQUEST_0_NUMBER0'], 3);

        if ($partner_code != $_GET['Extend']) {
            $OSCOM_MessageStack->add('partner', 'Error: The partner account could not be associated with the transaction. Please try again.', 'error');

            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        $partner = $OSCOM_Template->getValue('partner_campaign');
        $product = Partner::getProductPlan($plan, $duration);

        $total = $product['price'];

        if ($partner['billing_country_iso_code_2'] == 'DE') {
            $total += $total * 0.19;
        }

        if (($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['PAYMENTREQUEST_0_AMT'] != $total) || ($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['PAYMENTREQUEST_0_CURRENCYCODE'] != 'EUR')) {
            $OSCOM_MessageStack->add('partner', 'Error: The total of the transaction did not match the partnership extension total. Please try again.', 'error');

            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        $params = [
            'METHOD' => 'DoExpressCheckoutPayment',
            'TOKEN' => $_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['TOKEN'],
            'PAYERID' => $_SESSION[OSCOM::getSite()]['PartnerPayPalResult']['PAYERID'],
            'PAYMENTREQUEST_0_AMT' => $total,
            'PAYMENTREQUEST_0_ITEMAMT' => $product['price'],
            'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR'
        ];

        if ($partner['billing_country_iso_code_2'] == 'DE') {
            $params['PAYMENTREQUEST_0_TAXAMT'] = $product['price'] * 0.19;
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

            $OSCOM_MessageStack->add('partner', 'Error: There was a problem performing the transaction. Please try again.', 'error');

            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);

            OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'], 'SSL'));
        }

        $date_campaign = new \DateTime($partner['date_end']);

        $date_now = new \DateTime();

        $date_start = ($date_campaign < $date_now) ? $date_now : $date_campaign;

        $date_end = clone $date_start;
        $date_end->add(date_interval_create_from_date_string($duration . ' months'));

        $data = [
            'partner_id' => $partner['id'],
            'package_id' => ($plan == 'gold') ? 3 : 2,
            'date_added' => 'now()',
            'date_start' => $date_start->format('Y-m-d H:i:s'),
            'date_end' => $date_end->format('Y-m-d H:i:s'),
            'cost' => $total,
            'paypal_transaction_id' => $r['PAYMENTINFO_0_TRANSACTIONID']
        ];

        $OSCOM_PDO->save('website_partner_transaction', $data);

        Cache::clear('website_partner-' . $partner['code']);
        Cache::clear('website_partner_promotions');
        Cache::clear('website_partners');
        Cache::clear('website_carousel_frontpage');

        $email_txt_file = $OSCOM_Template->getPageContentsFile('email_partner_extension.txt');
        $email_txt_tmpl = file_exists($email_txt_file) ? file_get_contents($email_txt_file) : null;

        $email_html_file = $OSCOM_Template->getPageContentsFile('email_partner_extension.html');
        $email_html_tmpl = file_exists($email_html_file) ? file_get_contents($email_html_file) : null;

        $OSCOM_Template->setValue('user_name', $_SESSION[OSCOM::getSite()]['Account']['name']);
        $OSCOM_Template->setValue('partnership_extension_plan', $product['plan']);
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

        unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);
        unset($_SESSION[OSCOM::getSite()]['PartnerPayPalSecret']);

        $OSCOM_MessageStack->add('partner', 'The partnership has been successfully extended!', 'success');

        OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Extend=' . $_GET['Extend'] . '&Payment&Success', 'SSL'));
    }
}
