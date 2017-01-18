<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner\Extend\Payment;

use osCommerce\OM\Core\ApplicationAbstract;
use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Registry;

class Cancel
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');

        if (isset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult'])) {
            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalResult']);
        }

        if (isset($_SESSION[OSCOM::getSite()]['PartnerPayPalSecret'])) {
            unset($_SESSION[OSCOM::getSite()]['PartnerPayPalSecret']);
        }

        $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_partner_payment_cancelled'), 'warning');
    }
}
