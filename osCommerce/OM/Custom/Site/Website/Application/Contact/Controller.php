<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Contact;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
};

class Controller extends \osCommerce\OM\Core\Site\Website\ApplicationAbstract
{
    protected function initialize()
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_Session = Registry::get('Session');
        $OSCOM_Template = Registry::get('Template');

        if (!$OSCOM_Session->hasStarted()) {
            $OSCOM_Session->start();
        }

        if (!isset($_SESSION[OSCOM::getSite()]['keepAlive']) || !in_array(OSCOM::getSiteApplication(), $_SESSION[OSCOM::getSite()]['keepAlive'])) {
            $_SESSION[OSCOM::getSite()]['keepAlive'][] = OSCOM::getSiteApplication();
        }

        $OSCOM_Template->setValue('public_token', $_SESSION[OSCOM::getSite()]['public_token']);

        if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $OSCOM_Template->setValue('recaptcha_key_public', OSCOM::getConfig('recaptcha_key_public'));
            $OSCOM_Template->addHtmlElement('header', '<script src="https://www.google.com/recaptcha/api.js?hl=' . $OSCOM_Language->getCode() . '"></script>');
        }

        if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/highlights/contact.jpg')) {
            $OSCOM_Template->setValue('highlights_image', 'images/highlights/contact.jpg');
        } else {
            $OSCOM_Template->setValue('highlights_image', 'images/940x285.gif');
        }

        $this->_page_contents = 'main.html';
        $this->_page_title = OSCOM::getDef('contact_html_page_title');
    }
}
