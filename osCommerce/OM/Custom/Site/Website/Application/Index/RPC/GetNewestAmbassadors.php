<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\Site\Website\Users;

class GetNewestAmbassadors
{
    public static function execute()
    {
        echo json_encode(Users::getNewestAmbassadors(4));
    }
}
