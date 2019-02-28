<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
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
            $username = Sanitize::simple($_POST['username']);

            $user = false;

            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $user = Invision::fetchMember($username, 'email');
            }

            if ($user === false) {
                $user = Invision::fetchMember($username, 'username');
            }

            ActionRecorder::save([
                'action' => 'login',
                'success' => 0,
                'identifier' => (is_array($user) && isset($user['id']) && ($user['id'] > 0)) ? null : $username,
                'user_id' => (is_array($user) && isset($user['id']) && ($user['id'] > 0)) ? $user['id'] : null
            ]);
        }
    }
}
