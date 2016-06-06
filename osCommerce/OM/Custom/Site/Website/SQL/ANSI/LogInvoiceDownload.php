<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class LogInvoiceDownload
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qlog = $OSCOM_PDO->prepare('insert into :table_website_account_invoices_log (invoice_id, date_added, ip_address, user_id) values (:invoice_id, now(), :ip_address, :user_id)');
        $Qlog->bindInt(':invoice_id', $data['id']);
        $Qlog->bindValue(':ip_address', $data['ip_address']);
        $Qlog->bindInt(':user_id', $data['user_id']);
        $Qlog->execute();

        return $Qlog->rowCount();
    }
}
