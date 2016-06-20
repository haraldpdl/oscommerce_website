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

        if (isset($_COOKIE['member_id']) && isset($_COOKIE['pass_hash'])) {
            OSCOM::setCookie('member_id', '', time() - 31536000, null, null, false, true);
            OSCOM::setCookie('pass_hash', '', time() - 31536000, null, null, false, true);
        }

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
