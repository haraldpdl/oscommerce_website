<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\{
    Cache,
    OSCOM,
    PDO,
    Registry
};

use osCommerce\OM\Core\Site\Apps\Apps;

class GetLatestAddons
{
    public static function execute()
    {
        Registry::set('PDO_OLD', PDO::initialize(OSCOM::getConfig('legacy_db_server', 'Apps'), OSCOM::getConfig('legacy_db_server_username', 'Apps'), OSCOM::getConfig('legacy_db_server_password', 'Apps'), OSCOM::getConfig('legacy_db_database', 'Apps')));

        $result = [];

        $OSCOM_Cache = new Cache();

        if ($OSCOM_Cache->read('website-addons-listing-last5', 60)) {
            $result = $OSCOM_Cache->getCache();
        } else {
            $listing = Apps::getListing();

            if (isset($listing['entries']) && !empty($listing['entries'])) {
                $counter = 0;

                foreach ($listing['entries'] as $l) {
                    $counter += 1;

                    $date = \DateTime::createFromFormat('Ymd His', $l['last_update_date']);

                    $result[] = [
                        'title' => $l['title'],
                        'link' => 'https://apps.oscommerce.com/' . $l['public_id'],
                        'date' => $date->format('Y-m-d H:i:s')
                    ];

                    if ($counter === 5) {
                        break;
                    }
                }

                if (count($result) === 5) {
                    $OSCOM_Cache->write($result);
                }
            }
        }

        header('Cache-Control: max-age=10800, must-revalidate');
        header_remove('Pragma');
        header('Content-Type: application/javascript');

        echo json_encode($result);
    }
}
