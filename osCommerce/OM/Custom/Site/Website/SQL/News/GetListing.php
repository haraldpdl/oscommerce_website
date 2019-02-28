<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\News;

use osCommerce\OM\Core\Registry;

class GetListing
{
    public static function execute(): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        return $OSCOM_PDO->get('website_news', [
            'id',
            'title',
            'date_added',
            'date_format(date_added, "%D %M %Y") as date_added_formatted'
        ], [
            'status' => 1
        ], 'date_added desc, title', null, [
            'cache' => [
                'key' => 'website-news-listing'
            ]
        ])->fetchAll();
    }
}
