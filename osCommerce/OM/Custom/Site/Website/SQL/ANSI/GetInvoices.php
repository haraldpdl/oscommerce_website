<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetInvoices
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qinvoices = $OSCOM_PDO->prepare('select i.*, c.symbol_left as currency from :table_website_account_invoices i, :table_currencies c where i.user_id = :user_id and i.currency_id = c.currencies_id order by i.date desc');
        $Qinvoices->bindInt(':user_id', $data['user_id']);
        $Qinvoices->setCache('users-' . $data['user_id'] . '-invoices', 1440);
        $Qinvoices->execute();

        return $Qinvoices->fetchAll();
    }
}
