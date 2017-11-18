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
        $OSCOM_MessageStack = Registry::get('MessageStack');
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

        if (isset($_GET['ms']) && !empty($_GET['ms'])) {
            switch ($_GET['ms']) {
                case 'verified':
                    $OSCOM_MessageStack->add('account', OSCOM::getDef('verify_ms_success'), 'success');
                    break;

                case 'already_verified':
                    $OSCOM_MessageStack->add('account', OSCOM::getDef('verify_ms_error_already_verified'), 'warning');
                    break;

                case 'change_password_not_logged_in':
                    $_SESSION['login_redirect'] = [
                        'url' => OSCOM::getLink('Website', 'Account', 'ChangePassword', 'SSL')
                    ];

                    $OSCOM_MessageStack->add('account', OSCOM::getDef('change_password_ms_error_not_logged_in'), 'warning');
                    break;

                case 'new_password_saved':
                    $OSCOM_MessageStack->add('account', OSCOM::getDef('reset_password_new_ms_success'), 'success');
                    break;
            }
        }

        $application->setPageContent('login.html');
        $application->setPageTitle(OSCOM::getDef('login_html_title'));
    }
}
