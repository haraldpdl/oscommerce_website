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

  class Verify {
    public static function execute(ApplicationAbstract $application) {
      $OSCOM_Template = Registry::get('Template');

      if ( isset($_SESSION[OSCOM::getSite()]['Account']) ) {
        OSCOM::redirect(OSCOM::getLink(null, 'Account', null, 'SSL'));
      }

      $application->setPageContent('verify.html');
      $application->setPageTitle(OSCOM::getDef('verify_html_title'));
    }
  }
?>
