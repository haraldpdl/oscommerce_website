<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
    Cache,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Invision;

class Users
{
    const GROUP_GUEST_ID = 2;
    const GROUP_MEMBER_ID = 3;
    const GROUP_ADMIN_ID = 4;
    const GROUP_TEAM_CORE_ID = 6;
    const GROUP_AMBASSADOR_ID = 10;
    const GROUP_PARTNER_ID = 17;
    const GROUP_TEAM_COMMUNITY_ID = 19;

    protected static $_users = [];

    public static function get($id, $key = null)
    {
        $OSCOM_Cache = Registry::get('Cache');

        if (!isset(static::$_users[$id])) {
            if ($OSCOM_Cache->read('users-' . $id, 1440)) {
                $result = $OSCOM_Cache->getCache();
            } else {
                $user = Invision::fetchMember($id, 'id');

                $result = [];

                if (is_array($user) && isset($user['id'])) {
                    $result = $user;
                }

                $OSCOM_Cache->write($result);
            }

            static::$_users[$id] = $result;
        }

        if (isset($key)) {
            return static::$_users[$id][$key];
        }

        return static::$_users[$id];
    }

    public static function clearCache($id)
    {
        Cache::clear('users-' . $id);

        unset(static::$_users[$id]);
    }
}
