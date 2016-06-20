<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website;

abstract class ApplicationAbstract extends \osCommerce\OM\Core\ApplicationAbstract
{
    public function __construct()
    {
        $this->initialize();
    }
}
