<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\Registry;

class Download
{
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
}
