<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\OSCOM;

use osCommerce\OM\Core\Site\Website\News;

class GetNewsLatest
{
    public static function execute()
    {
        $http_origin = $_SERVER['HTTP_ORIGIN'];

        if (in_array($http_origin, ['https://forums.oscommerce.com', 'http://forums.oscommerce.com'])) {
            header('Access-Control-Allow-Origin: ' . $http_origin);
        }

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header('Access-Control-Allow-Headers: X-Requested-With');
            exit;
        }

        $news = News::getLatest();

        $result = [
            'title' => $news['title'],
            'url' => OSCOM::getLink(null, 'Us', 'News=' . $news['id'], 'SSL', false)
        ];

        header('Cache-Control: max-age=10800, must-revalidate');
        header_remove('Pragma');

        header('Content-Type: application/json');

        echo json_encode($result);
    }
}
