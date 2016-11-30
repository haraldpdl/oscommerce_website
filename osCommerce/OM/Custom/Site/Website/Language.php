<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2012 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website;

  use osCommerce\OM\Core\OSCOM;

  class Language extends \osCommerce\OM\Core\Site\Admin\Language {
    public function __construct() {
      parent::__construct();
    }

    public function set($code = null) {
      $this->_code = $code;

      if ( empty($this->_code) ) {
        if (OSCOM::configExists('default_language', 'Website')) {
          $this->_code = OSCOM::getConfig('default_language', 'Website');
        }
      }

      if ( empty($this->_code) || !$this->exists($this->_code) ) {
        $this->_code = 'en_US';
      }
    }
  }
?>
