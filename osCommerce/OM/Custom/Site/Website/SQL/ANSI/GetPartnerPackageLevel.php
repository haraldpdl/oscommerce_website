<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetPartnerPackageLevel
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qlevel = $OSCOM_PDO->prepare('select * from :table_website_partner_package_levels where id = :id');
        $Qlevel->bindInt(':id', $data['id']);
        $Qlevel->execute();

        return $Qlevel->fetch();
    }
}
