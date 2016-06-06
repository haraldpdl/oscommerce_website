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

use osCommerce\OM\Core\Site\Website\Invoices as InvoicesClass;

class Invoices
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $req_actions = http_build_query(array_slice($_GET, array_search('Invoices', array_keys($_GET))));

            $_SESSION['login_redirect'] = [
                'url' => OSCOM::getLink(null, null, $req_actions, 'SSL')
            ];

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Login', 'SSL'));
        }

        if (InvoicesClass::hasInvoices($_SESSION[OSCOM::getSite()]['Account']['id'])) {
            $OSCOM_Template->setValue('invoices', InvoicesClass::getAll($_SESSION[OSCOM::getSite()]['Account']['id']));

            $application->setPageContent('invoices.html');
        } else {
            $application->setPageContent('invoices_empty.html');
        }

        $application->setPageTitle(OSCOM::getDef('invoices_html_title'));
    }
}
