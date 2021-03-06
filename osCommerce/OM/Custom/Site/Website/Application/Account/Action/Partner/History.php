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

class History
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        if (empty($_GET['History']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['History'])) {
            Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_error_campaign_not_available'), 'error');

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner', 'SSL'));
        }

        $partner = Partner::get($_GET['History']);

        $OSCOM_Template->setValue('partner', $partner);

        $application->setPageContent('partner_history.html');

        $application->setPageTitle(OSCOM::getDef('partner_view_html_title', [
            ':partner_title' => $partner['title']
        ]));
    }
}
