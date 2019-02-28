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
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Invision;

class ResetPassword
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        if (isset($_SESSION[OSCOM::getSite()]['Account'])) {
            OSCOM::redirect(OSCOM::getLink(null, 'Account', null, 'SSL'));
        }

        $application->setPageContent('reset_password.html');
        $application->setPageTitle(OSCOM::getDef('reset_password_html_title'));

        if (isset($_GET['key']) && !empty($_GET['key']) && isset($_GET['id']) && !empty($_GET['id'])) {
            if ((strlen($_GET['key']) === 32) && is_numeric($_GET['id']) && ($_GET['id'] > 0)) {
                $result = Invision::getPasswordResetKey($_GET['id']);

                if (is_array($result) && isset($result['key']) && ($_GET['key'] == $result['key'])) {
                    $OSCOM_Template->setValue('reset_password_key', $result['key']);
                    $OSCOM_Template->setValue('reset_password_id', $result['id']);

                    $application->setPageContent('reset_password_submit.html');
                } else {
                    $OSCOM_MessageStack->add('account', OSCOM::getDef('reset_password_key_ms_error_not_found'), 'error');
                }
            } else {
                $OSCOM_MessageStack->add('account', OSCOM::getDef('reset_password_key_ms_error_not_found'), 'error');
            }
        }
    }
}
