<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

  use osCommerce\OM\Core\OSCOM;

  use osCommerce\OM\Core\Site\Website\News;

  class GetNewsLatest {
    public static function execute() {
      $news = News::getLatest();

      $result = array('title' => $news['title'],
                      'url' => OSCOM::getLink(null, 'Us', 'News=' . $news['id'], 'NONSSL', false));

      header('Content-Type: application/javascript');

      $json = json_encode($result);

      $output = <<<JAVASCRIPT
var oscNews = $json

jQuery(function() {
  jQuery('#latest_news_content').html('<a href="' + oscNews.url + '" target="_blank">' + oscNews.title + '</a>');
});
JAVASCRIPT;

      echo $output;
    }
  }
?>
