<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Services\Action;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;
  use osCommerce\OM\Core\Session;

  class Login {
    public static function execute(ApplicationAbstract $application) {
      define('SERVICE_SESSION_FORCE_COOKIE_USAGE', 1);
      Registry::set('Session', Session::load());

      $OSCOM_Session = Registry::get('Session');
      $OSCOM_Session->start();

      Registry::get('MessageStack')->loadFromSession();

      $application->setPageContent('login.html');
      $application->setPageTitle(OSCOM::getDef('dashboard_html_title'));
    }
  }
?>
