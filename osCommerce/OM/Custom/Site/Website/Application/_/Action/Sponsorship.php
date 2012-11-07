<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2012 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\_\Action;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  class Sponsorship {
    public static function execute(ApplicationAbstract $application) {
      $OSCOM_Template = Registry::get('Template');

      $application->setPageContent('sponsorship.html');
      $application->setPageTitle(OSCOM::getDef('cs_html_page_title'));

      if ( file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/highlights_sponsorship.jpg') ) {
        $OSCOM_Template->setValue('highlights_image', 'images/highlights_sponsorship.jpg');
      } else {
        $OSCOM_Template->setValue('highlights_image', 'images/940x285.gif');
      }
    }
  }
?>
