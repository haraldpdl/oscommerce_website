<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ContactInquiry;

use osCommerce\OM\Core\Registry;

class Save
{
    public static function execute(array $data): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $inquiry = [
            'inquiry_id' => $data['inquiry_id'],
            'department_module' => $data['department'],
            'company' => $data['company'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'inquiry' => $data['inquiry'] ?? null,
            'status' => $data['status'],
            'user_id' => $data['user_id'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'language_id' => $data['language_id'] ?? null
        ];

        return $OSCOM_PDO->save('website_contact_inquiries', $inquiry) === 1;
    }
}
