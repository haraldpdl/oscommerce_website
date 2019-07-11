<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Registry;

use osCommerce\OM\Core\{
    Currency as OSCOM_Currency,
    OSCOM
};

class Currency extends \osCommerce\OM\Core\RegistryAbstract
{
    public function __construct()
    {
        $this->value = new OSCOM_Currency();

        if (isset($_COOKIE['oscom_currency'])) {
            if ($this->value->exists($_COOKIE['oscom_currency'])) {
                $this->value->setSelected($_COOKIE['oscom_currency']);

                if ($_COOKIE['oscom_currency'] === $this->value->getDefault(true)) {
                    $this->removeCookie();
                }
            } else {
                $this->removeCookie();
            }
        }
    }

    protected function removeCookie()
    {
        OSCOM::setCookie('oscom_currency', '', -1, null, null, true);
    }
}
