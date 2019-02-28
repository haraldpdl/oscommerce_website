<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Users;

use osCommerce\OM\Core\Registry;

class SaveAddress
{
    public static function execute(array $data): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $address = [
            'gender' => $data['gender'] ?? null,
            'company' => $data['company'] ?? null,
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'street_address' => $data['street'],
            'street_address_2' => $data['street2'],
            'postcode' => $data['zip'],
            'city' => $data['city'],
            'state' => $data['zone'] ?? null,
            'country_id' => $data['country_id'],
            'zone_id' => $data['zone_id'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'fax' => $data['fax'] ?? null
        ];

        if (isset($data['other'])) {
            $address['other_info'] = $data['other'];
        }

        $where = null;

        if (isset($data['public_id'])) {
            $where = [
                'user_id' => $data['user_id'],
                'address_type' => $data['type'],
                'public_id' => $data['public_id']
            ];
        } else {
            $address['user_id'] = $data['user_id'];
            $address['address_type'] = $data['type'];
            $address['public_id'] = $data['new_public_id'];
        }

        return $OSCOM_PDO->save('website_user_addresses', $address, $where) === 1;
    }
}
