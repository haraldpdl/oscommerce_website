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
    Mail,
    OSCOM,
    Registry,
    RunScript
};

use osCommerce\OM\Core\Site\Website\{
    Download,
    News,
    Users
};

class GetReleases implements \osCommerce\OM\Core\RunScriptInterface
{
    public static function execute()
    {
        OSCOM::initialize('Website');

        $OSCOM_Language = Registry::get('Language');
        $OSCOM_Template = Registry::get('Template');

        $OSCOM_Language->loadIniFileFromDirectory(__DIR__ . '/languages', 'email.php');

        $source = __DIR__ . '/editions.json';

        if (is_file($source)) {
            $source = file_get_contents($source);

            if (!empty($source)) {
                $new_releases = [];

                $editions = json_decode($source, true);

                foreach ($editions as $e) {
                    $res = HttpRequest::getResponse(['url' => 'https://api.github.com/repos/' . $e['github']['owner'] . '/' . $e['github']['repository'] . '/releases']);

                    if (!empty($res)) {
                        $releases = json_decode($res, true);

                        if (is_array($releases) && !empty($releases)) {
                            $user = Users::get($e['oscommerce']['user_id']);

                            foreach ($releases as $rel) {
                                if (($rel['draft'] === false) && ($rel['prerelease'] === false) && (preg_match('/^v?(\d+)(\.\d+)+$/', $rel['tag_name']) === 1) && (!isset($e['github']['start_from_id']) || ($rel['id'] >= $e['github']['start_from_id']))) {
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

                                                if (!isset($new_releases[$e['oscommerce']['download_release_group']]) || !in_array($e['oscommerce']['code'], $new_releases[$e['oscommerce']['download_release_group']])) {
                                                    $new_releases[$e['oscommerce']['download_release_group']][] = $e['oscommerce']['code'];
                                                }

                                                RunScript::error('[CommunityEditions] Added ' . $rel['name'] . ' (' . Users::get($e['oscommerce']['user_id'], 'name') . ')');

                                                $OSCOM_Template->setValue('user_name', $user['name'], true);
                                                $OSCOM_Template->setValue('release_title', $e['oscommerce']['title'], true);
                                                $OSCOM_Template->setValue('release_version', $rel['tag_name'], true);
                                                $OSCOM_Template->setValue('release_code', $release_code, true);
                                                $OSCOM_Template->setValue('news_url', isset($news_id) ? News::getUrl($news_id) : null, true);

                                                $email_txt = $OSCOM_Template->getContent(__DIR__ . '/pages/email.txt');
                                                $email_html = $OSCOM_Template->getContent(__DIR__ . '/pages/email.html');

                                                if (!empty($email_txt) || !empty($email_html)) {
                                                    $OSCOM_Mail = new Mail($user['email'], $user['name'], 'hello@oscommerce.com', 'osCommerce', OSCOM::getDef('email_title'));

                                                    $OSCOM_Mail->addBCC('hpdl@oscommerce.com', 'Harald Ponce de Leon');

                                                    if (!empty($email_txt)) {
                                                        $OSCOM_Mail->setBodyPlain($email_txt);
                                                    }

                                                    if (!empty($email_html)) {
                                                        $OSCOM_Mail->setBodyHTML($email_html);
                                                    }

                                                    $OSCOM_Mail->send();
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($new_releases)) {
                    $OSCOM_Cache = Registry::get('Cache');

                    $OSCOM_Cache->clear('website-news-listing');
                    $OSCOM_Cache->clear('website-releases');

                    Download::clearInternalCache();

                    $history = [];

                    foreach ($new_releases as $pkg_group => $rel_groups) {
                        foreach ($rel_groups as $rel_group) {
                            foreach (Download::getAll($pkg_group, $rel_group) as $r) {
                                $matches = null;

                                if (preg_match('/^v?(\d+[\.\d+]+)$/', $r['version'], $matches) === 1) {
                                    $version = $matches[1];
                                    $major_version = explode('.', $version, 2)[0];

                                    $relDateTime = \DateTime::createFromFormat('!Y-m-d', $r['date']);

                                    $history[$rel_group][$major_version][] = $version . '|' . ($relDateTime !== false ? $relDateTime->format('Ymd') : '') . '|' . ($r['news_id'] ? News::getUrl($r['news_id']) : '');
                                }
                            }
                        }
                    }

                    foreach ($history as $rel_group => $gvalue) {
                        foreach ($gvalue as $major_version => $releases) {
                            $releases_content = implode("\n", $releases);

                            $releases_file = realpath(__DIR__ . '/../../../../../../../version/online_merchant/') . '/ce/' . basename((string)$rel_group) . '/' . basename($major_version);

                            if (!is_file($releases_file) || (md5_file($releases_file) !== md5($releases_content))) {
                                if (!is_dir(dirname($releases_file))) {
                                    mkdir(dirname($releases_file), 0664, true);
                                }

                                file_put_contents($releases_file, $releases_content);

                                chmod($releases_file, 0664);
                            }
                        }
                    }
                }
            }
        }
    }
}
