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

class Process
{
    public static function execute($user)
    {
        if (is_array($user) && isset($user['id'])) {
            if (($user['verified'] === true) && ($user['banned'] === false)) {
                ActionRecorder::save([
                    'action' => 'login',
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
                    'action' => 'login',
                    'success' => 0,
                    'user_id' => $user['id'],
                    'result' => $ar_result
                ]);
            }
        } else {
            $username = trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['username']));

            $user = Invision::fetchMember($username, 'username');

            ActionRecorder::save([
                'action' => 'login',
                'success' => 0,
                'identifier' => (isset($user['member_id']) && ($user['member_id'] > 0)) ? null : $username,
                'user_id' => (isset($user['member_id']) && ($user['member_id'] > 0)) ? $user['member_id'] : null
            ]);
        }
    }
}
