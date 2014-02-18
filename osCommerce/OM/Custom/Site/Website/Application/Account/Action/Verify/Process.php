<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Verify;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  use osCommerce\OM\Core\Site\Website\Invision;

  class Process {
    public static function execute(ApplicationAbstract $application) {
      $OSCOM_MessageStack = Registry::get('MessageStack');
      $OSCOM_Template = Registry::get('Template');

      $errors = [];

      $public_token = isset($_POST['public_token']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['public_token'])) : '';
      $user_id = isset($_POST['user_id']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_POST['user_id'])) : '';
      $key = isset($_POST['key']) ? preg_replace('/[^a-zA-Z0-9\-\_]/', '', $_POST['key']) : '';

      if ( $public_token !== md5($_SESSION[OSCOM::getSite()]['public_token']) ) {
        $OSCOM_MessageStack->add('account', OSCOM::getDef('error_form_protect_general'), 'error');

        return false;
      }

      if ( !is_numeric($user_id) || ($user_id < 1) ) {
        $errors[] = OSCOM::getDef('verify_user_id_ms_error_invalid');
      }

      if ( strlen($key) !== 32 ) {
        $errors[] = OSCOM::getDef('verify_key_ms_error_invalid');
      }

      if ( empty($errors) ) {
        $result = Invision::verifyUserKey($user_id, $key);

        if ( is_array($result) && isset($result['result']) && ($result['result'] === true) ) {
          $OSCOM_MessageStack->add('account', OSCOM::getDef('verify_ms_success'), 'success');

          OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Login', 'SSL'));
        } else {
          if ( isset($result['error']) && ($result['error'] == 'invalid_key') ) {
            $errors[] = OSCOM::getDef('verify_ms_error_no_match');
          } else {
            $errors[] = OSCOM::getDef('verify_ms_error_general');
          }
        }
      }

      foreach ( $errors as $e ) {
        $OSCOM_MessageStack->add('account', $e, 'error');
      }
    }
  }
?>
