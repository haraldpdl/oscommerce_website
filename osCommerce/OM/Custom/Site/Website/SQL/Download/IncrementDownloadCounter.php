<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Download;

use osCommerce\OM\Core\Registry;

class IncrementDownloadCounter
{
    public static function execute(array $data): int
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qfile = $OSCOM_PDO->prepare('update :table_website_downloads set counter = counter+1 where id = :id');
        $Qfile->bindInt(':id', $data['id']);
        $Qfile->execute();

        return $Qfile->rowCount();
    }
}
