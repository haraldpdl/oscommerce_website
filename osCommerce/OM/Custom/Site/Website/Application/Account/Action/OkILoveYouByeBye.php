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
    Events,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Invision;

class OkILoveYouByeBye
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Session = Registry::get('Session');

        Events::fire('logoff-before');

        if (isset($_SESSION[OSCOM::getSite()]['Account'])) {
            unset($_SESSION[OSCOM::getSite()]['Account']);
        }

        $OSCOM_Session->recreate();

        Invision::killCookies();

        Events::fire('logoff-after');

        $redirect_url = OSCOM::getLink(null, null, 'Login', 'SSL');

        if (isset($_SESSION['logout_redirect'])) {
            if (isset($_SESSION['logout_redirect']['url'])) {
                $redirect_url = $_SESSION['logout_redirect']['url'];
            }

            unset($_SESSION['logout_redirect']);
        } else {
            $OSCOM_MessageStack->add('account', OSCOM::getDef('logout_ms_success'), 'success');
        }

        OSCOM::redirect($redirect_url);
    }
}
