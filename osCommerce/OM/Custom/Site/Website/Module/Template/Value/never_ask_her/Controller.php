<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Template\Value\never_ask_her;

class Controller extends \osCommerce\OM\Core\Template\ValueAbstract
{
    public static function execute()
    {
        return (new \DateTime('2000-03-12'))->diff(new \DateTime())->format('%y');
    }
}
