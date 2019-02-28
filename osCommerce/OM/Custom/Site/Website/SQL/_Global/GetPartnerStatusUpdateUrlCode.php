<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\_Global;

use osCommerce\OM\Core\{
    Hash,
    Registry
};

class GetPartnerStatusUpdateUrlCode
{
    public static function execute(array $data): ?string
    {
        $OSCOM_PDO = Registry::get('PDO');

        $id = null;

        $Qurl = $OSCOM_PDO->prepare('select id from :table_website_partner_status_update_urls where partner_id = :partner_id and url = :url limit 1');
        $Qurl->bindInt(':partner_id', $data['partner_id']);
        $Qurl->bindValue(':url', $data['url']);
        $Qurl->execute();

        if ($Qurl->fetch() !== false) {
            $id = $Qurl->value('id');
        }

        while (!isset($id)) {
            $id = Hash::getRandomString(8);

            $Qcheck = $OSCOM_PDO->prepare('select id from :table_website_partner_status_update_urls where id = :id');
            $Qcheck->bindValue(':id', $id);
            $Qcheck->execute();

            if ($Qcheck->fetch() === false) {
                $new_url = [
                    'id' => $id,
                    'partner_id' => $data['partner_id'],
                    'url' => $data['url'],
                    'date_added' => 'now()'
                ];

                $OSCOM_PDO->save('website_partner_status_update_urls', $new_url);
            } else {
                $id = null;
            }
        }

        return $id;
    }
}
