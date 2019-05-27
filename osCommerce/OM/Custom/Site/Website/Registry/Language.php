<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Registry;

use osCommerce\OM\Core\Site\Website\Language as OSCOM_Language;

class Language extends \osCommerce\OM\Core\RegistryAbstract
{
    public function __construct()
    {
        $this->value = new OSCOM_Language();
    }
}
