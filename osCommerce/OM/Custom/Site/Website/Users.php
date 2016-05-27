<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
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

                if (is_array($user) && isset($user['member_id'])) {
                    $result = [
                        'id' => (int)$user['member_id'],
                        'name' => $user['members_display_name'],
                        'full_name' => $user['field_1'],
                        'email' => $user['email'],
                        'group_id' => (int)$user['member_group_id'],
                        'admin' => (int)$user['member_group_id'] === 4,
                        'team' => in_array((int)$user['member_group_id'], [6, 19]),
                        'verified' => (int)$user['member_group_id'] !== 1,
                        'banned' => in_array((int)$user['member_group_id'], [2, 5]) || (!empty($user['temp_ban']) && ($user['temp_ban'] != '0')),
                        'restricted_post' => (!empty($user['restrict_post']) && ($user['restrict_post'] != '0')) || (!empty($user['mod_posts']) && ($user['mod_posts'] != '0')),
                        'joined' => $user['joined'],
                        'posts' => $user['posts'],
                        'main_photo' => $user['pp_main_photo'],
                        'avatar_type' => $user['avatar_type'],
                        'avatar_location' => $user['avatar_location'],
                        'avatar_size' => $user['avatar_size']];
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
}
