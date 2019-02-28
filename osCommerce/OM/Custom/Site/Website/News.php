<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\Registry;

class News
{
    protected static $_news;

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

        if (!isset(static::$_news[$id])) {
            static::$_news[$id] = $OSCOM_PDO->call('Get', ['id' => $id]);
        }

        return isset($key) ? static::$_news[$id][$key] : static::$_news[$id];
    }

    public static function exists($id): bool
    {
        if (!isset(static::$_news[$id])) {
            static::get($id);
        }

        return isset(static::$_news[$id]) && !empty(static::$_news[$id]);
    }
}
