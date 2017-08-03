<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\_\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM
};

class Sponsorship
{
    public static function execute(ApplicationAbstract $application)
    {
        OSCOM::redirect(OSCOM::getLink(null, '_', 'Ambassadors'));
    }
}
