<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\RPC;

use osCommerce\OM\Core\{
    Mail,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Invision;

use osCommerce\OM\Core\Site\RPC\Controller as RPC;

class ResetPasswordRequest
{
    public static function execute()
    {
        $OSCOM_Template = Registry::get('Template');

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
            $login_key = isset($_POST['login_key']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['login_key'])) : '';

            if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
                $errors[] = OSCOM::getDef('error_form_protect_general');
            }

            if (empty($errors)) {
                if (strlen($login_key) < 3) {
                    $errors[] = OSCOM::getDef('reset_password_login_key_ms_error_short');
                }
            }

            if (empty($errors)) {
                if (filter_var($login_key, FILTER_VALIDATE_EMAIL) && Invision::checkMemberExists($login_key, 'email')) {
                    $user = Invision::fetchMember($login_key, 'email');
                }

                if ($user === false) {
                    if (Invision::checkMemberExists($login_key, 'username')) {
                        $user = Invision::fetchMember($login_key, 'username');
                    }
                }

                if ($user === false) {
                    $errors[] = OSCOM::getDef('reset_password_login_key_ms_error_unknown');
                }
            }

            if (empty($errors)) {
                $userResetKey = Invision::getPasswordResetKey($user['id'], true);

                if (is_array($userResetKey) && isset($userResetKey['key']) && !empty($userResetKey['key'])) {
                    $result['rpcStatus'] = RPC::STATUS_SUCCESS;
                    $result['emailSent'] = false;

                    $OSCOM_Template->setValue('reset_password_user', $userResetKey);

                    if ($userResetKey['send_email'] === true) {
                        $email_txt_file = $OSCOM_Template->getPageContentsFile('email_user_reset_password.txt');
                        $email_txt = file_exists($email_txt_file) ? $OSCOM_Template->parseContent(file_get_contents($email_txt_file)) : null;

                        $email_html_file = $OSCOM_Template->getPageContentsFile('email_user_reset_password.html');
                        $email_html = file_exists($email_html_file) ? $OSCOM_Template->parseContent(file_get_contents($email_html_file)) : null;

                        if (!empty($email_txt) || !empty($email_html)) {
                            $OSCOM_Mail = new Mail($userResetKey['name'], $userResetKey['email'], 'osCommerce', 'noreply@oscommerce.com', OSCOM::getDef('reset_password_email_subject'));

                            if (!empty($email_txt)) {
                                $OSCOM_Mail->setBodyPlain($email_txt);
                            }

                            if (!empty($email_html)) {
                                $OSCOM_Mail->setBodyHTML($email_html);
                            }

                            $OSCOM_Mail->send();

                            $result['emailSent'] = true;
                        }
                    }
                } else {
                    if (isset($userResetKey['error'])) {
                        switch ($userResetKey['error']) {
                            case 'invalid_member':
                                $errors[] = OSCOM::getDef('reset_password_login_key_ms_error_unknown');
                                break;

                            default:
                                $errors[] = OSCOM::getDef('reset_password_ms_error_general');
                        }
                    } else {
                        $errors[] = OSCOM::getDef('reset_password_ms_error_general');
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
