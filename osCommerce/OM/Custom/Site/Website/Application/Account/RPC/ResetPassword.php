<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\RPC;

use osCommerce\OM\Core\{
    OSCOM,
    Sanitize
};

use osCommerce\OM\Core\Site\Website\Invision;

use osCommerce\OM\Core\Site\RPC\Controller as RPC;

class ResetPassword
{
    public static function execute()
    {
        $result = [
            'rpcStatus' => RPC::STATUS_ERROR
        ];

        if (isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $result['errorCode'] = 'already_logged_in';
        }

        if (!isset($result['errorCode'])) {
            $errors = [];

            $user = false;

            $public_token = Sanitize::simple($_POST['public_token'] ?? null);
            $key = Sanitize::simple($_POST['key'] ?? null);
            $user_id = Sanitize::simple($_POST['id'] ?? null);
            $password = Sanitize::password($_POST['password'] ?? null);

            if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
                $errors[] = OSCOM::getDef('error_form_protect_general');
            }

            if (empty($errors)) {
                if ((mb_strlen($key) !== 32) || !is_numeric($user_id) || ($user_id < 1)) {
                    $errors[] = OSCOM::getDef('reset_password_key_ms_error_not_found');
                }
            }

            if (empty($errors)) {
                if (mb_strlen($password) < 3) {
                    $errors[] = OSCOM::getDef('reset_password_new_ms_error_short');
                } elseif (mb_strlen($password) > 32) {
                    $errors[] = OSCOM::getDef('reset_password_new_ms_error_long');
                }
            }

            if (empty($errors)) {
                $user = Invision::getPasswordResetKey($user_id);

                if (is_array($user) && isset($user['key']) && ($key == $user['key'])) {
                    $data = [
                        'password' => $password
                    ];

                    if (Invision::saveUser($user['id'], $data) !== false) {
                        Invision::deletePasswordResetKey($user['id']);

                        $result['rpcStatus'] = RPC::STATUS_SUCCESS;
                    } else {
                        $errors[] = OSCOM::getDef('reset_password_new_ms_error_general');
                    }
                } else {
                    $errors[] = OSCOM::getDef('reset_password_new_ms_error_general');
                }
            }

            if (!empty($errors)) {
                $result['errors'] = $errors;
            }
        }

        echo json_encode($result);
    }
}
