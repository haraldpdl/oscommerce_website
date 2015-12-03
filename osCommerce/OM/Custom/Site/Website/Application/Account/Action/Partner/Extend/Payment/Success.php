<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2015 osCommerce; http://www.oscommerce.com
 * @license BSD; http://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner\Extend\Payment;

use osCommerce\OM\Core\ApplicationAbstract;

class Success
{
    public static function execute(ApplicationAbstract $application)
    {
        $application->setPageContent('partner_extend_success.html');
    }
}
