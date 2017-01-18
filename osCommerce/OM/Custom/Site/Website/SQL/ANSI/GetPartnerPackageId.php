<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetPartnerPackageId
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qpkg = $OSCOM_PDO->prepare('select id from :table_website_partner_package where code = :code');
        $Qpkg->bindValue(':code', $data['code']);
        $Qpkg->execute();

        return $Qpkg->fetch();
    }
}
