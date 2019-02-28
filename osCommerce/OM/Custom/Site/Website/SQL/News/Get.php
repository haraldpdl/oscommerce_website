<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\News;

use osCommerce\OM\Core\Registry;

class Get
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qnews = $OSCOM_PDO->get([
            'website_news n' => [
                'rel' => 'website_user_profiles u',
                'on' => 'n.author_id = u.id'
            ]
        ], [
            'n.id',
            'n.title',
            'n.body',
            'n.date_added',
            'date_format(n.date_added, "%D %M %Y") as date_added_formatted',
            'n.image',
            'u.id as author_id',
            'u.display_name as author_name',
            'u.twitter_id as author_twitter_id',
            'u.google_plus_id as author_google_plus_id',
            'u.facebook_id as author_facebook_id',
            'u.github_id as author_github_id'
        ], [
            'n.id' => $data['id'],
            'n.status' => 1
        ], null, null, [
            'cache' => [
                'key' => 'website-news-' . (int)$data['id']
            ]
        ]);

        return $Qnews->fetch();
    }
}
