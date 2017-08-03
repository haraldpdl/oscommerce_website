<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\RPC;

use osCommerce\OM\Core\{
    Events,
    Mail,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\{
    Invision,
    Users
};

use osCommerce\OM\Core\Site\RPC\Controller as RPC;

class Login
{
    public static function execute()
    {
        $OSCOM_Session = Registry::get('Session');
        $OSCOM_Template = Registry::get('Template');

        $result = [
            'rpcStatus' => RPC::STATUS_ERROR
        ];

        if (isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $result['errorCode'] = 'already_logged_in';
        }

        if (!isset($result['errorCode'])) {
            $errors = [];

            $public_token = isset($_POST['public_token']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['public_token'])) : '';
            $username = isset($_POST['username']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['username'])) : '';
            $password = isset($_POST['password']) ? str_replace(array("\r\n", "\n", "\r"), '', $_POST['password']) : '';
            $sendVerification = isset($_POST['sendVerification']) && ($_POST['sendVerification'] == '1') ? true : false;
            $addressType = isset($_POST['addressType']) && !empty($_POST['addressType']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['addressType'])) : '';

            if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
                $errors[] = OSCOM::getDef('error_form_protect_general');
            }

            if (empty($errors)) {
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
            }

            if (empty($errors)) {
                $user = Invision::canLogin($username, $password);

                Events::fire('login-before', $user);

                if (is_array($user) && isset($user['id'])) {
                    if (($user['verified'] === true) && ($user['banned'] === false)) {
                        $result['rpcStatus'] = RPC::STATUS_SUCCESS;

                        $_SESSION[OSCOM::getSite()]['Account'] = $user;

                        $OSCOM_Session->recreate();

                        if (isset($_POST['remember_me']) && ($_POST['remember_me'] == '1')) {
                            Invision::setCookies($user['id'], $user['login_key']);
                        } else {
                            Invision::killCookies();
                        }

                        Events::fire('login-after');

                        $redirect_url = OSCOM::getLink(null, null, null, 'SSL');

                        if (isset($_SESSION['login_redirect'])) {
                            if (isset($_SESSION['login_redirect']['url'])) {
                                $redirect_url = $_SESSION['login_redirect']['url'];
                            }

                            unset($_SESSION['login_redirect']);
                        }

                        $result['redirect'] = $redirect_url;
                        $result['name'] = $user['name'];

                        if (!empty($addressType) && Users::hasAddress($user['id'], $addressType)) {
                            $address = Users::getAddress($user['id'], $addressType);
                            $address = reset($address);

                            $result['address'] = $address;
                        }
                    } elseif ($user['verified'] === false) {
                        $result['errorCode'] = 'not_verified';
                        $result['verificationSent'] = false;

                        if ($sendVerification === true) {
                            if (isset($user['val_newreg_id']) && !empty($user['val_newreg_id'])) {
                                $OSCOM_Template->setValue('new_member_reg', $user);

                                $email_txt_file = $OSCOM_Template->getPageContentsFile('email_new_user_verify.txt');
                                $email_txt = file_exists($email_txt_file) ? $OSCOM_Template->parseContent(file_get_contents($email_txt_file)) : null;

                                $email_html_file = $OSCOM_Template->getPageContentsFile('email_new_user_verify.html');
                                $email_html = file_exists($email_html_file) ? $OSCOM_Template->parseContent(file_get_contents($email_html_file)) : null;

                                if (!empty($email_txt) || !empty($email_html)) {
                                    $OSCOM_Mail = new Mail($user['name'], $user['email'], 'osCommerce', 'noreply@oscommerce.com', OSCOM::getDef('create_email_new_account_subject'));

                                    if (!empty($email_txt)) {
                                        $OSCOM_Mail->setBodyPlain($email_txt);
                                    }

                                    if (!empty($email_html)) {
                                        $OSCOM_Mail->setBodyHTML($email_html);
                                    }

                                    $OSCOM_Mail->send();

                                    $result['email'] = $user['email'];
                                    $result['verificationSent'] = true;
                                }
                            }
                        }
                    } else {
                        $errors[] = OSCOM::getDef('login_ms_error_banned');

                        $result['errorCode'] = 'banned';
                    }
                } else {
                    $errors[] = OSCOM::getDef('login_ms_error_general');
                }
            }

            if (!empty($errors)) {
                $result['errors'] = $errors;
            }
        }

        echo json_encode($result);
    }
}