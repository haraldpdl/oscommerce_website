<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Scripts\CommunityEditions;

use osCommerce\OM\Core\{
    HttpRequest,
    OSCOM,
    Registry,
    RunScript
};

use osCommerce\OM\Core\Site\Website\{
    Download,
    Users
};

class GetReleases implements \osCommerce\OM\Core\RunScriptInterface
{
    public static function execute()
    {
        OSCOM::initialize('Website');

        $source = __DIR__ . '/editions.json';

        if (file_exists($source)) {
            $editions = json_decode(file_get_contents($source), true);

            foreach ($editions as $e) {
                $res = HttpRequest::getResponse(['url' => 'https://api.github.com/repos/' . $e['github']['owner'] . '/' . $e['github']['repository'] . '/releases']);

                if (!empty($res)) {
                    $releases = json_decode($res, true);

                    if (is_array($releases) && !empty($releases)) {
                        foreach ($releases as $rel) {
                            if (($rel['draft'] === false) && ($rel['prerelease'] === false) && (!isset($e['github']['start_from_id']) || ($rel['id'] >= $e['github']['start_from_id']))) {
                                $release_code = $e['oscommerce']['download_release_group'] . '-' . $e['oscommerce']['code'] . '-' . $rel['tag_name'];

                                if (!Download::exists($release_code)) {
                                    $file = HttpRequest::getResponse(['url' => $rel['zipball_url']]);

                                    if (!empty($file)) {
                                        if (file_put_contents(Download::FILE_DIRECTORY . $release_code . '.zip', $file)) {
                                            $news_id = null;

                                            if (!empty($rel['body'])) {
                                                if (!isset($Parsedown)) {
                                                    $Parsedown = new \Parsedown();
                                                    $Parsedown->setSafeMode(true);
                                                }

                                                $body = str_replace(["\r", "\n"], '', $Parsedown->text($rel['body']));

                                                Registry::get('PDO')->save('website_news', [
                                                    'title' => $rel['name'],
                                                    'body' => $body,
                                                    'date_added' => (new \DateTime($rel['published_at']))->format('Y-m-d H:i:s'),
                                                    'status' => 1,
                                                    'author_id' => $e['oscommerce']['user_id']
                                                ]);

                                                $news_id = Registry::get('PDO')->lastInsertId();
                                            }

                                            Registry::get('PDO')->save('website_downloads', [
                                                'code' => $release_code,
                                                'title' => $e['oscommerce']['title'],
                                                'version' => $rel['tag_name'],
                                                'rel_group' => $e['oscommerce']['code'],
                                                'filename' => $release_code . '.zip',
                                                'date' => (new \DateTime($rel['published_at']))->format('Y-m-d'),
                                                'type' => 'release',
                                                'pkg_group' => $e['oscommerce']['download_release_group'],
                                                'news_id' => $news_id
                                            ]);

                                            if (isset($news_id)) {
                                                Registry::get('Cache')->clear('website-news-listing');
                                            }

                                            Registry::get('Cache')->clear('website-releases');

                                            RunScript::error('[CommunityEditions] Added ' . $rel['name'] . ' (' . Users::get($e['oscommerce']['user_id'], 'name') . ')');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
