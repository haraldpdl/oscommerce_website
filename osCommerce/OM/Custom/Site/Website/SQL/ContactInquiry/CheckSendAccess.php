<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ContactInquiry;

use osCommerce\OM\Core\Registry;

class CheckSendAccess
{
    public static function execute(array $data): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $where = [
            'ip_address' => $data['ip_address'],
            'date_added' => [
                'op' => '>',
                'val' => $data['date']
            ]
        ];

        if (isset($data['department'])) {
            $where['department_module'] = $data['department'];
        }

        $Qcheck = $OSCOM_PDO->get('website_contact_inquiries', 1, $where, null, 1);

        return count($Qcheck->fetchAll()) < 1;
    }
}
