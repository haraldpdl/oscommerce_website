<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Us\Action;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\HTML;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;
  use osCommerce\OM\Core\Site\Website\News as NewsClass;

  class News {
    public static function execute(ApplicationAbstract $application) {
      $OSCOM_Template = Registry::get('Template');

      if ( !empty($_GET['News']) && is_numeric($_GET['News']) && NewsClass::exists($_GET['News']) ) {
        $news_entry = NewsClass::get($_GET['News']);

        $application->setPageContent('news_entry.html');
        $application->setPageTitle(OSCOM::getDef('news_entry_html_page_title', array(':news_title' => $news_entry['title'])));

        $OSCOM_Template->setValue('news_entry', $news_entry);

        $OSCOM_Template->addHtmlHeaderTag('<link rel="canonical" href="' . HTML::outputProtected(OSCOM::getLink(null, null, 'News=' . $news_entry['id'])) . '" />');

        $body_raw = wordwrap(str_replace("\n", '', strip_tags($news_entry['body'])), 200, "\n");
        $short_body = substr($body_raw, 0, strpos($body_raw, "\n")) . '...';

        $OSCOM_Template->addHtmlHeaderTag('<meta name="description" content="' . HTML::outputProtected($short_body) . '" />');

/* Twitter Card - Summary */

        $OSCOM_Template->addHtmlHeaderTag('<meta name="twitter:card" content="summary" />');
        $OSCOM_Template->addHtmlHeaderTag('<meta name="twitter:site" content="@osCommerce" />');

        if ( !empty($news_entry['author_twitter_id']) ) {
          $OSCOM_Template->addHtmlHeaderTag('<meta name="twitter:creator" content="@' . HTML::outputProtected($news_entry['author_twitter_id']) . '" />');
        }

// The following are taken care of by Open Graph
//        $OSCOM_Template->addHtmlHeaderTag('<meta name="twitter:title" content="' . HTML::outputProtected($news_entry['title']) . '" />');
//        $OSCOM_Template->addHtmlHeaderTag('<meta name="twitter:description" content="' . HTML::outputProtected($short_body) . '" />');

//        if ( !empty($news_entry['image']) ) {
//          $OSCOM_Template->addHtmlHeaderTag('<meta name="twitter:image:src" content="' . HTML::outputProtected($OSCOM_Template->getBaseUrl() . OSCOM::getPublicSiteLink('images/news/' . $news_entry['image'])) . '" />');
//        }

/* Open Graph */

        $OSCOM_Template->addHtmlTag('prefix', 'og: http://ogp.me/ns#');
        $OSCOM_Template->addHtmlTag('prefix', 'article: http://ogp.me/ns/article#');
        $OSCOM_Template->addHtmlTag('prefix', 'profile: http://ogp.me/ns/profile#');

        $OSCOM_Template->addHtmlHeaderTag('<meta property="og:type" content="article" />');
        $OSCOM_Template->addHtmlHeaderTag('<meta property="og:site_name" content="osCommerce" />');
        $OSCOM_Template->addHtmlHeaderTag('<meta property="og:title" content="' . HTML::outputProtected($news_entry['title']) . '" />');
        $OSCOM_Template->addHtmlHeaderTag('<meta property="og:description" content="' . HTML::outputProtected($short_body) . '" />');
        $OSCOM_Template->addHtmlHeaderTag('<meta property="og:url" content="' . HTML::outputProtected(OSCOM::getLink(null, null, 'News=' . $news_entry['id'])) . '" />');

        if ( !empty($news_entry['image']) ) {
          $OSCOM_Template->addHtmlHeaderTag('<meta property="og:image" content="' . HTML::outputProtected($OSCOM_Template->getBaseUrl() . OSCOM::getPublicSiteLink('images/news/' . $news_entry['image'])) . '" />');
        }

        $OSCOM_Template->addHtmlHeaderTag('<meta property="article:author" content="http://forums.oscommerce.com/index.php?showuser=' . HTML::outputProtected($news_entry['author_id']) . '" />');

        $author_name_array = explode(' ', $news_entry['author_name'], 2);

        $OSCOM_Template->addHtmlHeaderTag('<meta property="profile:first_name" content="' . HTML::outputProtected($author_name_array[0]) . '" />');

        if ( isset($author_name_array[1]) && !empty($author_name_array[1]) ) {
          $OSCOM_Template->addHtmlHeaderTag('<meta property="profile:last_name" content="' . HTML::outputProtected($author_name_array[1]) . '" />');
        }
      } else {
        $application->setPageContent('news.html');
        $application->setPageTitle(OSCOM::getDef('news_html_page_title'));

        $OSCOM_Template->setValue('news_listing', NewsClass::getListing());
      }
    }
  }
?>
