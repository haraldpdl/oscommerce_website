<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Services\Action;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;
  use osCommerce\OM\Core\Session;

  use osCommerce\OM\Core\Site\Website\Invision;
  use osCommerce\OM\Core\Site\Website\Partner;

  class Dashboard {
    public static function execute(ApplicationAbstract $application) {
      $OSCOM_Template = Registry::get('Template');

      define('SERVICE_SESSION_FORCE_COOKIE_USAGE', 1);
      Registry::set('Session', Session::load());

      $OSCOM_Session = Registry::get('Session');
      $OSCOM_Session->start();

      Registry::get('MessageStack')->loadFromSession();

      if ( !isset($_SESSION[OSCOM::getSite()]['Services']) ) {
        if ( isset($_COOKIE['member_id']) && is_numeric($_COOKIE['member_id']) && ($_COOKIE['member_id'] > 0) && isset($_COOKIE['pass_hash']) && (strlen($_COOKIE['pass_hash']) == 32) ) {
          $OSCOM_Invision = new Invision();

          if ( $OSCOM_Invision->autoLogin($_COOKIE['member_id'], $_COOKIE['pass_hash']) ) {
            if ( $OSCOM_Invision->hasAccess() ) {
              $_SESSION[OSCOM::getSite()]['Services'] = array('id' => $OSCOM_Invision->getUserData('id'),
                                                              'name' => $OSCOM_Invision->getUserData('name'));
            }
          }
        }
      }

      if ( !isset($_SESSION[OSCOM::getSite()]['Services']) ) {
        OSCOM::redirect(OSCOM::getLink(null, 'Services', 'Login'));
      }

      if ( Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Services']['id']) ) {
        $OSCOM_Template->setValue('partner_campaigns', Partner::getCampaigns($_SESSION[OSCOM::getSite()]['Services']['id']));
        $OSCOM_Template->setValue('partner_date_now', date('Y-m-d H:i:s'));

        $application->setPageContent('dashboard.html');
      } else {
        $application->setPageContent('dashboard_empty.html');
      }

      $application->setPageTitle(OSCOM::getDef('dashboard_html_title'));
    }
  }
?>
