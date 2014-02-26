<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Account;

  use osCommerce\OM\Core\Hash;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  class Controller extends \osCommerce\OM\Core\Site\Website\ApplicationAbstract {
    protected function initialize() {
      $OSCOM_Session = Registry::get('Session');
      $OSCOM_Template = Registry::get('Template');

      if ( !$OSCOM_Session->hasStarted() ) {
        $OSCOM_Session->start();
        Registry::get('MessageStack')->loadFromSession();
      }

      if ( !isset($_SESSION[OSCOM::getSite()]['public_token']) ) {
        $_SESSION[OSCOM::getSite()]['public_token'] = Hash::getRandomString(32);
      }

      $OSCOM_Template->addHtmlHeaderTag('<meta name="robots" content="noindex, nofollow" />');

      $OSCOM_Template->setValue('public_token', $_SESSION[OSCOM::getSite()]['public_token']);
      $OSCOM_Template->setValue('recaptcha_pass', isset($_SESSION[OSCOM::getSite()]['recaptcha_pass']));

      if ( isset($_SESSION[OSCOM::getSite()]['Account']) ) {
        $OSCOM_Template->addHtmlHeaderTag('<link href="https://fonts.googleapis.com/css?family=Allura" rel="stylesheet" type="text/css" />');
        $OSCOM_Template->addHtmlHeaderTag('<script src="public/external/jquery/jquery.boxfit.js"></script>');

        $this->_page_contents = 'main.html';
        $this->_page_title = OSCOM::getDef('account_html_title');
      } else {
        $this->_page_contents = 'login.html';
        $this->_page_title = OSCOM::getDef('login_html_title');

        $OSCOM_Template->setValue('recaptcha_key_public', OSCOM::getConfig('recaptcha_key_public'));
      }

      if ( file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/account.png') ) {
        $OSCOM_Template->setValue('highlights_image', 'images/account.png');
      } else {
        $OSCOM_Template->setValue('highlights_image', 'images/940x285.gif');
      }
    }
  }
?>
