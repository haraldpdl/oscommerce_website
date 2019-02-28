<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Apps\Cache;

class Language extends \osCommerce\OM\Core\Site\Admin\Language
{
    protected $default_language_id = 1;

    public function __construct()
    {
        $OSCOM_PDO = Registry::get('PDO');

        $OSCOM_Cache = new Cache('languages');

        if (($languages = $OSCOM_Cache->get()) === false) {
            $Qlang = $OSCOM_PDO->query('select * from :table_languages order by sort_order, name');

            $languages = $Qlang->fetchAll();

            $OSCOM_Cache->set($languages);
        }

        if (!is_array($languages)) {
            $languages = [];
        }

        foreach ($languages as $lang) {
            $this->_languages[$lang['code']] = [
                'id' => (int)$lang['languages_id'],
                'code' => $lang['code'],
                'name' => $lang['name'],
                'locale' => $lang['locale'],
                'charset' => $lang['charset'],
                'date_format_short' => $lang['date_format_short'],
                'date_format_long' => $lang['date_format_long'],
                'time_format' => $lang['time_format'],
                'text_direction' => $lang['text_direction'],
                'currencies_id' => (int)$lang['currencies_id'],
                'numeric_separator_decimal' => $lang['numeric_separator_decimal'],
                'numeric_separator_thousands' => $lang['numeric_separator_thousands'],
                'parent_id' => (int)$lang['parent_id']
            ];
        }

        $this->set();

        $system_locale_numeric = setlocale(LC_NUMERIC, '0');
        setlocale(LC_ALL, explode(',', $this->getLocale()));
        setlocale(LC_NUMERIC, $system_locale_numeric);

        header('Content-Type: text/html; charset=' . $this->getCharacterSet());

        $this->loadIniFile();
        $this->loadIniFile(OSCOM::getSiteApplication() . '.php');
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
