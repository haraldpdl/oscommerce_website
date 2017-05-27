<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Verify;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Invision;

class Process
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');

        $errors = [];

        if (isset($_GET['id']) && isset($_GET['key'])) {
            $user_id = trim(str_replace(array("\r\n", "\n", "\r"), '', $_GET['id']));
            $key = preg_replace('/[^a-zA-Z0-9\-\_]/', '', $_GET['key']);
        } else {
            $public_token = isset($_POST['public_token']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['public_token'])) : '';
            $user_id = isset($_POST['user_id']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['user_id'])) : '';
            $key = isset($_POST['key']) ? preg_replace('/[^a-zA-Z0-9\-\_]/', '', $_POST['key']) : '';

            if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
                $OSCOM_MessageStack->add('account', OSCOM::getDef('error_form_protect_general'), 'error');

                return false;
            }
        }

        if (!is_numeric($user_id) || ($user_id < 1)) {
            $errors[] = OSCOM::getDef('verify_user_id_ms_error_invalid');
        }

        if (strlen($key) !== 32) {
            $errors[] = OSCOM::getDef('verify_key_ms_error_invalid');
        }

        if (empty($errors)) {
            $result = Invision::verifyUserKey($user_id, $key);

            if ($result === true) {
                $OSCOM_MessageStack->add('account', OSCOM::getDef('verify_ms_success'), 'success');

                OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Login', 'SSL'));
            } else {
                if (isset($result['error'])) {
                    switch ($result['error']) {
                        case 'invalid_key':
                            $errors[] = OSCOM::getDef('verify_ms_error_no_match');
                            break;

                        case 'invalid_member':
                            $errors[] = OSCOM::getDef('verify_ms_error_no_member');
                            break;

                        case 'already_verified':
                            $OSCOM_MessageStack->add('account', OSCOM::getDef('verify_ms_error_already_verified'), 'warning');

                            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Login', 'SSL'));
                            break;

                        default:
                            $errors[] = OSCOM::getDef('verify_ms_error_general');
                    }
                } else {
                    $errors[] = OSCOM::getDef('verify_ms_error_general');
                }
            }
        }

        foreach ($errors as $e) {
            $OSCOM_MessageStack->add('account', $e, 'error');
        }
    }
}
