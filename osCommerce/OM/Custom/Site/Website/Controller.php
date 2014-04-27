<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website;

  use osCommerce\OM\Core\Cache;
  use osCommerce\OM\Core\HTML;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\PDO;
  use osCommerce\OM\Core\Registry;

  use osCommerce\OM\Core\Site\Website\Session;

  class Controller implements \osCommerce\OM\Core\SiteInterface {
    protected static $_default_application = 'Index';

    public static function initialize() {
      Registry::set('MessageStack', new MessageStack());
      Registry::set('Cache', new Cache());
      Registry::set('PDO', PDO::initialize());
      Registry::set('Session', Session::load());

      $OSCOM_Session = Registry::get('Session');
      $OSCOM_Session->setLifeTime(3600);

      if ( !OSCOM::isRPC() ) {
        if ( isset($_COOKIE[$OSCOM_Session->getName()]) ) {
          $OSCOM_Session->start();
          Registry::get('MessageStack')->loadFromSession();

          if ( !isset($_SESSION[OSCOM::getSite()]['Account']) && (OSCOM::getSiteApplication() != 'Account') ) {
            $OSCOM_Session->kill();
          }
        }

        if ( !isset($_SESSION[OSCOM::getSite()]['Account']) ) {
          if ( isset($_COOKIE['member_id']) && is_numeric($_COOKIE['member_id']) && ($_COOKIE['member_id'] > 0) && isset($_COOKIE['pass_hash']) && (strlen($_COOKIE['pass_hash']) == 32) ) {
            $user = Invision::canAutoLogin($_COOKIE['member_id'], $_COOKIE['pass_hash']);

            if ( is_array($user) && isset($user['id']) && ($user['verified'] === true) && ($user['banned'] === false) ) {
              if ( !$OSCOM_Session->hasStarted() ) {
                $OSCOM_Session->start();
                Registry::get('MessageStack')->loadFromSession();
              }

              $_SESSION[OSCOM::getSite()]['Account'] = $user;

              $OSCOM_Session->recreate();
            } else {
              OSCOM::setCookie('member_id', '', time() - 31536000, null, null, false, true);
              OSCOM::setCookie('pass_hash', '', time() - 31536000, null, null, false, true);
            }
          }
        }
      }

      Registry::set('Language', new Language());
      Registry::set('Template', new Template());

      $OSCOM_Template = Registry::get('Template');
      $OSCOM_Language = Registry::get('Language');

      $OSCOM_Template->addHtmlTag('dir', $OSCOM_Language->getTextDirection());
      $OSCOM_Template->addHtmlTag('lang', OSCOM::getDef('html_lang_code')); // HPDL A better solution is to define the ISO 639-1 value at the language level

      $OSCOM_Template->addHtmlHeaderTag('<meta name="generator" content="osCommerce Website v' . HTML::outputProtected(OSCOM::getVersion(OSCOM::getSite())) . '" />');

      $application = 'osCommerce\\OM\\Core\\Site\\Website\\Application\\' . OSCOM::getSiteApplication() . '\\Controller';
      Registry::set('Application', new $application());
      $OSCOM_Template->setApplication(Registry::get('Application'));

      $OSCOM_Template->setValue('html_tags', $OSCOM_Template->getHtmlTags());
      $OSCOM_Template->setValue('html_character_set', $OSCOM_Language->getCharacterSet());
      $OSCOM_Template->setValue('html_page_title', $OSCOM_Template->getPageTitle());
      $OSCOM_Template->setValue('html_page_contents_file', $OSCOM_Template->getPageContentsFile());
      $OSCOM_Template->setValue('html_base_href', $OSCOM_Template->getBaseUrl());
      $OSCOM_Template->setValue('html_header_tags', $OSCOM_Template->getHtmlHeaderTags());
      $OSCOM_Template->setValue('site_req', [ 'site' => OSCOM::getSite(), 'app' => OSCOM::getSiteApplication(), 'actions' => Registry::get('Application')->getActionsRun() ]);
      $OSCOM_Template->setValue('site_version', OSCOM::getVersion(OSCOM::getSite()));
      $OSCOM_Template->setValue('current_year', date('Y'));
      $OSCOM_Template->setValue('in_ssl', OSCOM::getRequestType() == 'SSL');

      if ( isset($_SESSION[OSCOM::getSite()]['Account']) ) {
        $OSCOM_Template->setValue('user', $_SESSION[OSCOM::getSite()]['Account']);
      }
    }

    public static function getDefaultApplication() {
      return self::$_default_application;
    }

    public static function hasAccess($application) {
      return true;
    }
  }
?>
