<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Invoices;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    Events,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Invoices;

class Get
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');

        $token = isset($_GET['token']) ? trim(str_replace(array("\r\n", "\n", "\r"), '', $_GET['token'])) : '';
        $invoice = trim(str_replace(array("\r\n", "\n", "\r"), '', $_GET['Get']));

        if ($token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
            $OSCOM_MessageStack->add('account', OSCOM::getDef('error_form_protect_general'), 'error');

            return false;
        }

        Events::fire('invoice-download-before', $invoice);

        if (empty($invoice) || (Invoices::exists($invoice, $_SESSION[OSCOM::getSite()]['Account']['id']) === false)) {
            $OSCOM_MessageStack->add('account', OSCOM::getDef('error_invoice_nonexistent'), 'error');

            return false;
        }

        $file = realpath(OSCOM::getConfig('dir_fs_invoices') . basename($invoice) . '.pdf');

        if ((substr($file, 0, strlen(OSCOM::getConfig('dir_fs_invoices'))) !== OSCOM::getConfig('dir_fs_invoices')) || file_exists($file) === false) {
            $OSCOM_MessageStack->add('account', OSCOM::getDef('error_invoice_file_nonexistent'), 'error');

            return false;
        }

        Events::fire('invoice-download-after', $invoice);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($invoice) . '.pdf"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));

        readfile($file);

        exit;
    }
}
