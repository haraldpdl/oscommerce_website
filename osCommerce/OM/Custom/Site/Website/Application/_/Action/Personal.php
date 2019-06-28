<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\_\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM,
    Registry
};

class Personal
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        $application->setPageContent('personal.html');
        $application->setPageTitle(OSCOM::getDef('personal_html_page_title'));

        $OSCOM_Template->setValue('sweet_sixteen', (int)date('Y') - 2000);
    }
}
