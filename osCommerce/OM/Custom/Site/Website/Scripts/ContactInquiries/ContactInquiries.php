<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Scripts\ContactInquiries;

use osCommerce\OM\Core\{
    Mail,
    OSCOM,
    RunScript
};

use osCommerce\OM\Core\Site\Website\ContactInquiry;

class ContactInquiries implements \osCommerce\OM\Core\RunScriptInterface
{
    public static function execute()
    {
        OSCOM::initialize('Website');

        foreach (ContactInquiry::getPending() as $i) {
            try {
                $OSCOM_Mail = new Mail('hello@oscommerce.com', 'osCommerce', 'noreply@oscommerce.com', 'osCommerce', 'Contact Inquiry [' . $i['inquiry_id'] . ']');
                $OSCOM_Mail->setReplyTo($i['email'], $i['name']);
                $OSCOM_Mail->setBody('Department: ' . $i['department_module'] . "\n" . 'Inquiry ID: ' . $i['inquiry_id'] . "\n" . 'Company: ' . $i['company'] . "\n" . 'From: ' . $i['name'] . "\n" . 'Email: ' . $i['email'] . "\n" . 'User ID: ' . $i['user_id'] . "\n" . 'IP Address: ' . long2ip($i['ip_address']) . "\n\n" . $i['inquiry']);
                $OSCOM_Mail->send();

                ContactInquiry::setStatus($i['id'], ContactInquiry::STATUS_NOTIFIED);
            } catch (\Exception $e) {
                RunScript::error('(ContactInquiries) ' . $e->getMessage());
                exit;
            }
        }
    }
}
