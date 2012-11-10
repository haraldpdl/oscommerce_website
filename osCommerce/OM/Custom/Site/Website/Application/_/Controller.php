<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2012 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\_;

  use osCommerce\OM\Core\OSCOM;

  class Controller extends \osCommerce\OM\Core\Site\Website\ApplicationAbstract {
    protected function initialize() {
      $this->_page_contents = 'main.html';
    }

    public function runActions() {
      parent::runActions();

      if ( is_null($this->getCurrentAction()) ) {
        OSCOM::redirect(OSCOM::getLink(null, 'Index'));
      }
    }
  }
?>
