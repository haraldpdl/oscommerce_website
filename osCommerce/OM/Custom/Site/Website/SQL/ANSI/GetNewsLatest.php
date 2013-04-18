<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

  use osCommerce\OM\Core\Registry;

  class GetNewsLatest {
    public static function execute() {
      $OSCOM_PDO = Registry::get('PDO');

      $Qnews = $OSCOM_PDO->query('select id, title, date_added from :table_website_news where status = 1 order by date_added desc, title limit 1');
      $Qnews->setCache('website-news-listing-latest_slim');
      $Qnews->execute();

      return $Qnews->fetch();
    }
  }
?>
