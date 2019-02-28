<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Contact\Action\Newsletter;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    Mail,
    OSCOM,
    Registry,
    Sanitize
};

use osCommerce\OM\Core\Site\Website\{
    Invision,
    Newsletter
};

class Subscribe
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        if (isset($_GET['key']) && isset($_GET['sig'])) {
            $pending_key = Sanitize::simple($_GET['key'] ?? null);
            $pending_sig = Sanitize::simple($_GET['sig'] ?? null);

            if ((mb_strlen($pending_key) !== 32) || (mb_strlen($pending_sig) !== 40)) {
                $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_subscription_confirm_invalid_key'), 'error');

                return false;
            }

            $pending = Newsletter::getPendingSubscription($pending_key);

            if (($pending === null) || ($pending_sig !== sha1($pending['list_id'] . $pending['email']))) {
                $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_subscription_confirm_invalid_key'), 'error');

                return false;
            }

            if (Newsletter::subscribe($pending_key)) {
                $application->setPageContent('newsletter_subscribed.html');
            } else {
                $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_subscription_confirm_general_error'), 'error');
            }

            return false;
        }

        $public_token = Sanitize::simple($_POST['public_token'] ?? null);
        $email = Sanitize::simple($_POST['email'] ?? null);

        if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
            $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('error_form_protect_general'), 'error');

            return false;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_subscribe_email_ms_error_invalid'), 'error');

            return false;
        }

        if (Invision::isFilterBanned(OSCOM::getIPAddress(), 'ip')) {
            $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_subscribe_ip_address_ms_error_filter_banned'), 'error');

            return false;
        }

        if (Invision::isFilterBanned($email, 'email')) {
            $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_subscribe_email_ms_error_filter_banned'), 'error');

            return false;
        }

        if (Newsletter::isSubscribed($email, 1)) {
            $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_subscribe_email_ms_error_already_subscribed'), 'error');

            return false;
        }

        $pending_key = null;

        if (Newsletter::isSubscriptionPending($email, 1)) {
            $sub = Newsletter::getPendingSubscriptionKey($email, 1);

            if (is_array($sub)) {
                $date_now = new \DateTime();
                $date_sub = new \DateTime($sub['optin_time']);

                $seconds = $date_now->getTimestamp() - $date_sub->getTimestamp();
                $minutes = intval($seconds / 60);

                if ($minutes >= 5) {
                    $pending_key = $sub['pending_key'];

                    Newsletter::updatePendingSubscription($email, 1);

                    $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_subscription_pending_resent_ms_warning'), 'warning');
                } else {
                    $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_subscription_pending_ms_error'), 'error');
                }
            } else {
                $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('error_general'), 'error');
            }
        } else {
            $pending_key = Newsletter::savePendingSubscription($email, 1);

            if ($pending_key === null) {
                $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('error_general'), 'error');
            }
        }

        if ($pending_key !== null) {
            $OSCOM_Template->setValue('newsletter_pending_key', $pending_key);
            $OSCOM_Template->setValue('newsletter_pending_sig', sha1('1' . $email));

            $email_txt_file = $OSCOM_Template->getPageContentsFile('email_pending.txt');
            $email_txt = file_exists($email_txt_file) ? $OSCOM_Template->parseContent(file_get_contents($email_txt_file)) : null;

            $email_html_file = $OSCOM_Template->getPageContentsFile('email_pending.html');
            $email_html = file_exists($email_html_file) ? $OSCOM_Template->parseContent(file_get_contents($email_html_file)) : null;

            if (!empty($email_txt) || !empty($email_html)) {
                $OSCOM_Mail = new Mail($email, null, 'newsletter@oscommerce.com', 'osCommerce Newsletter', OSCOM::getDef('newsletter_subscription_pending_email_subject'));

                if (!empty($email_txt)) {
                    $OSCOM_Mail->setBodyPlain($email_txt);
                }

                if (!empty($email_html)) {
                    $OSCOM_Mail->setBodyHTML($email_html);
                }

                $OSCOM_Mail->send();
            }

            $application->setPageContent('newsletter_pending.html');
        }
    }
}
