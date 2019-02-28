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

use osCommerce\OM\Core\Site\Sites\Sites as SitesClass;

class Sites
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        if (empty($_GET['Sites']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Sites'])) {
            Registry::get('MessageStack')->add('partner', OSCOM::getDef('partner_error_campaign_not_available'), 'error');

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Partner', 'SSL'));
        }

        $partner = Partner::get($_GET['Sites']);

        $OSCOM_Template->setValue('partner', $partner);

        $partner_campaign = Partner::getCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['Sites']);

        $OSCOM_Template->setValue('partner_campaign', $partner_campaign);

        $showcase = [];

        foreach (SitesClass::getShowcaseListing($_GET['Sites']) as $site) {
            $showcase[$site['public_id']] = $site;
        }

        uasort($showcase, function($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });

        $sites = [];

        foreach (Partner::getCampaignAdmins($_GET['Sites']) as $user_id) {
            foreach (SitesClass::getUserListing($user_id) as $site) {
                if (!isset($showcase[$site['public_id']]) && ($site['status'] == SitesClass::STATUS_LIVE)) {
                    $sites[$site['public_id']] = $site;
                }
            }
        }

        uasort($sites, function($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });

        $OSCOM_Template->setValue('partner_showcase', $showcase);
        $OSCOM_Template->setValue('partner_sites', $sites);
        $OSCOM_Template->setValue('partner_showcase_total', count($showcase));
        $OSCOM_Template->setValue('partner_showcase_max', is_numeric($partner_campaign['total_duration']) && $partner_campaign['total_duration'] > 24 ? 24 : (int)$partner_campaign['total_duration']);

        $application->setPageContent('partner_sites.html');

        $application->setPageTitle(OSCOM::getDef('partner_view_html_title', [
            ':partner_title' => $partner['title']
        ]));
    }
}
