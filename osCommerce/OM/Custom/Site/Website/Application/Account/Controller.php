<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account;

use osCommerce\OM\Core\{
    OSCOM,
    Registry
};

class Controller extends \osCommerce\OM\Core\Site\Website\ApplicationAbstract
{
    protected function initialize()
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Session = Registry::get('Session');
        $OSCOM_Template = Registry::get('Template');

        if (!$OSCOM_Session->hasStarted()) {
            $OSCOM_Session->start();
        }

        if (!isset($_SESSION[OSCOM::getSite()]['keepAlive']) || !in_array(OSCOM::getSiteApplication(), $_SESSION[OSCOM::getSite()]['keepAlive'])) {
            $_SESSION[OSCOM::getSite()]['keepAlive'][] = OSCOM::getSiteApplication();
        }

        $OSCOM_Template->addHtmlElement('header', '<meta name="robots" content="noindex, nofollow">');

        $OSCOM_Template->setValue('public_token', $_SESSION[OSCOM::getSite()]['public_token']);

        if (isset($_SESSION[OSCOM::getSite()]['Account'])) {
            if (isset($_GET['ms']) && !empty($_GET['ms'])) {
                switch ($_GET['ms']) {
                    case 'already_logged_in':
                        $OSCOM_MessageStack->add('account', OSCOM::getDef('login_ms_warning_already_logged_in'), 'warning');
                        break;

                    case 'password_changed':
                        $OSCOM_MessageStack->add('account', OSCOM::getDef('change_password_ms_success'), 'success');
                        break;
                }
            }

            $this->_page_contents = 'main.html';
            $this->_page_title = OSCOM::getDef('account_html_title');
        } else {
            $this->_page_contents = 'login.html';
            $this->_page_title = OSCOM::getDef('login_html_title');

            $OSCOM_Template->setValue('recaptcha_key_public', OSCOM::getConfig('recaptcha_key_public'));
        }
    }
}
