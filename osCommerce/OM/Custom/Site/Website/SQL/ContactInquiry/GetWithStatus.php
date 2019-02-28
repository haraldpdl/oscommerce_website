<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ContactInquiry;

use osCommerce\OM\Core\Registry;

class GetWithStatus
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $where = [
            'status' => $data['status']
        ];

        if (isset($data['department'])) {
            $data['department_module'] = $data['department'];
        }

        return $OSCOM_PDO->get('website_contact_inquiries', '*', $where, 'date_added')->fetchAll();
    }
}
