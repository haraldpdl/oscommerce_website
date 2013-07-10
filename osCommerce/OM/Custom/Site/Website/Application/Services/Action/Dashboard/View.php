<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Services\Action\Dashboard;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\HTML;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  use osCommerce\OM\Core\Site\Website\Partner;

  class View {
    public static function execute(ApplicationAbstract $application) {
      $OSCOM_Template = Registry::get('Template');

      if ( empty($_GET['View']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Services']['id'], $_GET['View']) ) {
        Registry::get('MessageStack')->add('services', OSCOM::getDef('dashboard_error_campaign_not_available'), 'error');

        OSCOM::redirect(OSCOM::getLink(null, 'Services', 'Dashboard'));
      }

      $application->setPageContent('info.html');
      $application->setPageTitle(OSCOM::getDef('partner_html_page_title', array(':partner_title' => Partner::get($_GET['View'], 'title'))));

      $partner = Partner::get($_GET['View']);

      $OSCOM_Template->setValue('partner', $partner);
      $OSCOM_Template->setValue('partner_header', (empty($partner['image_big']) ? HTML::image(OSCOM::getPublicSiteLink($OSCOM_Template->getValue('highlights_image')), null, 940, 285) : '<a href="' . HTML::outputProtected($partner['url']) . '" target="_blank">' . HTML::image(OSCOM::getPublicSiteLink('images/partners/' . $partner['image_big']), null, 940, 285) . '</a>'));
    }
  }
?>
