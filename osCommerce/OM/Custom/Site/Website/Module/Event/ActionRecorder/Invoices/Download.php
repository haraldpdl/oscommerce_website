<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Event\ActionRecorder\Invoices;

use osCommerce\OM\Core\{
    ActionRecorder,
    OSCOM
};

use osCommerce\OM\Core\Site\Website\Invoices;

class Download
{
    public static function execute($invoice)
    {
        $ar = [
            'action' => 'invoice_download',
            'success' => 1,
            'user_id' => $_SESSION[OSCOM::getSite()]['Account']['id'],
            'identifier' => $invoice
        ];

        $file = realpath(OSCOM::getConfig('dir_fs_invoices') . basename($invoice) . '.pdf');

        if (empty($invoice) || (Invoices::exists($invoice, $_SESSION[OSCOM::getSite()]['Account']['id']) === false)) {
            $ar['success'] = 0;
            $ar['result'] = 'nonexistent';
        } elseif ((substr($file, 0, strlen(OSCOM::getConfig('dir_fs_invoices'))) !== OSCOM::getConfig('dir_fs_invoices')) || file_exists($file) === false) {
            $ar['success'] = 0;
            $ar['result'] = 'file_nonexistent';
        } else {
            $ar['identifier'] = Invoices::get($invoice, $_SESSION[OSCOM::getSite()]['Account']['id'], 'id') . ':' . $ar['identifier'];
        }

        ActionRecorder::save($ar);
    }
}
