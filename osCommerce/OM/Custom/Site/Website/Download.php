<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
    Cache,
    HttpRequest,
    OSCOM,
    Registry
};

//use osCommerce\OM\Core\Site\Me\Me;

class Download
{
    const FILE_DIRECTORY = OSCOM::PUBLIC_DIRECTORY . 'public/sites/Website/files/';

    protected static $files;

    public static function getAll($pkg_group = null, $rel_group = null)
    {
        if (!isset(static::$files)) {
            static::setReleases();
        }

        if (isset($pkg_group)) {
            if (isset($rel_group)) {
                $rel = [];

                foreach (static::$files[$pkg_group] as $code => $data) {
                    if ($data['group'] == $rel_group) {
                        $rel[$code] = $data;
                    }
                }

                return $rel;
            }

            return static::$files[$pkg_group];
        }

        return static::$files;
    }

    public static function get($id, $key = null)
    {
        if (!isset(static::$files)) {
            static::setReleases();
        }

        if (!is_numeric($id)) {
            $id = static::getID($id);
        }

        foreach (static::$files as $pkg_group => $releases) {
            foreach ($releases as $code => $data) {
                if ($data['id'] == $id) {
                    if (isset($key)) {
                        return $data[$key];
                    } else {
                        return $data;
                    }
                }
            }
        }

        return false;
    }

    public static function getID($code)
    {
        if (!isset(static::$files)) {
            static::setReleases();
        }

        foreach (static::$files as $pkg_group => $releases) {
            foreach ($releases as $file_code => $file_data) {
                if ($code == $file_code) {
                    return $file_data['id'];
                }
            }
        }

        return false;
    }

    public static function exists($id)
    {
        if (!isset(static::$files)) {
            static::setReleases();
        }

        if (!is_numeric($id)) {
            $id = static::getID($id);
        }

        foreach (static::$files as $pkg_group => $releases) {
            foreach ($releases as $code => $data) {
                if ($data['id'] == $id) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function incrementDownloadCounter($id): int
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (!is_numeric($id)) {
            $id = static::getID($id);
        }

        return $OSCOM_PDO->call('IncrementDownloadCounter', ['id' => $id]);
    }

    protected static function setReleases()
    {
        $OSCOM_PDO = Registry::get('PDO');

        static::$files = $OSCOM_PDO->call('GetReleases');
    }

    public static function getCommunityEditions(string $pkg_group): array
    {
        $OSCOM_Cache = new Cache();

        if ($OSCOM_Cache->read('website-releases-ce-' . $pkg_group)) {
            $result = $OSCOM_Cache->getCache();
        } else {
            $result = [];

            $source = OSCOM::BASE_DIRECTORY . '/Custom/Site/Website/Scripts/CommunityEditions/editions.json';

            if (file_exists($source)) {
                $editions = json_decode(file_get_contents($source), true);

                foreach ($editions as $e) {
                    $gh = HttpRequest::getResponse(['url' => 'https://api.github.com/repos/' . $e['github']['owner'] . '/' . $e['github']['repository']]);

                    if (!empty($gh)) {
                        $gh = json_decode($gh, true);

                        if (is_array($gh) && !empty($gh)) {
                            $rel = array_values(static::getAll($pkg_group, $e['oscommerce']['code']))[0]; // get latest version

                            $release = [
                                'title' => $rel['title'],
                                'version' => $rel['version'],
                                'description' => $gh['description'],
                                'code' => $rel['code'],
                                'news_id' => $rel['news_id'],
                                'github' => $e['github']['owner'] . '/' . $e['github']['repository'],
                                'support_url' => null,
                                'user_name' => null,
                                'user_photo_url' => null,
                                'user_profile_url' => null
                            ];

                            if (isset($e['oscommerce']['forum_channel_id'])) {
                                $release['support_url'] = Invision::getForumChannelUrl($e['oscommerce']['forum_channel_id']);
                            } elseif (isset($e['oscommerce']['forum_club_id'])) {
                                $release['support_url'] = Invision::getForumClubUrl($e['oscommerce']['forum_club_id']);
                            }

/*
                            if (Me::userIdExists($e['oscommerce']['user_id'])) {
                                $user = Users::get($e['oscommerce']['user_id']);
                                $user_me = Me::get($e['oscommerce']['user_id']);

                                $release['user_name'] = !empty($user['full_name']) ? $user['full_name'] : $user['name'];
                                $release['user_photo_url'] = $user['photo_url'];
                                $release['user_profile_url'] = OSCOM::getLink('Me', null, $user_me['profile_name']);
                            }
*/
                            $result[] = $release;
                        }
                    }
                }
            }

            $OSCOM_Cache->write($result);
        }

        if (!is_array($result)) {
            $result = [];
        }

        return $result;
    }
}
