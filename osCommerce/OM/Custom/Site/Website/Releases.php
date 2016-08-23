<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\OSCOM;

class Releases
{
    protected static $versions;

    public static function versionExists($version)
    {
        if (preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $matches) === 1) {
            if (!isset(static::$versions)) {
                static::setVersions();
            }

            foreach (static::$versions as $v) {
                if ($v['version'] == $version) {
                    return true;
                }
            }

            return false;
        }

        trigger_error('Error: Website\\Releases::versionExists(): Version is malformed: ' . $version);

        return false;
    }

    public static function hasApps($version)
    {
        if (preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $matches) === 1) {
            if (!isset(static::$versions)) {
                static::setVersions();
            }

            foreach (static::$versions as $v) {
                if ($v['version'] == $version) {
                    return ($v['has_apps'] === true);
                }
            }

            return false;
        }

        trigger_error('Error: Website\\Releases::hasApps(): Version is malformed: ' . $version);

        return false;
    }

    protected static function setVersions()
    {
        static::$versions = OSCOM::callDB('Website\GetReleaseVersions', null, 'Site');
    }
}
