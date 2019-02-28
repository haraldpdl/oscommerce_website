<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

abstract class ApplicationAbstract extends \osCommerce\OM\Core\ApplicationAbstract
{
    public function __construct()
    {
        $this->initialize();
    }
}
