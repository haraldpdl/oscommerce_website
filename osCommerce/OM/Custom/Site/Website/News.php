<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
};

class News
{
    protected static $internal_cache;

    public static function getListing(): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        return $OSCOM_PDO->call('GetListing');
    }

    public static function getLatest(): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        return $OSCOM_PDO->call('GetLatest');
    }

    public static function get($id, $key = null)
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (!isset(static::$internal_cache[$id])) {
            static::$internal_cache[$id] = $OSCOM_PDO->call('Get', ['id' => $id]);
        }

        return isset($key) ? static::$internal_cache[$id][$key] : static::$internal_cache[$id];
    }

    public static function exists($id): bool
    {
        if (!isset(static::$internal_cache[$id])) {
            static::get($id);
        }

        return isset(static::$internal_cache[$id]) && !empty(static::$internal_cache[$id]);
    }

    public static function getUrl(int $id): string
    {
        return OSCOM::getLink('Website', 'Us', 'News=' . $id);
    }
}
