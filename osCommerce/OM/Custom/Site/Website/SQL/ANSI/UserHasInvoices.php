<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class UserHasInvoices
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qcheck = $OSCOM_PDO->prepare('select id from :table_website_account_invoices where user_id = :user_id limit 1');
        $Qcheck->bindInt(':user_id', $data['user_id']);
        $Qcheck->setCache('users-' . $data['user_id'] . '-invoices-check', 1440, true);
        $Qcheck->execute();

        return $Qcheck->fetch() !== false;
    }
}
