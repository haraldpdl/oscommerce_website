<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
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
