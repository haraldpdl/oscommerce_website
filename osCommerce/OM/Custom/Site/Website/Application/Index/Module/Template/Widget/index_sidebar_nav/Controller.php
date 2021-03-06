<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\Module\Template\Widget\index_sidebar_nav;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Invision;

class Controller extends \osCommerce\OM\Core\Template\WidgetAbstract
{
    public static function execute($param = null)
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_Template = Registry::get('Template');

        if ($OSCOM_Template->valueExists('stats_addons', false) === false) {
            $OSCOM_Template->setValue('stats_addons', $OSCOM_Language->formatNumber(static::getTotalApps(), 0));
        }

        $OSCOM_Template->setValue('stats_sites', $OSCOM_Language->formatNumber(static::getTotalSites(), 0));
        $OSCOM_Template->setValue('stats_community_online_users', $OSCOM_Language->formatNumber(static::getOnlineUsers(), 0));

        if ($OSCOM_Template->valueExists('stats_community_total_users', false) === false) {
            $OSCOM_Template->setValue('stats_community_total_users', $OSCOM_Language->formatNumber(static::getTotalUsers(), 0));
        }

        $OSCOM_Template->setValue('stats_community_total_forum_postings', $OSCOM_Language->formatNumber(static::getTotalForumPostings() / 1000000, 1));

        $file = OSCOM::BASE_DIRECTORY . 'Custom/Site/' . OSCOM::getSite() . '/Application/' . OSCOM::getSiteApplication() . '/Module/Template/Widget/index_sidebar_nav/pages/main.html';

        if (!file_exists($file)) {
            $file = OSCOM::BASE_DIRECTORY . 'Core/Site/' . OSCOM::getSite() . '/Application/' . OSCOM::getSiteApplication() . '/Module/Template/Widget/index_sidebar_nav/pages/main.html';
        }

        return file_get_contents($file);
    }

    public static function getTotalApps()
    {
        $OSCOM_Cache = Registry::get('Cache');

        $data = null;
        $apps = 7800;

        if ($OSCOM_Cache->read('stats_total_apps', 1440)) {
            $data = $OSCOM_Cache->getCache();
        } else {
            $PDO_OLD = Registry::get('PDO_OLD');

            $Qa = $PDO_OLD->get('contrib_packages', 'count(*) as total', null, null, null, ['prefix_tables' => false]);

            $data = $Qa->valueInt('total');

            if (is_int($data) && ($data > 0)) {
                $OSCOM_Cache->write($data);
            }
        }

        if (is_int($data) && ($data > 0)) {
            $apps = $data;
        }

        return $apps;
    }

    public static function getTotalSites()
    {
        $OSCOM_Cache = Registry::get('Cache');
        $OSCOM_PDO = Registry::get('PDO');

        $data = null;
        $sites = 20000;

        if ($OSCOM_Cache->read('stats_total_sites', 1440)) {
            $data = $OSCOM_Cache->getCache();
        } else {
            $Qs = $OSCOM_PDO->get('website_live_shops', 'count(*) as total');

            $data = $Qs->valueInt('total');

            if (is_int($data) && ($data > 0)) {
                $OSCOM_Cache->write($data);
            }
        }

        if (is_int($data) && ($data > 0)) {
            $sites = $data;
        }

        return $sites;
    }

    public static function getOnlineUsers()
    {
        $OSCOM_Cache = Registry::get('Cache');

        $data = null;
        $users = Invision::DEFAULT_TOTAL_ONLINE_USERS;

        if ($OSCOM_Cache->read('stats_online_users', 60)) {
            $data = $OSCOM_Cache->getCache();
        } else {
            $data = Invision::getTotalOnlineUsers();

            if (is_int($data) && ($data > 0)) {
                $OSCOM_Cache->write($data);
            }
        }

        if (is_int($data) && ($data > 0)) {
            $users = $data;
        }

        return $users;
    }

    public static function getTotalUsers()
    {
        $OSCOM_Cache = Registry::get('Cache');

        $data = null;
        $users = Invision::DEFAULT_TOTAL_USERS;

        if ($OSCOM_Cache->read('stats_total_users', 1440)) {
            $data = $OSCOM_Cache->getCache();
        } else {
            $data = Invision::getTotalUsers();

            if (is_int($data) && ($data > 0)) {
                $OSCOM_Cache->write($data);
            }
        }

        if (is_int($data) && ($data > 0)) {
            $users = $data;
        }

        return $users;
    }

    public static function getTotalForumPostings()
    {
        $OSCOM_Cache = Registry::get('Cache');

        $data = null;
        $posts = Invision::DEFAULT_TOTAL_POSTINGS;

        if ($OSCOM_Cache->read('stats_total_postings', 10080)) {
            $data = $OSCOM_Cache->getCache();
        } else {
            $data = Invision::getTotalPostings();

            if (is_int($data) && ($data > 0)) {
                $OSCOM_Cache->write($data);
            }
        }

        if (is_int($data) && ($data > 0)) {
            $posts = $data;
        }

        return $posts;
    }
}
