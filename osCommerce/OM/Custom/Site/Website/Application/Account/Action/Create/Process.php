<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Create;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\HttpRequest;
  use osCommerce\OM\Core\Mail;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  use osCommerce\OM\Core\Site\Website\Invision;

  class Process {
    public static function execute(ApplicationAbstract $application) {
      $OSCOM_MessageStack = Registry::get('MessageStack');
      $OSCOM_Template = Registry::get('Template');

      $errors = [];

      $public_token = isset($_POST['public_token']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['public_token'])) : '';
      $username = isset($_POST['username']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['username'])) : '';
      $email = isset($_POST['email']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['email'])) : '';
      $password = isset($_POST['password']) ? str_replace(array("\r\n", "\n", "\r"), '', $_POST['password']) : '';
      $agree_tos = isset($_POST['agree_tos']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['agree_tos'])) : '';

      if ( $public_token !== md5($_SESSION[OSCOM::getSite()]['public_token']) ) {
        $OSCOM_MessageStack->add('account', OSCOM::getDef('error_form_protect_general'), 'error');

        return false;
      }

      if ( strlen($username) < 3 ) {
        $errors[] = OSCOM::getDef('create_username_ms_error_short');
      } elseif ( strlen($username) > 26 ) {
        $errors[] = OSCOM::getDef('create_username_ms_error_long');
      } elseif ( stripos($username, 'oscommerce') !== false ) {
        $errors[] = OSCOM::getDef('create_username_ms_error_oscommerce');
      }

      if ( empty($email) ) {
        $errors[] = OSCOM::getDef('create_email_address_ms_error_required');
      } elseif ( !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
        $errors[] = OSCOM::getDef('create_email_address_ms_error_invalid');
      }

      if ( strlen($password) < 3 ) {
        $errors[] = OSCOM::getDef('create_password_ms_error_short');
      } elseif ( strlen($password) > 32 ) {
        $errors[] = OSCOM::getDef('create_password_ms_error_long');
      }

      if ( $agree_tos != '1' ) {
        $errors[] = OSCOM::getDef('create_tos_agree_ms_error_required');
      }

      if ( !isset($_SESSION[OSCOM::getSite()]['recaptcha_pass']) ) {
        $recaptcha_error = true;

        if ( isset($_POST['recaptcha_challenge_field']) && !empty($_POST['recaptcha_challenge_field']) && isset($_POST['recaptcha_response_field']) && !empty($_POST['recaptcha_response_field']) ) {
          $params = [ 'privatekey' => OSCOM::getConfig('recaptcha_key_private'),
                      'remoteip' => OSCOM::getIPAddress(),
                      'challenge' => $_POST['recaptcha_challenge_field'],
                      'response' => $_POST['recaptcha_response_field'] ];

          $post_string = '';

          foreach ( $params as $key => $value ) {
            $post_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
          }

          $post_string = substr($post_string, 0, -1);

          $response = HttpRequest::getResponse( ['url' => 'http://www.google.com/recaptcha/api/verify',
                                                 'parameters' => $post_string] );

          if ( !empty($response) ) {
            $result = explode("\n", $response, 2);

            if ( isset($result[0]) && ($result[0] == 'true') ) {
              $recaptcha_error = false;

              $_SESSION[OSCOM::getSite()]['recaptcha_pass'] = true;
              $OSCOM_Template->setValue('recaptcha_pass', true, true);
            }
          }
        }

        if ( $recaptcha_error === true ) {
          $errors[] = OSCOM::getDef('create_security_check_ms_error_invalid');
        }
      }

      if ( isset($_SESSION[OSCOM::getSite()]['recaptcha_pass']) ) {
        if ( !isset($_SESSION[OSCOM::getSite()]['sfs_pass']) ) {
          $sfs_error = true;

          $params = [ 'ip' => OSCOM::getIPAddress(),
                      'email' => $email,
                      'f' => 'json' ];

          $get_string = '';

          foreach ( $params as $key => $value ) {
            $get_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
          }

          $get_string = substr($get_string, 0, -1);

          $response = HttpRequest::getResponse( ['url' => 'http://www.stopforumspam.com/api?' . $get_string,
                                                 'method' => 'get'] );

          if ( !empty($response) ) {
            $result = @json_decode($response, true);

            if ( is_array($result) ) {
              if ( isset($result['success']) && ($result['success'] === 1) && isset($result['email']['appears']) && ($result['email']['appears'] === 0) && isset($result['ip']['appears']) && ($result['ip']['appears'] === 0) ) {
                $sfs_error = false;

                $_SESSION[OSCOM::getSite()]['sfs_pass'] = true;
              } else {
                $_SESSION[OSCOM::getSite()]['sfs_pass'] = false;
              }
            }
          }
        }

        if ( (isset($sfs_error) && ($sfs_error === true)) || (isset($_SESSION[OSCOM::getSite()]['sfs_pass']) && ($_SESSION[OSCOM::getSite()]['sfs_pass'] === false)) ) {
          $errors[] = OSCOM::getDef('create_security_check_ms_error_spammer');
        }
      }

      if ( isset($_SESSION[OSCOM::getSite()]['recaptcha_pass']) && isset($_SESSION[OSCOM::getSite()]['sfs_pass']) && ($_SESSION[OSCOM::getSite()]['sfs_pass'] === true) ) {
        if ( Invision::checkMemberExists($username, 'username') ) {
          $errors[] = OSCOM::getDef('create_username_ms_error_exists');
        }

        if ( Invision::checkMemberExists($email, 'email') ) {
          $errors[] = OSCOM::getDef('create_email_address_ms_error_exists');
        }

        if ( empty($errors) ) {
          $result = Invision::createUser($username, $email, $password);

          if ( is_array($result) && isset($result['result']) && ($result['result'] === true) && isset($result['member']['member_id']) && is_numeric($result['member']['member_id']) && ($result['member']['member_id'] > 0) && isset($result['validate_key']) && !empty($result['validate_key']) ) {
            $result['member']['validate_key'] = $result['validate_key'];

            $OSCOM_Template->setValue('new_member_reg', $result['member']);

            $email_txt_file = $OSCOM_Template->getPageContentsFile('email_new_user_verify.txt');
            $email_txt = file_exists($email_txt_file) ? $OSCOM_Template->parseContent(file_get_contents($email_txt_file)) : null;

            $email_html_file = $OSCOM_Template->getPageContentsFile('email_new_user_verify.html');
            $email_html = file_exists($email_html_file) ? $OSCOM_Template->parseContent(file_get_contents($email_html_file)) : null;

            if ( !empty($email_txt) || !empty($email_html) ) {
              $OSCOM_Mail = new Mail($result['member']['name'], $result['member']['email'], 'osCommerce', 'noreply@oscommerce.com', OSCOM::getDef('create_email_new_account_subject'));

              if ( !empty($email_txt) ) {
                $OSCOM_Mail->setBodyPlain($email_txt);
              }

              if ( !empty($email_html) ) {
                $OSCOM_Mail->setBodyHTML($email_html);
              }

              $OSCOM_Mail->send();
            }

            unset($_SESSION[OSCOM::getSite()]['recaptcha_pass']);
            unset($_SESSION[OSCOM::getSite()]['sfs_pass']);

            $OSCOM_MessageStack->add('account', OSCOM::getDef('create_ms_success'), 'success');

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Verify', 'SSL'));
          } else {
            $errors[] = OSCOM::getDef('create_ms_error_general');
          }
        }
      }

      foreach ( $errors as $e ) {
        $OSCOM_MessageStack->add('account', $e, 'error');
      }
    }
  }
?>