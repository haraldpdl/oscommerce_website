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

      header('Cache-Control: max-age=10800, must-revalidate');
      header_remove('Pragma');
      header('Content-Type: application/javascript');

      $json = json_encode($result);

      if (isset($_GET['format']) && ($_GET['format'] = 'jquery')) {
        $output = <<<JAVASCRIPT
var oscNews = $json

$(function() {
  $('#latest_news_content').html('<a href="' + oscNews.url + '" target="_blank">' + oscNews.title + '</a>');
});
JAVASCRIPT;
      } else {
        $output = <<<JAVASCRIPT
var oscNews = $json

document.observe('dom:loaded', function() {
  $('latest_news_content').update('<a href="' + oscNews.url + '" target="_blank">' + oscNews.title + '</a>');
});
JAVASCRIPT;
      }

      echo $output;
    }
  }
?>
