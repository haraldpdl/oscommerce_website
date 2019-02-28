<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\News;

use osCommerce\OM\Core\Registry;

class GetLatest
{
    public static function execute(): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        return $OSCOM_PDO->get('website_news', [
            'id',
            'title',
            'date_added'
        ], [
            'status' => 1
        ], 'date_added desc, title', 1, [
            'cache' => [
                'key' => 'website-news-listing-latest_slim'
            ]
        ])->fetch();
    }
}
