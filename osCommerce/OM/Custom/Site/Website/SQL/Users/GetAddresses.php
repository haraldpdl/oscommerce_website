<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Users;

use osCommerce\OM\Core\Registry;

class GetAddresses
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qaddresses = $OSCOM_PDO->get([
            'website_user_addresses a' => [
                'rel' => 'zones z',
                'on' => 'a.zone_id = z.zone_id'
            ],
            'countries c'
        ], [
            'a.public_id',
            'a.address_type as type',
            'a.gender',
            'a.company',
            'a.firstname',
            'a.lastname',
            'a.street_address as street',
            'a.street_address_2 as street2',
            'a.suburb',
            'a.postcode as zip',
            'a.city',
            'a.state',
            'a.telephone',
            'a.fax',
            'a.other_info as other',
            'c.countries_iso_code_2 as country_iso_2',
            'z.zone_code'
        ], [
            'a.user_id' => $data['user_id'],
            'a.country_id' => [
                'rel' => 'c.countries_id'
            ]
        ]);

        return $Qaddresses->fetchAll();
    }
}
