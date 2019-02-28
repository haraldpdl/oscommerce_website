<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Template\Widget\latest_news_article;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\News;

class Controller extends \osCommerce\OM\Core\Template\WidgetAbstract
{
    public static function execute($param = null)
    {
        $OSCOM_Template = Registry::get('Template');

        $file = OSCOM::BASE_DIRECTORY . 'Custom/Site/' . OSCOM::getSite() . '/Module/Template/Widget/latest_news_article/pages/main.html';

        if (!file_exists($file)) {
            $file = OSCOM::BASE_DIRECTORY . 'Core/Site/' . OSCOM::getSite() . '/Module/Template/Widget/latest_news_article/pages/main.html';
        }

        $OSCOM_Template->setValue('latest_news_article', News::getLatest());

        return file_get_contents($file);
    }
}
