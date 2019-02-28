<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Partner;

use osCommerce\OM\Core\Registry;

class GetPackageId
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qpkg = $OSCOM_PDO->prepare('select id from :table_website_partner_package where code = :code');
        $Qpkg->bindValue(':code', $data['code']);
        $Qpkg->execute();

        return $Qpkg->fetch();
    }
}
