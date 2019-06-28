<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM
};

class ChangePassword
{
    public static function execute(ApplicationAbstract $application)
    {
        if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $req_actions = http_build_query(array_slice($_GET, (int)array_search('ChangePassword', array_keys($_GET))));

            $_SESSION['login_redirect'] = [
                'url' => OSCOM::getLink(null, null, $req_actions, 'SSL')
            ];

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Login', 'SSL'));
        }

        $application->setPageContent('change_password.html');
        $application->setPageTitle(OSCOM::getDef('change_password_html_title'));
    }
}
