<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Login;

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
        $OSCOM_Session = Registry::get('Session');

        $errors = [];

        $public_token = isset($_POST['public_token']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['public_token'])) : '';
        $username = isset($_POST['username']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['username'])) : '';
        $password = isset($_POST['password']) ? str_replace(array("\r\n", "\n", "\r"), '', $_POST['password']) : '';

        if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
            $OSCOM_MessageStack->add('account', OSCOM::getDef('error_form_protect_general'), 'error');

            return false;
        }

        if (strlen($username) < 3) {
            $errors[] = OSCOM::getDef('login_username_ms_error_short');
        } elseif (strlen($username) > 26) {
            $errors[] = OSCOM::getDef('login_username_ms_error_long');
        }

        if (strlen($password) < 3) {
            $errors[] = OSCOM::getDef('login_password_ms_error_short');
        } elseif (strlen($password) > 32) {
            $errors[] = OSCOM::getDef('login_password_ms_error_long');
        }

        if (empty($errors)) {
            $user = Invision::canLogin($username, $password);

            if (is_array($user) && isset($user['id'])) {
                if (($user['verified'] === true) && ($user['banned'] === false)) {
                    $_SESSION[OSCOM::getSite()]['Account'] = $user;

                    $OSCOM_Session->recreate();

                    if (isset($_POST['remember_me']) && ($_POST['remember_me'] == '1')) {
                        OSCOM::setCookie('member_id', $user['id'], time() + 31536000, null, null, false, true);
                        OSCOM::setCookie('pass_hash', $user['login_key'], time() + 604800, null, null, false, true);
                    } else {
                        OSCOM::setCookie('member_id', '', time() - 31536000, null, null, false, true);
                        OSCOM::setCookie('pass_hash', '', time() - 31536000, null, null, false, true);
                    }

                    $redirect_url = OSCOM::getLink(null, null, null, 'SSL');

                    if (isset($_SESSION['login_redirect'])) {
                        if (isset($_SESSION['login_redirect']['url'])) {
                            $redirect_url = $_SESSION['login_redirect']['url'];
                        }

                        unset($_SESSION['login_redirect']);
                    }

                    OSCOM::redirect($redirect_url);
                } elseif ($user['verified'] === false) {
                    $errors[] = OSCOM::getDef('login_ms_error_not_verified');
                } else {
                    $errors[] = OSCOM::getDef('login_ms_error_banned');
                }
            } else {
              $errors[] = OSCOM::getDef('login_ms_error_general');
            }
        }

        foreach ($errors as $e) {
            $OSCOM_MessageStack->add('account', $e, 'error');
        }
    }
}
