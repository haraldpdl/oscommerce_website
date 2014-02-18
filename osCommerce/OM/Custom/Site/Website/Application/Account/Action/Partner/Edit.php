<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  use osCommerce\OM\Core\Site\Website\Partner;

  class Edit {
    public static function execute(ApplicationAbstract $application) {
      $OSCOM_Template = Registry::get('Template');

      if ( empty($_GET['Edit']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Edit']) ) {
        Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_error_campaign_not_available'), 'error');

        OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner', 'SSL'));
      }

      $OSCOM_Template->setValue('partner_campaign', Partner::getCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Edit']));

      $application->setPageContent('partner_edit.html');
    }
  }
?>
