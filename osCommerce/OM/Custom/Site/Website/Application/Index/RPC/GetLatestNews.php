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
    OSCOM
};

use osCommerce\OM\Core\Site\Website\News;

class GetLatestNews
{
    public static function execute()
    {
        $result = [];

        $OSCOM_Cache = new Cache();

        if ($OSCOM_Cache->read('website-news-listing-last5')) {
            $result = $OSCOM_Cache->getCache();
        } else {
            $news = News::getListing();

            if (!empty($news)) {
                $news = array_slice($news, 0, 5, true);

                foreach ($news as $n) {
                    $result[] = [
                        'title' => $n['title'],
                        'link' => OSCOM::getLink(null, 'Us', 'News=' . $n['id'], 'SSL', false),
                        'date' => $n['date_added']
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
