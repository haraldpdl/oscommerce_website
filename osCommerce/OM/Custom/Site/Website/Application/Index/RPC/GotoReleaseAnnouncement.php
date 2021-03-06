<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\OSCOM;

use osCommerce\OM\Core\Site\Website\{
    Download,
    News
};

class GotoReleaseAnnouncement
{
    public static function execute()
    {
        if (isset($_GET['v']) && !empty($_GET['v']) && Download::exists($_GET['v'])) {
            $news_id = Download::get($_GET['v'], 'news_id');

            if (isset($news_id) && is_numeric($news_id) && ($news_id > 0) && News::exists($news_id)) {
                OSCOM::redirect(OSCOM::getLink('Website', 'Us', 'News=' . $news_id));
            }
        }

        OSCOM::redirect(OSCOM::getLink('Website', 'Us', 'News'));
    }
}
