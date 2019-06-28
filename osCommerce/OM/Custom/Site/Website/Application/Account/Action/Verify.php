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
    Is,
    OSCOM,
    Registry,
    Sanitize
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

        $user_id = Sanitize::simple($_GET['id'] ?? null);
        $key = Sanitize::simple($_GET['key'] ?? null);

        if (Is::Integer($user_id, 1) && (preg_match('/^[a-zA-Z0-9\-\_]{32}$/', $key) === 1)) {
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
