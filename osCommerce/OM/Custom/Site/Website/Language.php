<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\OSCOM;

class Language extends \osCommerce\OM\Core\Site\Admin\Language
{
    protected $default_language_id = 1;

    public function __construct()
    {
        parent::__construct();
    }

    public function set($code = null)
    {
        $this->_code = $code;

        if (empty($this->_code)) {
            if (OSCOM::configExists('default_language', 'Website')) {
                $this->_code = OSCOM::getConfig('default_language', 'Website');
            }
        }

        if (empty($this->_code) || !$this->exists($this->_code)) {
            $this->_code = $this->getCode($this->getDefaultId());
        }
    }

    public function getDefaultId()
    {
        return $this->default_language_id;
    }
}
