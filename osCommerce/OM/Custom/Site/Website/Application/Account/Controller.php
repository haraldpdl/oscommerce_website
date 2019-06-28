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

use osCommerce\OM\Core\Site\Website\Users;

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

            $user_custom = Users::getCustomFields($_SESSION[OSCOM::getSite()]['Account']['id']);

            $OSCOM_Template->setValue('user_custom', $user_custom);
            $OSCOM_Template->setValue('joined_short', (new \DateTime($_SESSION[OSCOM::getSite()]['Account']['joined']))->format('F Y'));
            $OSCOM_Template->setValue('reputation_friendly', number_format($user_custom['reputation']));

            $birthday = null;

            if (isset($user_custom['birthday'])) {
                $bday = explode('/', $user_custom['birthday'], 3);

                if (count($bday) === 3) {
                    $bdDateTime = \DateTime::createFromFormat('m/d/Y', $user_custom['birthday']);

                    if ($bdDateTime !== false) {
                        $bdDateTime_errors = \DateTime::getLastErrors();

                        if (($bdDateTime_errors['warning_count'] === 0) && ($bdDateTime_errors['error_count'] === 0)) {
                            $birthday = $bdDateTime->format('jS M Y');
                        }
                    }
                } elseif (count($bday) === 2) {
                    $bday[] = 2000; // add a compatible leap year for 02/29 birthdays

                    $bdDateTime = \DateTime::createFromFormat('m/d/Y', implode('/', $bday));

                    if ($bdDateTime !== false) {
                        $bdDateTime_errors = \DateTime::getLastErrors();

                        if (($bdDateTime_errors['warning_count'] === 0) && ($bdDateTime_errors['error_count'] === 0)) {
                            $birthday = $bdDateTime->format('jS M');
                        }
                    }
                }
            }

            $OSCOM_Template->setValue('birthday_friendly', $birthday);

            $gender_code = '';

            if ($user_custom['gender'] == 'Male') {
                $gender_code = 'male';
            } elseif ($user_custom['gender'] == 'Female') {
                $gender_code = 'female';
            } elseif ($user_custom['gender'] == 'Not Telling') {
                $gender_code = 'other';
            }

            $OSCOM_Template->setValue('gender_code', $gender_code);

            $this->_page_contents = 'main.html';
            $this->_page_title = OSCOM::getDef('account_html_title');
        } else {
            $this->_page_contents = 'login.html';
            $this->_page_title = OSCOM::getDef('login_html_title');

            $OSCOM_Template->setValue('recaptcha_key_public', OSCOM::getConfig('recaptcha_key_public'));
        }
    }
}
