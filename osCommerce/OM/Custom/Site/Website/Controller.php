<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
    Events,
    Hash,
    HTML,
    OSCOM,
    Registry
};

class Controller implements \osCommerce\OM\Core\SiteInterface
{
    protected static $_default_application = 'Index';

    public static function initialize()
    {
        require(__DIR__ . '/External/vendor/autoload.php');

        Registry::addAliases([
            'PDO_OLD' => 'Core\Site\Apps\Registry\PDO_OLD'
        ]);

        $OSCOM_Session = Registry::get('Session');
        $OSCOM_Session->setLifeTime(3600);

        Events::scan();

        Events::watch('session_started', function () {
            if (!isset($_SESSION[OSCOM::getSite()]['public_token'])) {
                $_SESSION[OSCOM::getSite()]['public_token'] = Hash::getRandomString(32);
            }
        });

        if (!OSCOM::isRPC()) {
            if (isset($_COOKIE[$OSCOM_Session->getName()]) || isset($_COOKIE[Invision::COOKIE_SESSION_NAME])) {
                $OSCOM_Session->start();

                if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
                    $user = Invision::canAutoLogin();

                    if (is_array($user) && isset($user['id'])) {
                        Events::fire('auto_login-before', $user);

                        if (($user['verified'] === true) && ($user['banned'] === false)) {
                            $_SESSION[OSCOM::getSite()]['Account'] = $user;

                            $OSCOM_Session->recreate();

                            Events::fire('auto_login-after');
                        } else {
                            Invision::killCookies();
                        }
                    } else {
                        Invision::killCookies();
                    }
                }
            }
        }

        $application = 'osCommerce\\OM\\Core\\Site\\Website\\Application\\' . OSCOM::getSiteApplication() . '\\Controller';
        Registry::set('Application', new $application());

        if ($OSCOM_Session->hasStarted()) {
            if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
                $keep_alive = false;

                $req_sig = array_keys($_GET);

                if (isset($req_sig[0]) && ($req_sig[0] == 'RPC')) {
                    array_shift($req_sig);
                }

                if (isset($req_sig[0]) && ($req_sig[0] == OSCOM::getSite())) {
                    array_shift($req_sig);
                }

                $req_sig = implode('&', $req_sig);

                if (isset($_SESSION[OSCOM::getSite()]['keepAlive'])) {
                    foreach ($_SESSION[OSCOM::getSite()]['keepAlive'] as $ka) {
                        if (strpos($req_sig, $ka) === 0) {
                            $keep_alive = true;
                            break;
                        }
                    }
                }

                if ($keep_alive === false) {
                    $OSCOM_Session->kill();
                }
            }
        }

        $OSCOM_Template = Registry::get('Template');
        $OSCOM_Language = Registry::get('Language');

        $OSCOM_Template->setApplication(Registry::get('Application'));

        $OSCOM_Template->addHtmlTag('dir', $OSCOM_Language->getTextDirection());
        $OSCOM_Template->addHtmlTag('lang', OSCOM::getDef('html_lang_code')); // HPDL A better solution is to define the ISO 639-1 value at the language level

        $OSCOM_Template->addHtmlElement('header', '<meta name="generator" content="osCommerce Website v' . HTML::outputProtected(OSCOM::getVersion(OSCOM::getSite())) . '">');

        $OSCOM_Template->setValue('html_tags', $OSCOM_Template->getHtmlTags());
        $OSCOM_Template->setValue('html_character_set', $OSCOM_Language->getCharacterSet());
        $OSCOM_Template->setValue('html_page_title', $OSCOM_Template->getPageTitle());
        $OSCOM_Template->setValue('html_page_contents_file', $OSCOM_Template->getPageContentsFile());
        $OSCOM_Template->setValue('html_base_href', $OSCOM_Template->getBaseUrl());
        $OSCOM_Template->setValue('html_header_tags', $OSCOM_Template->getHtmlElements('header'));
        $OSCOM_Template->setValue('html_footer_tags', $OSCOM_Template->getHtmlElements('footer'));
        $OSCOM_Template->setValue('site_req', ['site' => OSCOM::getSite(), 'app' => OSCOM::getSiteApplication(), 'actions' => Registry::get('Application')->getActionsRun()]);
        $OSCOM_Template->setValue('site_version', OSCOM::getVersion(OSCOM::getSite()));
        $OSCOM_Template->setValue('current_year', date('Y'));

        if ($OSCOM_Session->hasStarted() && isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $OSCOM_Template->setValue('user', $_SESSION[OSCOM::getSite()]['Account']);
        }
    }

    public static function getDefaultApplication()
    {
        return self::$_default_application;
    }

    public static function hasAccess($application)
    {
        return true;
    }
}
