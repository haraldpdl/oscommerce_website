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

  class CreateNewsRssFeed {
    public static function execute() {
      $OSCOM_PDO = Registry::get('PDO');

      $result = array();

      if ( isset($_POST['key']) && ($_POST['key'] == OSCOM::getConfig('cron_key')) ) {
        $news = '';

        $Qnews = $OSCOM_PDO->query('select id, title, body, date_format(date_added, "%a, %d %b %Y %H:%i:%s -0400") as date_added_formatted from :table_website_news where status = 1 order by date_added desc limit 5');

        while ( $Qnews->fetch() ) {
          $news .= '    <item>' . "\n" .
                   '      <title>' . htmlentities($Qnews->value('title')) . '</title>' . "\n" .
                   '      <link>http://www.oscommerce.com/index.php?Us&News=' . $Qnews->valueInt('id') . '</link>' . "\n" .
                   '      <description><![CDATA[' . nl2br($Qnews->value('body')) . ']]></description>' . "\n" .
                   '      <pubDate>' . $Qnews->value('date_added_formatted') . '</pubDate>' . "\n" .
                   '      <guid>http://www.oscommerce.com/index.php?Us&News=' . $Qnews->valueInt('id') . '</guid>' . "\n" .
                   '    </item>' . "\n";
        }

        if ( !empty($news) ) {
          $rss = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
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

          if ( file_put_contents(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/rss/news.xml', $rss, LOCK_EX) !== false ) {
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
