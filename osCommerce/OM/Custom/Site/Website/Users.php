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
