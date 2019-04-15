<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Scripts\Rss;

use osCommerce\OM\Core\{
    HTML,
    OSCOM,
    SimpleXMLElement
};

use osCommerce\OM\Core\Site\Website\News as NewsClass;

class News implements \osCommerce\OM\Core\RunScriptInterface
{
    public static function execute()
    {
        OSCOM::initialize('Website');

        $feed = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" />');

        $channel = $feed->addChild('channel');

        $channel->addChild('title', 'osCommerce News');
        $channel->addChild('language', 'en');
        $channel->addChild('link', 'https://www.oscommerce.com');
        $channel->addChild('description', 'osCommerce News Announcements');
        $channel->addChild('copyright', 'Copyright (c) ' . date('Y') . ' osCommerce');
        $channel->addChild('managingEditor', 'hpdl@oscommerce.com');
        $channel->addChild('pubDate', (new \DateTime('now'))->format(\DateTimeInterface::RSS));

        $image = $channel->addChild('image');

        $image->addChild('title', 'osCommerce');
        $image->addChild('url', 'https://www.oscommerce.com/public/sites/Website/images/oscommerce.png');
        $image->addChild('link', 'https://www.oscommerce.com');

        $counter = 0;

        foreach (NewsClass::getListing() as $n) {
            $item = $channel->addChild('item');

            $item->addChildCData('title', HTML::sanitize($n['title']));
            $item->addChild('link', 'https://www.oscommerce.com/Us&amp;News=' . $n['id']);

            $news = NewsClass::get($n['id']);

            $article = nl2br($news['body']);

            if (!empty($news['image'])) {
                $article = '<img src="https://www.oscommerce.com/' . OSCOM::getPublicSiteLink('images/news/' . $news['image']) . '" alt="" />' . "\n" . $article;
            }

            $item->addChildCData('description', $article);
            $item->addChild('pubDate', (new \DateTime($n['date_added']))->format(\DateTimeInterface::RSS));
            $item->addChild('guid', 'https://www.oscommerce.com/Us&amp;News=' . $n['id']);

            $counter++;

            if ($counter === 15) {
                break;
            }
        }

        if ($counter > 0) {
            file_put_contents(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/rss/news.xml', $feed->asXML(), LOCK_EX);
        } else {
            throw new \Exception('(Rss\News) Entries are less than 15: ' . $counter);
        }
    }
}
