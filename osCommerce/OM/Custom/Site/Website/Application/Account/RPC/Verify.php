<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\RPC;

use osCommerce\OM\Core\OSCOM;

use osCommerce\OM\Core\Site\Website\Invision;

use osCommerce\OM\Core\Site\RPC\Controller as RPC;

class Verify
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

            $public_token = isset($_POST['public_token']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['public_token'])) : '';
            $user_id = isset($_POST['user_id']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['user_id'])) : '';
            $key = isset($_POST['key']) ? str_replace(array("\r\n", "\n", "\r"), '', $_POST['key']) : '';

            if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
                $errors[] = OSCOM::getDef('error_form_protect_general');
            }

            if (!is_numeric($user_id) || ($user_id < 1)) {
                $errors[] = OSCOM::getDef('verify_user_id_ms_error_invalid');
            }

            if (strlen($key) !== 32) {
                $errors[] = OSCOM::getDef('verify_key_ms_error_invalid');
            }

            if (empty($errors)) {
                $verify_result = Invision::verifyUserKey($user_id, $key);

                if ($verify_result === true) {
                    $result['rpcStatus'] = RPC::STATUS_SUCCESS;
                } else {
                    if (isset($verify_result['error'])) {
                        switch ($verify_result['error']) {
                            case 'invalid_key':
                                $errors[] = OSCOM::getDef('verify_ms_error_no_match');
                                break;

                            case 'invalid_member':
                                $errors[] = OSCOM::getDef('verify_ms_error_no_member');
                                break;

                            case 'already_verified':
                                $result['errorCode'] = 'already_verified';
                                break;

                            default:
                                $errors[] = OSCOM::getDef('verify_ms_error_general');
                        }
                    } else {
                        $errors[] = OSCOM::getDef('verify_ms_error_general');
                    }
                }
            }

            if (!empty($errors)) {
                $result['errors'] = $errors;
            }
        }

        echo json_encode($result);
    }
}
