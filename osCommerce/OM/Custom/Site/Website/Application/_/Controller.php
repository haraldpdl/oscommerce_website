<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\_;

  use osCommerce\OM\Core\OSCOM;

  class Controller extends \osCommerce\OM\Core\Site\Website\ApplicationAbstract {
    protected function initialize() {
      $this->_page_contents = 'main.html';
    }

    public function runActions() {
      if (!OSCOM::isRPC()) {
        parent::runActions();

        if ( $this->getCurrentAction() === false ) {
          OSCOM::redirect(OSCOM::getLink(null, 'Index'));
        }
      }
    }
  }
?>
