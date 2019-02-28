<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Contact\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM
};

class Newsletter
{
    public static function execute(ApplicationAbstract $application)
    {
        $application->setPageContent('newsletter.html');
        $application->setPageTitle(OSCOM::getDef('newsletter_html_page_title'));
    }
}
