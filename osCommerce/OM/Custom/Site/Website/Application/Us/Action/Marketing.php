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
    OSCOM
};

class Marketing
{
    public static function execute(ApplicationAbstract $application)
    {
        $application->setPageContent('marketing.html');
        $application->setPageTitle(OSCOM::getDef('marketing_html_page_title'));
    }
}
