<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ContactInquiry;

use osCommerce\OM\Core\Registry;

class Exists
{
    public static function execute(array $data): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        return $OSCOM_PDO->get('website_contact_inquiries', 1, [
            'inquiry_id' => $data['id']
        ], null, 1)->fetch() !== false;
    }
}
