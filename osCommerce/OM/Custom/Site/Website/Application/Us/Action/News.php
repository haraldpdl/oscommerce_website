<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Us\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    HTML,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\News as NewsClass;

class News
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        if (!empty($_GET['News']) && is_numeric($_GET['News']) && NewsClass::exists($_GET['News'])) {
            $news_entry = NewsClass::get($_GET['News']);

            $application->setPageContent('news_entry.html');
            $application->setPageTitle(OSCOM::getDef('news_entry_html_page_title', [':news_title' => $news_entry['title']]));

            $OSCOM_Template->setValue('news_entry', $news_entry);

            $OSCOM_Template->addHtmlElement('header', '<link rel="canonical" href="' . HTML::outputProtected(OSCOM::getLink(null, null, 'News=' . $news_entry['id'])) . '">');

            $short_body = wordwrap(str_replace("\n", '', strip_tags($news_entry['body'])), 200, "\n");

            if (strpos($short_body, "\n") !== false) {
                $short_body = substr($short_body, 0, strpos($short_body, "\n")) . '...';
            }

            $OSCOM_Template->addHtmlElement('header', '<meta name="description" content="' . HTML::outputProtected($short_body) . '">');

/* Twitter Card - Summary */

            $OSCOM_Template->addHtmlElement('header', '<meta name="twitter:card" content="summary">');
            $OSCOM_Template->addHtmlElement('header', '<meta name="twitter:site" content="@osCommerce">');

            if (!empty($news_entry['author_twitter_id'])) {
                $OSCOM_Template->addHtmlElement('header', '<meta name="twitter:creator" content="@' . HTML::outputProtected($news_entry['author_twitter_id']) . '">');
            }

        /* The following are taken care of by Open Graph
            $OSCOM_Template->addHtmlElement('header', '<meta name="twitter:title" content="' . HTML::outputProtected($news_entry['title']) . '">');
            $OSCOM_Template->addHtmlElement('header', '<meta name="twitter:description" content="' . HTML::outputProtected($short_body) . '">');

            if (!empty($news_entry['image'])) {
                $OSCOM_Template->addHtmlElement('header', '<meta name="twitter:image:src" content="' . HTML::outputProtected($OSCOM_Template->getBaseUrl() . OSCOM::getPublicSiteLink('images/news/' . $news_entry['image'])) . '">');
            }
        */

/* Open Graph */

            $OSCOM_Template->addHtmlTag('prefix', 'og: http://ogp.me/ns#');
            $OSCOM_Template->addHtmlTag('prefix', 'article: http://ogp.me/ns/article#');
            $OSCOM_Template->addHtmlTag('prefix', 'profile: http://ogp.me/ns/profile#');

            $OSCOM_Template->addHtmlElement('header', '<meta property="og:type" content="article">');
            $OSCOM_Template->addHtmlElement('header', '<meta property="og:site_name" content="osCommerce">');
            $OSCOM_Template->addHtmlElement('header', '<meta property="og:title" content="' . HTML::outputProtected($news_entry['title']) . '">');
            $OSCOM_Template->addHtmlElement('header', '<meta property="og:description" content="' . HTML::outputProtected($short_body) . '">');
            $OSCOM_Template->addHtmlElement('header', '<meta property="og:url" content="' . HTML::outputProtected(OSCOM::getLink(null, null, 'News=' . $news_entry['id'])) . '">');

            if (!empty($news_entry['image'])) {
                $OSCOM_Template->addHtmlElement('header', '<meta property="og:image" content="' . HTML::outputProtected($OSCOM_Template->getBaseUrl() . OSCOM::getPublicSiteLink('images/news/' . $news_entry['image'])) . '">');
            }

            $OSCOM_Template->addHtmlElement('header', '<meta property="article:author" content="https://forums.oscommerce.com/index.php?showuser=' . HTML::outputProtected($news_entry['author_id']) . '">');

            $author_name_array = explode(' ', $news_entry['author_name'], 2);

            $OSCOM_Template->addHtmlElement('header', '<meta property="profile:first_name" content="' . HTML::outputProtected($author_name_array[0]) . '">');

            if (isset($author_name_array[1]) && !empty($author_name_array[1])) {
                $OSCOM_Template->addHtmlElement('header', '<meta property="profile:last_name" content="' . HTML::outputProtected($author_name_array[1]) . '">');
            }
        } else {
            $application->setPageContent('news.html');
            $application->setPageTitle(OSCOM::getDef('news_html_page_title'));

            $OSCOM_Template->setValue('news_listing', NewsClass::getListing());
        }
    }
}
