<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Services\Action\Login;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  use osCommerce\OM\Core\Site\Website\Invision;

  class Process {
    public static function execute(ApplicationAbstract $application) {
      if ( isset($_POST['username']) && !empty($_POST['username']) && isset($_POST['password']) && !empty($_POST['password']) ) {
        $OSCOM_Invision = new Invision($_POST['username'], $_POST['password']);
        $OSCOM_Invision->perform();

        if ( $OSCOM_Invision->hasAccess() ) {
          $_SESSION[OSCOM::getSite()]['Services'] = array('id' => $OSCOM_Invision->getUserData('id'),
                                                          'name' => $OSCOM_Invision->getUserData('name'));

          OSCOM::redirect(OSCOM::getLink(null, 'Services', 'Dashboard'));
        } else {
          Registry::get('MessageStack')->add('services', $OSCOM_Invision->getNoAccessReason(), 'error');
        }
      }
    }
  }
?>
