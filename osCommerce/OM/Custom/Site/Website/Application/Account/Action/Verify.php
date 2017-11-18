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

class Verify
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        if (isset($_SESSION[OSCOM::getSite()]['Account'])) {
            OSCOM::redirect(OSCOM::getLink(null, 'Account', null, 'SSL'));
        }

        $user_id = isset($_GET['id']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_GET['id'])) : '';
        $key = isset($_GET['key']) ? preg_replace('/[^a-zA-Z0-9\-\_]/', '', $_GET['key']) : '';

        if (is_numeric($user_id) && ($user_id > 0) && (strlen($key) === 32)) {
            $OSCOM_Template->setValue('verifyKey', [
                'user_id' => $user_id,
                'key' => $key
            ]);
        }

        if (isset($_GET['ms']) && !empty($_GET['ms'])) {
            switch ($_GET['ms']) {
                case 'account_created':
                    $OSCOM_MessageStack->add('account', OSCOM::getDef('create_ms_success'), 'success');
                    break;
            }
        }

        $application->setPageContent('verify.html');
        $application->setPageTitle(OSCOM::getDef('verify_html_title'));
    }
}
