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
    OSCOM,
    Registry
};

class Login
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        if (isset($_SESSION[OSCOM::getSite()]['Account'])) {
            OSCOM::redirect(OSCOM::getLink(null, null, null, 'SSL'));
        }

        if (isset($_SESSION['login_redirect'])) {
            if (isset($_SESSION['login_redirect']['info_text'])) {
                $OSCOM_Template->setValue('login_redirect_info_text', $_SESSION['login_redirect']['info_text']);
            }

            if (isset($_SESSION['login_redirect']['cancel_url']) && isset($_SESSION['login_redirect']['cancel_text'])) {
                $OSCOM_Template->setValue('login_redirect_cancel_text', $_SESSION['login_redirect']['cancel_text']);
            }
        }

        $application->setPageContent('login.html');
        $application->setPageTitle(OSCOM::getDef('login_html_title'));
    }
}
