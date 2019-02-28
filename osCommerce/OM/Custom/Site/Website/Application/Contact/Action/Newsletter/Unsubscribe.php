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

use osCommerce\OM\Core\Site\Website\Newsletter;

class Unsubscribe
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        if (isset($_GET['key']) && isset($_GET['sig'])) {
            $sub_key = Sanitize::simple($_GET['key'] ?? null);
            $sub_sig = Sanitize::simple($_GET['sig'] ?? null);

            if ((mb_strlen($sub_key) !== 32) || (mb_strlen($sub_sig) !== 40)) {
                $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_unsubscribe_invalid_key'), 'error');

                return false;
            }

            $sub = Newsletter::getSubscription($sub_key);

            if (($sub === null) || ($sub_sig !== sha1($sub['list_id'] . $sub['email']))) {
                $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_unsubscribe_invalid_key'), 'error');

                return false;
            }

            if (Newsletter::unsubscribe($sub_key)) {
                $application->setPageContent('newsletter_unsubscribed.html');
            } else {
                $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_unsubscribe_general_error'), 'error');
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
            $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_unsubscribe_email_ms_error_invalid'), 'error');

            return false;
        }

        if (!Newsletter::isSubscribed($email, 1)) {
            $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_unsubscribe_ms_error_not_subscribed'), 'error');

            return false;
        }

        $sub = Newsletter::getSubscriptionKey($email, 1);

        if (is_array($sub)) {
            $send_email = true;

            if (isset($sub['optout_req_time'])) {
                $date_now = new \DateTime();
                $date_req = new \DateTime($sub['optout_req_time']);

                $seconds = $date_now->getTimestamp() - $date_req->getTimestamp();
                $minutes = intval($seconds / 60);

                if ($minutes >= 5) {
                    $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_unsubscription_resent_ms_warning'), 'warning');
                } else {
                    $send_email = false;

                    $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_unsubscription_pending_ms_error'), 'error');
                }
            } else {
                $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('newsletter_unsubscription_sent_ms_warning'), 'warning');
            }

            if ($send_email === true) {
                Newsletter::updateSubscriptionOptOutRequest($email, 1);

                $OSCOM_Template->setValue('newsletter_sub_key', $sub['sub_key']);
                $OSCOM_Template->setValue('newsletter_sub_sig', sha1('1' . $email));

                $email_txt_file = $OSCOM_Template->getPageContentsFile('email_unsubscribe.txt');
                $email_txt = file_exists($email_txt_file) ? $OSCOM_Template->parseContent(file_get_contents($email_txt_file)) : null;

                $email_html_file = $OSCOM_Template->getPageContentsFile('email_unsubscribe.html');
                $email_html = file_exists($email_html_file) ? $OSCOM_Template->parseContent(file_get_contents($email_html_file)) : null;

                if (!empty($email_txt) || !empty($email_html)) {
                    $OSCOM_Mail = new Mail($email, null, 'newsletter@oscommerce.com', 'osCommerce Newsletter', OSCOM::getDef('newsletter_subscription_unsubscribe_email_subject'));

                    if (!empty($email_txt)) {
                        $OSCOM_Mail->setBodyPlain($email_txt);
                    }

                    if (!empty($email_html)) {
                        $OSCOM_Mail->setBodyHTML($email_html);
                    }

                    $OSCOM_Mail->send();
                }
            }
        } else {
            $OSCOM_MessageStack->add('newsletter', OSCOM::getDef('error_general'), 'error');
        }
    }
}
