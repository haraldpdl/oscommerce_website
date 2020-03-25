<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\OSCOM;

class Session extends \osCommerce\OM\Core\Session
{
    public static function load($name = null)
    {
        if (OSCOM::configExists('store_sessions', 'Website')) {
            static::$driver = OSCOM::getConfig('store_sessions', 'Website');
        }

        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_secure', (OSCOM::getConfig('enable_ssl', 'Website') === 'true') ? '1' : '0');
        ini_set('session.cookie_httponly', '1');

        return parent::load($name);
    }
}
