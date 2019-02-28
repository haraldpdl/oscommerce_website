<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Products;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Download;
use osCommerce\OM\Core\Site\Website\Application\Index\Module\Template\Widget\index_sidebar_nav\Controller as WidgetIndexSidebar;

class Controller extends \osCommerce\OM\Core\Site\Website\ApplicationAbstract
{
    protected function initialize()
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_Template = Registry::get('Template');

        $OSCOM_Template->setValue('releases_oscom2_latest', Download::getAll('oscom2', 'latest'));
        $OSCOM_Template->setValue('releases_oscom24_beta', Download::getAll('oscom2', 'beta'));
//        $OSCOM_Template->setValue('releases_oscom2_earlier', Download::getAll('oscom2', 'earlier'));
//        $OSCOM_Template->setValue('releases_oscom3_latest', Download::getAll('oscom3', 'latest'));
//        $OSCOM_Template->setValue('releases_oscom3_earlier', Download::getAll('oscom3', 'earlier'));
        $OSCOM_Template->setValue('releases_archive_oscom', Download::getAll('archive', 'oscom'));
        $OSCOM_Template->setValue('releases_archive_tep', Download::getAll('archive', 'tep'));

        if ($OSCOM_Template->valueExists('stats_addons', false) === false) {
            $OSCOM_Template->setValue('stats_addons', $OSCOM_Language->formatNumber(WidgetIndexSidebar::getTotalApps(), 0));
        }

        $this->_page_contents = 'main.html';
        $this->_page_title = OSCOM::getDef('products_html_page_title');

        if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/products.jpg')) {
            $OSCOM_Template->setValue('highlights_image', 'images/products.jpg');
        } else {
            $OSCOM_Template->setValue('highlights_image', 'images/940x285.gif');
        }
    }
}
