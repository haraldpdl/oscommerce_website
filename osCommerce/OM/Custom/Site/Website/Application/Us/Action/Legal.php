<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Us\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM,
    Registry
};

class Legal
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Language = Registry::get('Language');

        $OSCOM_Language->loadIniFile('pages/legal_tos_general.php');
        $OSCOM_Language->loadIniFile('pages/legal_tos_ambassador.php');
        $OSCOM_Language->loadIniFile('pages/legal_tos_apps_marketplace.php');
        $OSCOM_Language->loadIniFile('pages/legal_tos_forums.php');
        $OSCOM_Language->loadIniFile('pages/legal_tos_live_sites.php');

        $application->setPageContent('legal.html');
        $application->setPageTitle(OSCOM::getDef('legal_html_page_title'));
    }
}
