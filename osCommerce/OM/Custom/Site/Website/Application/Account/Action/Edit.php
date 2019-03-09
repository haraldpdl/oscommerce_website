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

class Edit
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $req_actions = http_build_query(array_slice($_GET, array_search('Edit', array_keys($_GET))));

            $_SESSION['login_redirect'] = [
                'url' => OSCOM::getLink(null, null, $req_actions, 'SSL')
            ];

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Login', 'SSL'));
        }

        $custom_fields = $OSCOM_Template->getValue('user_custom');

        $bday = explode('/', $custom_fields['birthday'], 3);

        $OSCOM_Template->setValue('bday_month', $bday[0] ?? null);
        $OSCOM_Template->setValue('bday_date', $bday[1] ?? null);
        $OSCOM_Template->setValue('bday_year', $bday[2] ?? null);

        $dates = [];

        for ($i = 1; $i <= 31; $i++) {
            $dates[] = [
                'id' => $i,
                'title' => $i
            ];
        }

        $OSCOM_Template->setValue('date_dates', $dates);

        $months = [];

        for ($i = 1; $i <= 12; $i++) {
            $months[] = [
                'id' => $i,
                'title' => strftime('%B', mktime(0, 0, 0, $i, 1))
            ];
        }

        $OSCOM_Template->setValue('date_months', $months);

        $years = [];

        for ($i = date('Y'), $n = (date('Y') - 150); $i >= $n; $i--) {
            $years[] = [
                'id' => $i,
                'title' => $i
            ];
        }

        $OSCOM_Template->setValue('date_years', $years);

        $application->setPageContent('edit.html');
        $application->setPageTitle(OSCOM::getDef('account_html_title'));

        $OSCOM_Template->addHtmlElement('footer', '<script src="' . OSCOM::getPublicSiteLink('external/bs-custom-file-input-1.3.1.min.js') . '"></script>');
        $OSCOM_Template->addHtmlElement('footer', '<script>$(document).ready(function(){bsCustomFileInput.init();});</script>');
    }
}
