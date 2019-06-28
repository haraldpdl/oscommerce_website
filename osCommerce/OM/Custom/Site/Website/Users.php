<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
    Hash,
    Registry
};

use osCommerce\OM\Core\Site\Website\Invision;

use osCommerce\OM\Core\Site\Apps\Cache;

use osCommerce\OM\Core\Site\Shop\Address;

class Users
{
    const GROUP_GUEST_ID = 2;
    const GROUP_MEMBER_ID = 3;
    const GROUP_ADMIN_ID = 4;
    const GROUP_TEAM_CORE_ID = 6;
    const GROUP_AMBASSADOR_ID = 10;
    const GROUP_PARTNER_ID = 17;
    const GROUP_TEAM_COMMUNITY_ID = 19;

    const AMBASSADOR_LEVEL_PRICE = 49;
    const CUSTOMFIELD_AMBASSADOR_LEVEL_ID = 23;

    protected static $users = [];
    protected static $users_custom = [];

    public static function get(int $id, string $key = null)
    {
        if (!isset(static::$users[$id])) {
            $CACHE_User = new Cache('users-' . $id);

            if (($result = $CACHE_User->get()) === false) {
                $result = [];

                $user = Invision::fetchMember($id, 'id');

                if (is_array($user) && isset($user['id'])) {
                    $result = $user;
                }

                if (!empty($result)) {
                    $CACHE_User->set($result, 1440);
                }
            }

            static::$users[$id] = $result;
        }

        if (isset($key)) {
            return static::$users[$id][$key];
        }

        return static::$users[$id];
    }

    public static function getCustomFields(int $id, string $key = null)
    {
        if (!isset(static::$users_custom[$id])) {
            $CACHE_User = new Cache('users-' . $id . '-custom_fields');

            if (($result = $CACHE_User->get()) === false) {
                $result = [];

                $member = Invision::fetchMember($id, 'id', true);
                $customFields = Invision::getUserCustomFields($id);

                if (is_array($customFields)) {
                    $result = [
                        'location' => $customFields[Invision::CUSTOM_FIELDS['location']['group_id']]['fields'][Invision::CUSTOM_FIELDS['location']['id']]['value'] ?? null,
                        'website' => $customFields[Invision::CUSTOM_FIELDS['website']['group_id']]['fields'][Invision::CUSTOM_FIELDS['website']['id']]['value'] ?? null,
                        'twitter' => $customFields[Invision::CUSTOM_FIELDS['twitter']['group_id']]['fields'][Invision::CUSTOM_FIELDS['twitter']['id']]['value'] ?? null,
                        'bio_short' => $customFields[Invision::CUSTOM_FIELDS['bio_short']['group_id']]['fields'][Invision::CUSTOM_FIELDS['bio_short']['id']]['value'] ?? null,
                        'company' => $customFields[Invision::CUSTOM_FIELDS['company']['group_id']]['fields'][Invision::CUSTOM_FIELDS['company']['id']]['value'] ?? null,
                        'birthday' => $member->bday_month ? ($member->bday_month . '/' . $member->bday_day . ($member->bday_year ? '/' . $member->bday_year : '')) : null,
                        'gender' => $customFields[Invision::CUSTOM_FIELDS['gender']['group_id']]['fields'][Invision::CUSTOM_FIELDS['gender']['id']]['value'] ?? null,
                        'reputation' => $member->pp_reputation_points ?? null
                    ];
                }

                if (!empty($result)) {
                    $CACHE_User->set($result, 1440);
                }
            }

            static::$users_custom[$id] = $result;
        }

        if (isset($key)) {
            return static::$users_custom[$id][$key];
        }

        return static::$users_custom[$id];
    }

    public static function save(int $id, array $data): bool
    {
        $result = Invision::saveUser($id, $data);

        if (is_array($result) && isset($result['id'])) {
            $CACHE_User = new Cache('users-' . $id);
            $CACHE_User->set($result, 1440);

            static::$users[$id] = $result;

            if (isset($_SESSION['Website']['Account']) && ($_SESSION['Website']['Account']['id'] === $id)) {
                $_SESSION['Website']['Account'] = $result;
            }

            $CACHE_UserCustom = new Cache('users-' . $id . '-custom_fields');
            $CACHE_UserCustom->delete();

            static::getCustomFields($id);

            return true;
        }

        return false;
    }

    public static function getAddress(int $id, string $type, string $public_id = null)
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (!isset(static::$users[$id])) {
            static::get($id);
        }

        if (!isset(static::$users[$id]['address'])) {
            $data = [
                'user_id' => $id
            ];

            $result = $OSCOM_PDO->call('GetAddresses', $data);

            if (!empty($result)) {
                foreach ($result as $a) {
                    $atype = $a['type'];
                    $apublicid = $a['public_id'];

                    unset($a['type']);
                    unset($a['public_id']);

                    static::$users[$id]['address'][$atype][$apublicid] = $a;
                }

                $CACHE_User = new Cache('users-' . $id);
                $CACHE_User->set(static::$users[$id], 1440);
            }
        }

        if (isset(static::$users[$id]['address'][$type])) {
            if (isset($public_id)) {
                return static::$users[$id]['address'][$type][$public_id] ?? null;
            }

            return static::$users[$id]['address'][$type];
        }

        return null;
    }

    public static function hasAddress(int $id, string $type, string $public_id = null): bool
    {
        $address = static::getAddress($id, $type, $public_id);

        if (isset($public_id)) {
            return isset($address[$public_id]);
        }

        return isset($address);
    }

    public static function getAddressPublicId(int $id, string $type)
    {
        $address = static::getAddress($id, $type);

        if (isset($address)) {
            reset($address);

            return key($address);
        }

        return null;
    }

    public static function saveAddress(int $id, array $address, string $type, string $public_id = null): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'user_id' => $id,
            'type' => $type,
            'gender' => $address['gender'] ?? null,
            'company' => $address['company'] ?? null,
            'firstname' => $address['firstname'],
            'lastname' => $address['lastname'],
            'street' => $address['street'],
            'street2' => $address['street2'],
            'suburb' => null,
            'zip' => $address['zip'],
            'city' => $address['city'],
            'zone' => $address['state'] ?? null,
            'country_id' => $address['country_id'],
            'zone_id' => $address['zone_id'] ?? null,
            'telephone' => $address['telephone'] ?? null,
            'fax' => $address['fax'] ?? null,
            'public_id' => null,
            'new_public_id' => null
        ];

        if (isset($address['other'])) {
            $address['other'] = $address['other'];
        }

        if (isset($public_id)) {
            $data['public_id'] = $public_id;
        } else {
            do {
                $data['new_public_id'] = Hash::getRandomString(5);

                $Qcheck = $OSCOM_PDO->get('website_user_addresses', 'id', [
                    'public_id' => $data['new_public_id']
                ], null, 1);

                if ($Qcheck->fetch() === false) {
                    break;
                }
            } while (true);
        }

        if ($OSCOM_PDO->call('SaveAddress', $data)) {
            static::$users[$id]['address'][$type][$data['public_id'] ?? $data['new_public_id']] = [
                'gender' => $data['gender'],
                'company' => $data['company'],
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'street' => $data['street'],
                'street2' => $data['street2'],
                'suburb' => $data['suburb'],
                'zip' => $data['zip'],
                'city' => $data['city'],
                'state' => $data['zone'],
                'telephone' => $data['telephone'],
                'fax' => $data['fax'],
                'other' => $data['other'] ?? null,
                'country_iso_2' => Address::getCountryIsoCode2($data['country_id']),
                'zone_code' => ($data['zone_id'] > 0) ? Address::getZoneCode($data['zone_id']) : null
            ];

            $CACHE_User = new Cache('users-' . $id);
            $CACHE_User->set(static::$users[$id], 1440);

            return true;
        }

        return false;
    }

    public static function getNewestAmbassadors(int $limit = 12): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $CACHE_Ambassadors = new Cache('ambassadors-newest-NS-limit' . $limit);

        if (($result = $CACHE_Ambassadors->get()) === false) {
            $data = [
                'limit' => $limit
            ];

            $result = [];

            foreach ($OSCOM_PDO->call('GetNewestAmbassadors', $data) as $a) {
                $result[] = $a['user_id'];
            }

            if (($limit > 0) && (count($result) < $limit)) {
                $sponsors = [
                    182953,
                    249059,
                    102418,
                    36315,
                    1916,
                    15542,
                    184805,
                    2212,
                    74962,
                    14259,
                    211496,
                    68771
                ];

                $add = array_slice($sponsors, 0, $limit - count($result));

                $result = array_merge($result, $add);
            }

            $CACHE_Ambassadors->set($result);
        }

        return $result;
    }

    public static function clearCache(int $id)
    {
        $OSCOM_Cache = new Cache();
        $OSCOM_Cache->delete('users-' . $id);

        unset(static::$users[$id]);
    }
}
