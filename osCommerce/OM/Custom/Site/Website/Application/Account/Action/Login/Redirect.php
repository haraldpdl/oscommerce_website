<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Login;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM
};

class Redirect
{
    public static function execute(ApplicationAbstract $application)
    {
        $url = OSCOM::getLink(null, OSCOM::getDefaultSiteApplication(), null, 'AUTO');

        if (isset($_SESSION['login_redirect']) && isset($_SESSION['login_redirect']['cancel_url']) && isset($_SESSION['login_redirect']['cancel_text'])) {
            $url = $_SESSION['login_redirect']['cancel_url'];

            unset($_SESSION['login_redirect']);
        }

        OSCOM::redirect($url);
    }
}
