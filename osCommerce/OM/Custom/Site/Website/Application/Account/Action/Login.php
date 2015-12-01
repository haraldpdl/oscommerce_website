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

  class Login {
    public static function execute(ApplicationAbstract $application) {
      if ( isset($_SESSION[OSCOM::getSite()]['Account']) ) {
        OSCOM::redirect(OSCOM::getLink(null, null, null, 'SSL'));
      }

      $application->setPageContent('login.html');
      $application->setPageTitle(OSCOM::getDef('login_html_title'));
    }
  }
?>
