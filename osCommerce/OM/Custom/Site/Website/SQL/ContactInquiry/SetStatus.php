<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ContactInquiry;

use osCommerce\OM\Core\Registry;

class SetStatus
{
    public static function execute(array $data): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        return $OSCOM_PDO->save('website_contact_inquiries', [
            'status' => $data['status']
        ], [
            'id' => $data['id']
        ]) === 1;
    }
}
