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

use osCommerce\OM\Core\Site\Website\Invoices as InvoicesClass;

class Invoices
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $req_actions = http_build_query(array_slice($_GET, (int)array_search('Invoices', array_keys($_GET))));

            $_SESSION['login_redirect'] = [
                'url' => OSCOM::getLink(null, null, $req_actions, 'SSL')
            ];

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'Login', 'SSL'));
        }

        if (InvoicesClass::hasInvoices($_SESSION[OSCOM::getSite()]['Account']['id'])) {
            $invoices = [];

            foreach (InvoicesClass::getAll($_SESSION[OSCOM::getSite()]['Account']['id']) as $invoice) {
                $invoices[] = [
                    'year' => date_format(new \DateTime($invoice['date']), 'Y'),
                    'number' => $invoice['number'],
                    'date' => $invoice['date_formatted'],
                    'title' => $invoice['title'],
                    'cost' => $invoice['cost_formatted'],
                    'status' => $invoice['status_code']
                ];
            }

            $OSCOM_Template->setValue('invoices', $invoices);
            $OSCOM_Template->setValue('invoices_link', OSCOM::getLink(null, 'Account', 'Invoices&Get=%number%&token=' . md5($OSCOM_Template->getValue('public_token')), 'SSL'));

            $application->setPageContent('invoices.html');
        } else {
            $application->setPageContent('invoices_empty.html');
        }

        $application->setPageTitle(OSCOM::getDef('invoices_html_title'));
    }
}
