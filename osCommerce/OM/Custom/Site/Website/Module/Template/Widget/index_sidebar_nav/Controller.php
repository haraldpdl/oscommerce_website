<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2012 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Module\Template\Widget\index_sidebar_nav;

  use osCommerce\OM\Core\HttpRequest;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  class Controller extends \osCommerce\OM\Core\Template\WidgetAbstract {
    static public function execute($param = null) {
      $OSCOM_Language = Registry::get('Language');
      $OSCOM_Template = Registry::get('Template');

      if ($OSCOM_Template->valueExists('stats_addons', false) === false) {
        $OSCOM_Template->setValue('stats_addons', $OSCOM_Language->formatNumber(static::getTotalAddOns(), 0));
      }

      $OSCOM_Template->setValue('stats_sites', $OSCOM_Language->formatNumber(13300, 0));
      $OSCOM_Template->setValue('stats_community_online_users', $OSCOM_Language->formatNumber(static::getOnlineUsers(), 0));

      if ($OSCOM_Template->valueExists('stats_community_total_users', false) === false) {
        $OSCOM_Template->setValue('stats_community_total_users', $OSCOM_Language->formatNumber(static::getTotalUsers(), 0));
      }

      $OSCOM_Template->setValue('stats_community_total_forum_postings', $OSCOM_Language->formatNumber(static::getTotalForumPostings(), 1));

      $file = OSCOM::BASE_DIRECTORY . 'Custom/Site/' . OSCOM::getSite() . '/Module/Template/Widget/index_sidebar_nav/pages/main.html';

      if ( !file_exists($file) ) {
        $file = OSCOM::BASE_DIRECTORY . 'Core/Site/' . OSCOM::getSite() . '/Module/Template/Widget/index_sidebar_nav/pages/main.html';
      }

      return file_get_contents($file);
    }

    static public function getTotalAddOns() {
      return 7700;
    }

    static public function getOnlineUsers() {
      $OSCOM_Cache = Registry::get('Cache');

      $data = null;
      $users = 700;

      if ( OSCOM::configExists('community_api_key') ) {
        if ( $OSCOM_Cache->read('stats_community_api_fetchOnlineUsers', 60) ) {
          $data = $OSCOM_Cache->getCache();
        } else {
          $request = xmlrpc_encode_request('fetchOnlineUsers', array('api_key' => OSCOM::getConfig('community_api_key'),
                                                                     'api_module' => OSCOM::getConfig('community_api_module')));

          $data = xmlrpc_decode(HttpRequest::getResponse(array('url' => OSCOM::getConfig('community_api_address'),
                                                               'parameters' => $request)));

          if ( is_array($data) && !empty($data) && isset($data['TOTAL']) ) {
            $OSCOM_Cache->write($data);
          }
        }

        if ( isset($data) ) {
          $users = (int)str_replace(',', '', $data['TOTAL']);
        }
      }

      return $users;
    }

    static public function getTotalUsers() {
      $OSCOM_Cache = Registry::get('Cache');

      $data = null;
      $users = 280000;

      if ( OSCOM::configExists('community_api_key') ) {
        if ( $OSCOM_Cache->read('stats_community_api_fetchStats', 1440) ) {
          $data = $OSCOM_Cache->getCache();
        } else {
          $request = xmlrpc_encode_request('fetchStats', array('api_key' => OSCOM::getConfig('community_api_key'),
                                                               'api_module' => OSCOM::getConfig('community_api_module')));

          $data = xmlrpc_decode(HttpRequest::getResponse(array('url' => OSCOM::getConfig('community_api_address'),
                                                               'parameters' => $request)));

          if ( is_array($data) && !empty($data) && isset($data['total_members']) ) {
            $OSCOM_Cache->write($data);
          }
        }

        if ( isset($data) ) {
          $users = (int)str_replace(',', '', $data['total_members']);
        }
      }

      return $users;
    }

    static public function getTotalForumPostings() {
      return 1600000/1000000;

      $OSCOM_Cache = Registry::get('Cache');

      $data = null;
      $posts = 1600000;

      if ( OSCOM::configExists('community_api_key') ) {
        if ( $OSCOM_Cache->read('stats_community_api_fetchStats', 1440) ) {
          $data = $OSCOM_Cache->getCache();
        } else {
          $request = xmlrpc_encode_request('fetchStats', array('api_key' => OSCOM::getConfig('community_api_key'),
                                                               'api_module' => OSCOM::getConfig('community_api_module')));

          $data = xmlrpc_decode(HttpRequest::getResponse(array('url' => OSCOM::getConfig('community_api_address'),
                                                               'parameters' => $request)));

          if ( is_array($data) && !empty($data) && isset($data['total_posts']) ) {
            $OSCOM_Cache->write($data);
          }
        }

        if ( isset($data) ) {
          $posts = (int)str_replace(',', '', $data['total_posts']);
        }
      }

      return $posts;
    }
  }
?>
