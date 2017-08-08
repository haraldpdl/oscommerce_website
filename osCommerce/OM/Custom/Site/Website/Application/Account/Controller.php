<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Account;

  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  class Controller extends \osCommerce\OM\Core\Site\Website\ApplicationAbstract {
    protected function initialize() {
      $OSCOM_Session = Registry::get('Session');
      $OSCOM_Template = Registry::get('Template');

      if ( !$OSCOM_Session->hasStarted() ) {
        $OSCOM_Session->start();
      }

      if (!isset($_SESSION[OSCOM::getSite()]['keepAlive']) || !in_array(OSCOM::getSiteApplication(), $_SESSION[OSCOM::getSite()]['keepAlive'])) {
        $_SESSION[OSCOM::getSite()]['keepAlive'][] = OSCOM::getSiteApplication();
      }

      $OSCOM_Template->addHtmlElement('header', '<meta name="robots" content="noindex, nofollow" />');

      $OSCOM_Template->setValue('public_token', $_SESSION[OSCOM::getSite()]['public_token']);
      $OSCOM_Template->setValue('recaptcha_pass', isset($_SESSION[OSCOM::getSite()]['recaptcha_pass']));

      if ( isset($_SESSION[OSCOM::getSite()]['Account']) ) {
        $this->_page_contents = 'main.html';
        $this->_page_title = OSCOM::getDef('account_html_title');
      } else {
        $this->_page_contents = 'login.html';
        $this->_page_title = OSCOM::getDef('login_html_title');

        $OSCOM_Template->setValue('recaptcha_key_public', OSCOM::getConfig('recaptcha_key_public'));
      }
    }
  }
?>
