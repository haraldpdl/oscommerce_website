<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Us;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
};

class Controller extends \osCommerce\OM\Core\Site\Website\ApplicationAbstract
{
    protected function initialize()
    {
        $OSCOM_Template = Registry::get('Template');

        $this->_page_contents = 'main.html';
        $this->_page_title = OSCOM::getDef('us_html_page_title');

        if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/highlights/us.jpg')) {
            $OSCOM_Template->setValue('highlights_image', 'images/highlights/us.jpg');
        } else {
            $OSCOM_Template->setValue('highlights_image', 'images/940x285.gif');
        }
    }
}
