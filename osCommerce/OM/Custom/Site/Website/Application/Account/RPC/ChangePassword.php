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

class ChangePassword
{
    public static function execute()
    {
        $result = [
            'rpcStatus' => RPC::STATUS_ERROR
        ];

        if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $result['errorCode'] = 'not_logged_in';
        }

        if (!isset($result['errorCode'])) {
            $errors = [];

            $public_token = Sanitize::simple($_POST['public_token'] ?? null);
            $current_password = Sanitize::simple($_POST['current_password'] ?? null);
            $new_password = Sanitize::simple($_POST['new_password'] ?? null);

            if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
                $errors[] = OSCOM::getDef('error_form_protect_general');
            }

            if (empty($errors)) {
                if (strlen($current_password) < 3) {
                    $errors[] = OSCOM::getDef('change_password_current_ms_error_short');
                } elseif (strlen($current_password) > 32) {
                    $errors[] = OSCOM::getDef('change_password_current_ms_error_long');
                }

                if (strlen($new_password) < 3) {
                    $errors[] = OSCOM::getDef('change_password_new_ms_error_short');
                } elseif (strlen($new_password) > 32) {
                    $errors[] = OSCOM::getDef('change_password_new_ms_error_long');
                }
            }

            if (empty($errors)) {
                $user = Invision::canLogin($_SESSION[OSCOM::getSite()]['Account']['name'], $current_password);

                if (is_array($user) && isset($user['id'])) {
                    $new_user = Invision::saveUser($_SESSION[OSCOM::getSite()]['Account']['id'], [
                        'password' => $new_password
                    ]);

                    if (is_array($new_user) && isset($new_user['id'])) {
                        $result['rpcStatus'] = RPC::STATUS_SUCCESS;
                    } else {
                        $errors[] = OSCOM::getDef('change_password_ms_error_general');
                    }
                } else {
                    $errors[] = OSCOM::getDef('change_password_ms_error_current_password_incorrect');
                }
            }

            if (!empty($errors)) {
                $result['errors'] = $errors;
            }
        }

        echo json_encode($result);
    }
}
