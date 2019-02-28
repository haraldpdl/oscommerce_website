<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Partner;

use osCommerce\OM\Core\Registry;

class GetPackageLevel
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qlevel = $OSCOM_PDO->prepare('select * from :table_website_partner_package_levels where id = :id');
        $Qlevel->bindInt(':id', $data['id']);
        $Qlevel->execute();

        return $Qlevel->fetch();
    }
}
