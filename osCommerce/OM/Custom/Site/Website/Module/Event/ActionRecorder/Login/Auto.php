<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Event\ActionRecorder\Login;

use osCommerce\OM\Core\ActionRecorder;

use osCommerce\OM\Core\Site\Website\Invision;

class Auto
{
    public static function execute($user)
    {
        if (is_array($user) && isset($user['id'])) {
            if (($user['verified'] === true) && ($user['banned'] === false)) {
                ActionRecorder::save([
                    'action' => 'auto_login',
                    'success' => 1,
                    'user_id' => $user['id']
                ]);
            } else {
                if ($user['verified'] === false) {
                    $ar_result = 'not_verified';
                } else {
                    $ar_result = 'banned';
                }

                ActionRecorder::save([
                    'action' => 'auto_login',
                    'success' => 0,
                    'user_id' => $user['id'],
                    'result' => $ar_result
                ]);
            }
        } else {
            $user = Invision::fetchMember($_COOKIE[Invision::COOKIE_MEMBER_ID], 'id');

            ActionRecorder::save([
                'action' => 'auto_login',
                'success' => 0,
                'identifier' => (is_array($user) && isset($user['id']) && ($user['id'] > 0)) ? null : $_COOKIE[Invision::COOKIE_MEMBER_ID],
                'user_id' => (is_array(§user) && isset($user['id']) && ($user['id'] > 0)) ? $user['id'] : null
            ]);
        }
    }
}
