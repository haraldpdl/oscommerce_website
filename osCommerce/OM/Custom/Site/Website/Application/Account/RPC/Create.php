<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\RPC;

use osCommerce\OM\Core\{
    HttpRequest,
    Mail,
    OSCOM,
    Registry,
    Sanitize
};

use osCommerce\OM\Core\Site\Website\Invision;

use osCommerce\OM\Core\Site\RPC\Controller as RPC;

class Create
{
    public static function execute()
    {
        $OSCOM_Template = Registry::get('Template');

        $result = [
            'rpcStatus' => RPC::STATUS_ERROR,
            'resetGSecurityCheck' => false
        ];

        if (isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $result['errorCode'] = 'already_logged_in';
        }

        if (!isset($result['errorCode'])) {
            $errors = [];

            $public_token = Sanitize::simple($_POST['public_token'] ?? null);
            $username = Sanitize::simple($_POST['username'] ?? null);
            $email = Sanitize::simple($_POST['email'] ?? null);
            $password = Sanitize::simple($_POST['password'] ?? null);
            $grSecurityCheck = $_POST['gr_security_check'] ?? '';
            $sendVerification = isset($_POST['sendVerification']) && ($_POST['sendVerification'] == '1') ? true : false;

            if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
                $errors[] = OSCOM::getDef('error_form_protect_general');
            }

            if (empty($errors)) {
                if (strlen($username) < 3) {
                    $errors[] = OSCOM::getDef('create_username_ms_error_short');
                } elseif (strlen($username) > 26) {
                    $errors[] = OSCOM::getDef('create_username_ms_error_long');
                } elseif (stripos($username, 'oscommerce') !== false) {
                    $errors[] = OSCOM::getDef('create_username_ms_error_oscommerce');
                } elseif (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = OSCOM::getDef('create_username_ms_error_email');
                }

                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = OSCOM::getDef('create_email_address_ms_error_invalid');
                }

                if (strlen($password) < 3) {
                    $errors[] = OSCOM::getDef('login_password_ms_error_short');
                } elseif (strlen($password) > 32) {
                    $errors[] = OSCOM::getDef('login_password_ms_error_long');
                }
            }

            if (empty($errors)) {
                if (Invision::isFilterBanned(OSCOM::getIPAddress(), 'ip')) {
                    $errors[] = OSCOM::getDef('create_ip_address_ms_error_filter_banned');
                }
            }

            if (empty($errors)) {
                if (Invision::isFilterBanned($username, 'name')) {
                    $errors[] = OSCOM::getDef('create_username_ms_error_filter_banned');
                }
            }

            if (empty($errors)) {
                if (Invision::isFilterBanned($email, 'email')) {
                    $errors[] = OSCOM::getDef('create_email_ms_error_filter_banned');
                }
            }

            if (empty($errors)) {
                $recaptcha_error = true;

                if (!empty($grSecurityCheck)) {
                    $params = [
                        'secret' => OSCOM::getConfig('recaptcha_key_private'),
                        'remoteip' => OSCOM::getIPAddress(),
                        'response' => $grSecurityCheck
                    ];

                    $post_string = '';

                    foreach ($params as $key => $value) {
                        $post_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
                    }

                    $post_string = substr($post_string, 0, -1);

                    $response = HttpRequest::getResponse([
                        'url' => 'https://www.google.com/recaptcha/api/siteverify',
                        'parameters' => $post_string
                    ]);

                    if (!empty($response)) {
                        $gr_result = json_decode($response, true);

                        if (is_array($gr_result) && isset($gr_result['success']) && ($gr_result['success'] === true)) {
                            $recaptcha_error = false;
                        } else {
                            trigger_error('User account creation failed (' . $username . ' [' . $email . ']); Recaptcha: ' . $response);
                        }
                    }

                    $result['resetGSecurityCheck'] = true;
                }

                if ($recaptcha_error === true) {
                    $errors[] = OSCOM::getDef('create_security_check_ms_error_invalid');

                    $result['resetGSecurityCheck'] = true;
                }
            }

            if (empty($errors)) {
                $sfs_error = true;

                $params = [
                    'ip' => OSCOM::getIPAddress(),
                    'email' => $email,
                    'f' => 'json'
                ];

                $params_string = '';

                foreach ($params as $key => $value) {
                    $params_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
                }

                $params_string = substr($params_string, 0, -1);

                $response = HttpRequest::getResponse([
                    'url' => 'https://www.stopforumspam.com/api',
                    'parameters' => $params_string
                ]);

                if (!empty($response)) {
                    $sfs_result = json_decode($response, true);

                    if (is_array($sfs_result) && isset($sfs_result['success']) && ($sfs_result['success'] === 1)) {
                        $score = 0;

                        if (isset($sfs_result['email']['confidence'])) {
                            $score += $sfs_result['email']['confidence'];
                        }

                        if (isset($sfs_result['ip']['confidence'])) {
                            $score += $sfs_result['ip']['confidence'];
                        }

                        if ($score < 40) {
                            $sfs_error = false;
                        }
                    }

                    if ($sfs_error === true) {
                        trigger_error('User account creation failed (' . $username . ' [' . $email . ']); SFS: ' . $response);
                    }
                }

                if ($sfs_error === true) {
                    $errors[] = OSCOM::getDef('create_security_check_ms_error_spammer');
                }
            }

            if (empty($errors)) {
                if (Invision::checkMemberExists($username, 'username')) {
                    $errors[] = OSCOM::getDef('create_username_ms_error_exists');
                }

                if (Invision::checkMemberExists($email, 'email')) {
                    $errors[] = OSCOM::getDef('create_email_address_ms_error_exists');
                }
            }

            if (empty($errors)) {
                $user = Invision::createUser($username, $email, $password);

                if (is_array($user) && isset($user['id']) && is_numeric($user['id']) && ($user['id'] > 0) && isset($user['val_newreg_id']) && !empty($user['val_newreg_id'])) {
                    $result['rpcStatus'] = RPC::STATUS_SUCCESS;
                    $result['verificationSent'] = false;

                    if ($sendVerification === true) {
                        if (isset($user['val_newreg_id']) && !empty($user['val_newreg_id'])) {
                            $OSCOM_Template->setValue('new_member_reg', $user);

                            $email_txt_file = $OSCOM_Template->getPageContentsFile('email_new_user_verify.txt');
                            $email_txt = file_exists($email_txt_file) ? $OSCOM_Template->parseContent(file_get_contents($email_txt_file)) : null;

                            $email_html_file = $OSCOM_Template->getPageContentsFile('email_new_user_verify.html');
                            $email_html = file_exists($email_html_file) ? $OSCOM_Template->parseContent(file_get_contents($email_html_file)) : null;

                            if (!empty($email_txt) || !empty($email_html)) {
                                $OSCOM_Mail = new Mail($user['email'], $user['name'], 'noreply@oscommerce.com', 'osCommerce', OSCOM::getDef('create_email_new_account_subject'));

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
                    $errors[] = OSCOM::getDef('create_ms_error_general');
                }
            }

            if (!empty($errors)) {
                $result['errors'] = $errors;
            }
        }

        echo json_encode($result);
    }
}
