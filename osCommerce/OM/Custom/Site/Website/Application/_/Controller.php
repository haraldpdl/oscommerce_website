<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\_;

use osCommerce\OM\Core\OSCOM;

class Controller extends \osCommerce\OM\Core\Site\Website\ApplicationAbstract
{
    protected function initialize()
    {
        $this->_page_contents = 'main.html';
    }

    public function runActions()
    {
        if (!OSCOM::isRPC()) {
            parent::runActions();

            if ($this->getCurrentAction() === false) {
                OSCOM::redirect(OSCOM::getLink(null, 'Index'));
            }
        }
    }
}
