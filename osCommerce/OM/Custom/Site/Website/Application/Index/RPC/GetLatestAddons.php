<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\{
    Cache,
    HttpRequest
};

class GetLatestAddons
{
    public static function execute()
    {
        $result = [];

        $OSCOM_Cache = new Cache();

        if ($OSCOM_Cache->read('website-addons-listing-last5', 60)) {
            $result = $OSCOM_Cache->getCache();
        } else {
            $addons = HttpRequest::getResponse(['url' => 'http://addons.oscommerce.com/?action=fetchLatest']);

            if (!empty($addons)) {
                $addons = json_decode($addons, true);

                foreach ($addons as $a) {
                    $result[] = [
                        'title' => $a['title'],
                        'link' => 'http://addons.oscommerce.com/info/' . (int)$a['id'],
                        'date' => $a['date']
                    ];
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
