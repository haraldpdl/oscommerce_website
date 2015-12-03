<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2015 osCommerce; http://www.oscommerce.com
 * @license BSD; http://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner;

use osCommerce\OM\Core\ApplicationAbstract;
use osCommerce\OM\Core\HTML;
use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Registry;

use osCommerce\OM\Core\Site\Website\Partner;

class Edit
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        if (empty($_GET['Edit']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Edit'])) {
            Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_error_campaign_not_available'), 'error');

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner', 'SSL'));
        }

        $partner_campaign = Partner::getCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Edit']);

        $OSCOM_Template->setValue('partner_campaign', $partner_campaign);
        $OSCOM_Template->setValue('partner_header', HTML::image(OSCOM::getPublicSiteLink(empty($partner_campaign['image_big']) ? $OSCOM_Template->getValue('highlights_image') : 'images/partners/' . $partner_campaign['image_big'])));

        $application->setPageContent('partner_edit.html');

        $application->setPageTitle(OSCOM::getDef('partner_view_html_title', [
            ':partner_title' => $partner_campaign['title']
        ]));
    }
}
