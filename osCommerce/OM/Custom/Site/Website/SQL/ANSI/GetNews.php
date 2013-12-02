<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

  use osCommerce\OM\Core\Registry;

  class GetNews {
    public static function execute($data) {
      $OSCOM_PDO = Registry::get('PDO');

      $Qnews = $OSCOM_PDO->prepare('select n.id, n.title, n.body, n.date_added, date_format(n.date_added, "%D %M %Y") as date_added_formatted, n.image, u.id as author_id, u.display_name as author_name, u.twitter_id as author_twitter_id, u.google_plus_id as author_google_plus_id, u.facebook_id as author_facebook_id, u.github_id as author_github_id from :table_website_news n left join :table_website_user_profiles u on (n.author_id = u.id) where n.id = :id and n.status = 1');
      $Qnews->bindInt(':id', $data['id']);
      $Qnews->setCache('website-news-' . $data['id']);
      $Qnews->execute();

      return $Qnews->fetch();
    }
  }
?>
