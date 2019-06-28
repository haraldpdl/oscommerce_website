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

use osCommerce\OM\Core\Site\Apps\Apps as AppsClass;

class Apps implements \osCommerce\OM\Core\RunScriptInterface
{
    public static function execute()
    {
        OSCOM::initialize('Apps');

        $feed = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" />');

        $channel = $feed->addChild('channel');

        $channel->addChild('title', 'osCommerce Apps');
        $channel->addChild('language', 'en');
        $channel->addChild('link', 'https://apps.oscommerce.com');
        $channel->addChild('description', 'osCommerce Apps Marketplace Listing');
        $channel->addChild('copyright', 'Copyright (c) ' . date('Y') . ' osCommerce');
        $channel->addChild('managingEditor', 'hpdl@oscommerce.com');
        $channel->addChild('pubDate', (new \DateTime('now'))->format(\DateTimeInterface::RSS));

        $image = $channel->addChild('image');

        $image->addChild('title', 'osCommerce');
        $image->addChild('url', 'https://www.oscommerce.com/public/sites/Website/images/oscommerce.png');
        $image->addChild('link', 'https://www.oscommerce.com');

        $counter = 0;

        foreach (AppsClass::getListing()['entries'] as $a) {
            $pubDateTime = \DateTime::createFromFormat('Ymd His', $a['last_update_date']);

            $item = $channel->addChild('item');

            $item->addChildCData('title', HTML::sanitize($a['title']));
            $item->addChild('link', 'https://apps.oscommerce.com/' . $a['public_id']);
            $item->addChildCData('description', HTML::sanitize($a['short_description']));

            if ($pubDateTime !== false) {
                $item->addChild('pubDate', $pubDateTime->format(\DateTimeInterface::RSS));
            }

            $counter++;

            if ($counter === 15) {
                break;
            }
        }

        if ($counter > 0) {
            file_put_contents(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/rss/addons.xml', $feed->asXML(), LOCK_EX);
        } else {
            throw new \Exception('(Rss\Apps) Entries are less than 15: ' . $counter);
        }
    }
}
