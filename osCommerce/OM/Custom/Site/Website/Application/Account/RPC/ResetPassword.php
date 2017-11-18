<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\RPC;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
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

            $public_token = isset($_POST['public_token']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['public_token'])) : '';
            $key = isset($_POST['key']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['key'])) : '';
            $user_id = isset($_POST['id']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['id'])) : '';
            $password = isset($_POST['password']) ? str_replace(array("\r\n", "\n", "\r"), '', $_POST['password']) : '';

            if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
                $errors[] = OSCOM::getDef('error_form_protect_general');
            }

            if (empty($errors)) {
                if ((strlen($key) !== 32) || !is_numeric($user_id) || ($user_id < 1)) {
                    $errors[] = OSCOM::getDef('reset_password_key_ms_error_not_found');
                }
            }

            if (empty($errors)) {
                if (strlen($password) < 3) {
                    $errors[] = OSCOM::getDef('reset_password_new_ms_error_short');
                } elseif (strlen($password) > 32) {
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
