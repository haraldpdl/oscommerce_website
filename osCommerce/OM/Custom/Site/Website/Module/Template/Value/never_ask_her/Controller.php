<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Template\Value\never_ask_her;

class Controller extends \osCommerce\OM\Core\Template\ValueAbstract
{
    static public function execute()
    {
        return (new \DateTime('2000-03-12'))->diff(new \DateTime())->format('%y');
    }
}
