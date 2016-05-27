<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM
};

class Verify
{
    public static function execute(ApplicationAbstract $application)
    {
        if (isset($_SESSION[OSCOM::getSite()]['Account'])) {
            OSCOM::redirect(OSCOM::getLink(null, 'Account', null, 'SSL'));
        }

        $application->setPageContent('verify.html');
        $application->setPageTitle(OSCOM::getDef('verify_html_title'));
    }
}
