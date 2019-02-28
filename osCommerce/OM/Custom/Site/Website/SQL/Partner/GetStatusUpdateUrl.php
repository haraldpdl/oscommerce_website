<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Partner;

use osCommerce\OM\Core\Registry;

class GetStatusUpdateUrl
{
    public static function execute(array $data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qurl = $OSCOM_PDO->prepare('select url from :table_website_partner_status_update_urls where id = :id and partner_id = :partner_id');
        $Qurl->bindValue(':id', $data['id']);
        $Qurl->bindInt(':partner_id', $data['partner_id']);
        $Qurl->execute();

        if ($Qurl->fetch() !== false) {
            return $Qurl->value('url');
        }

        return false;
    }
}
