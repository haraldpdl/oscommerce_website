<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\HTML;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  use osCommerce\OM\Core\Site\Website\Partner;

  class View {
    public static function execute(ApplicationAbstract $application) {
      $OSCOM_Template = Registry::get('Template');

      if ( empty($_GET['View']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['View']) ) {
        Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_error_campaign_not_available'), 'error');

        OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner', 'SSL'));
      }

      $partner = Partner::get($_GET['View']);

      $OSCOM_Template->setValue('partner', $partner);
      $OSCOM_Template->setValue('partner_header', (empty($partner['image_big']) ? HTML::image(OSCOM::getPublicSiteLink($OSCOM_Template->getValue('highlights_image')), null, 940, 285) : '<a href="' . HTML::outputProtected($partner['url']) . '" target="_blank">' . HTML::image(OSCOM::getPublicSiteLink('images/partners/' . $partner['image_big']), null, 940, 285) . '</a>'));

      $application->setPageContent('partner_preview.html');
      $application->setPageTitle(OSCOM::getDef('partner_view_html_title', array(':partner_title' => $partner['title'])));
    }
  }
?>
