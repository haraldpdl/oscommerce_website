<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Account\Action;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  class OkILoveYouByeBye {
    public static function execute(ApplicationAbstract $application) {
      $OSCOM_MessageStack = Registry::get('MessageStack');
      $OSCOM_Session = Registry::get('Session');

      unset($_SESSION[OSCOM::getSite()]['Account']);

      $OSCOM_Session->recreate();

      if ( isset($_COOKIE['member_id']) && isset($_COOKIE['pass_hash']) ) {
        OSCOM::setCookie('member_id', '', time() - 31536000, null, null, false, true);
        OSCOM::setCookie('pass_hash', '', time() - 31536000, null, null, false, true);
      }

      $OSCOM_MessageStack->add('account', OSCOM::getDef('logout_ms_success'), 'success');

      OSCOM::redirect(OSCOM::getLink(null, null, 'Login', 'SSL'));
    }
  }
?>