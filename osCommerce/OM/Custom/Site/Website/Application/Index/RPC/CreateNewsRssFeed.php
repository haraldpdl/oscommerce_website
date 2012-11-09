<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2012 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;
  use osCommerce\OM\Core\Site\RPC\Controller as RPC;

  require(OSCOM::BASE_DIRECTORY . 'External/simplepie_1.3.1.mini.php');

  class CreateNewsRssFeed {
    public static function execute() {
      $OSCOM_PDO = Registry::get('PDO');

      $result = array();

      if ( isset($_POST['key']) && ($_POST['key'] == OSCOM::getConfig('cron_key')) ) {
        $news = '';
        $merge = '';

        $Qnews = $OSCOM_PDO->query('select id, title, body, date_format(date_added, "%a, %d %b %Y %H:%i:%s -0400") as date_added_formatted, image from :table_website_news where status = 1 order by date_added desc limit 5');

        while ( $Qnews->fetch() ) {
          $news_article = nl2br($Qnews->value('body'));

          if ( strlen($Qnews->value('image')) > 0 ) {
            $news_article = '<img src="http://www.oscommerce.com/' . OSCOM::getPublicSiteLink('images/news/' . $Qnews->value('image')) . '" alt="" />' . "\n" . $news_article;
          }

          $news .= '    <item>' . "\n" .
                   '      <title>' . htmlentities($Qnews->value('title')) . '</title>' . "\n" .
                   '      <link>http://www.oscommerce.com/Us&amp;News=' . $Qnews->valueInt('id') . '</link>' . "\n" .
                   '      <description><![CDATA[' . $news_article . ']]></description>' . "\n" .
                   '      <pubDate>' . $Qnews->value('date_added_formatted') . '</pubDate>' . "\n" .
                   '      <guid>http://www.oscommerce.com/Us&amp;News=' . $Qnews->valueInt('id') . '</guid>' . "\n" .
                   '    </item>' . "\n";
        }

        if ( !empty($news) ) {
          $news = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
                  '<rss version="2.0">' . "\n" .
                  '  <channel>' . "\n" .
                  '    <title>osCommerce News</title>' . "\n" .
                  '    <language>en</language>' . "\n" .
                  '    <description>Official osCommerce news and announcements</description>' . "\n" .
                  '    <link>http://www.oscommerce.com</link>' . "\n" .
                  '    <copyright>Copyright (c) ' . date('Y') . ' osCommerce</copyright>' . "\n" .
                  '    <image>' . "\n" .
                  '      <title>osCommerce</title>' . "\n" .
                  '      <url>http://www.oscommerce.com/images/oscommerce_88x31.gif</url>' . "\n" .
                  '      <link>http://www.oscommerce.com</link>' . "\n" .
                  '    </image>' . "\n" .
                  $news .
                  '  </channel>' . "\n" .
                  '</rss>' . "\n";

        }

        if ( !empty($news) ) {
          $feed = new \SimplePie();
          $feed->set_cache_location(OSCOM::BASE_DIRECTORY . 'Work/Cache');

          $feed->set_feed_url(array('http://feeds.feedburner.com/osCommerce',
                                    'http://feeds.feedburner.com/osCommerce_Blogs'));

          $success = $feed->init();

          $feed->handle_content_type();

          if ( $success ) {
            $merge = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>osCommerce News and Blog Announcements</title>
    <link>http://www.oscommerce.com</link>
    <atom:link href="http://www.oscommerce.com/public/sites/Website/rss/news_and_blogs.xml" rel="self" type="application/rss+xml" />
    <description>An aggregated feed of osCommerce news and blog announcements.</description>
    <pubDate>' . date('D, d M Y H:i:s O') . '</pubDate>
    <language>en-us</language>' . "\n";

            $counter = 0;

            foreach ( $feed->get_items() as $item ) {
              $merge .= '      <item>
        <title><![CDATA[' . $item->get_title() . ']]></title>
        <link>' . $item->get_permalink() . '</link>
        <guid>' . $item->get_permalink() . '</guid>
        <description><![CDATA[' . $item->get_description() . ']]></description>
        <pubDate>' . $item->get_date('D, d M Y H:i:s O') . '</pubDate>
      </item>' . "\n";

              $counter++;

              if ($counter >= 15) {
                break;
              }
            }

            $merge .= '  </channel>
        </rss>';
          }
        }

        if ( !empty($news) && !empty($merge) ) {
          if ( (file_put_contents(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/rss/news.xml', $news, LOCK_EX) !== false) && (file_put_contents(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/rss/news_and_blogs.xml', $merge, LOCK_EX) !== false) ) {
            $result['rpcStatus'] = RPC::STATUS_SUCCESS;
          }
        }
      }

      if ( !isset($result['rpcStatus']) ) {
        $result['rpcStatus'] = RPC::STATUS_NO_ACCESS;
      }

      echo json_encode($result);
    }
  }
?>
