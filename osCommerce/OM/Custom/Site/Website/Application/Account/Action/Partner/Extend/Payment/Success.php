<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner\Extend\Payment;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM,
    Registry
};

class Success
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');

        $OSCOM_MessageStack->add('partner', OSCOM::getDef('success_partner_payment_processed'), 'success');

        $application->setPageContent('partner_extend_success.html');
    }
}
