<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2012 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Module\Template\Widget\latest_news_article;

  use osCommerce\OM\Core\Cache;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;
  use osCommerce\OM\Core\Site\Website\News;

  class Controller extends \osCommerce\OM\Core\Template\WidgetAbstract {
    static public function execute($param = null) {
      $OSCOM_Template = Registry::get('Template');

      $file = OSCOM::BASE_DIRECTORY . 'Custom/Site/' . OSCOM::getSite() . '/Module/Template/Widget/latest_news_article/pages/main.html';

      if ( !file_exists($file) ) {
        $file = OSCOM::BASE_DIRECTORY . 'Core/Site/' . OSCOM::getSite() . '/Module/Template/Widget/latest_news_article/pages/main.html';
      }

      $OSCOM_Cache = new Cache();

      if ( $OSCOM_Cache->read('website-news-listing-latest') ) {
        $data = $OSCOM_Cache->getCache();
      } else {
        $news = News::getListing();

        $data = $news[0];

        $OSCOM_Cache->write($data);
      }

      $OSCOM_Template->setValue('latest_news_article', $data);

      return file_get_contents($file);
    }
  }
?>
