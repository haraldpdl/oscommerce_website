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
