<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Partner;

class Edit
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_Template = Registry::get('Template');

        if (empty($_GET['Edit']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Edit'])) {
            Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_error_campaign_not_available'), 'error');

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner', 'SSL'));
        }

        $partner = Partner::get($_GET['Edit']);

        $OSCOM_Template->setValue('partner', $partner);

        foreach ($OSCOM_Language->getAll() as $l) {
            $OSCOM_Template->setValue('partner_campaign_' . $l['code'], Partner::getCampaignInfo($partner['id'], $l['id']));
        }

        $application->setPageContent('partner_edit.html');

        $application->setPageTitle(OSCOM::getDef('partner_view_html_title', [
            ':partner_title' => $partner['title']
        ]));

        $OSCOM_Template->addHtmlElement('footer', '<script src="' . OSCOM::getPublicSiteLink('external/bs-custom-file-input.min.js') . '"></script>');
        $OSCOM_Template->addHtmlElement('footer', '<script>$(document).ready(function(){bsCustomFileInput.init();});</script>');
    }
}
