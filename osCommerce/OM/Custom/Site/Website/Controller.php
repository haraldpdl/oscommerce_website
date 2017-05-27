<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
    Cache,
    Events,
    HTML,
    OSCOM,
    PDO,
    Registry
};

use osCommerce\OM\Core\Site\Website\Session;

class Controller implements \osCommerce\OM\Core\SiteInterface
{
    protected static $_default_application = 'Index';

    public static function initialize()
    {
        Registry::set('MessageStack', new MessageStack());
        Registry::set('Cache', new Cache());
        Registry::set('PDO', PDO::initialize());
        Registry::set('Session', Session::load());

        $OSCOM_Session = Registry::get('Session');
        $OSCOM_Session->setLifeTime(3600);

        Events::scan();

        if (!OSCOM::isRPC()) {
            if (isset($_COOKIE[$OSCOM_Session->getName()])) {
                $OSCOM_Session->start();

                if (!isset($_SESSION[OSCOM::getSite()]['Account']) && (OSCOM::getSiteApplication() != 'Account')) {
                    $OSCOM_Session->kill();
                }
            }

            if (!$OSCOM_Session->hasStarted() || !isset($_SESSION[OSCOM::getSite()]['Account'])) {
                $user = Invision::canAutoLogin();

                if (is_array($user) && isset($user['id'])) {
                    Events::fire('auto_login-before', $user);

                    if (($user['verified'] === true) && ($user['banned'] === false)) {
                        if (!$OSCOM_Session->hasStarted()) {
                            $OSCOM_Session->start();
                        }

                        $_SESSION[OSCOM::getSite()]['Account'] = $user;

                        $OSCOM_Session->recreate();

                        Events::fire('auto_login-after');
                    } else {
                        Invision::killCookies();
                    }
                }
            }
        }

        Registry::set('Language', new Language());
        Registry::set('Template', new Template());

        $OSCOM_Template = Registry::get('Template');
        $OSCOM_Language = Registry::get('Language');

        $OSCOM_Template->addHtmlTag('dir', $OSCOM_Language->getTextDirection());
        $OSCOM_Template->addHtmlTag('lang', OSCOM::getDef('html_lang_code')); // HPDL A better solution is to define the ISO 639-1 value at the language level

        $OSCOM_Template->addHtmlElement('header', '<meta name="generator" content="osCommerce Website v' . HTML::outputProtected(OSCOM::getVersion(OSCOM::getSite())) . '" />');

        $application = 'osCommerce\\OM\\Core\\Site\\Website\\Application\\' . OSCOM::getSiteApplication() . '\\Controller';
        Registry::set('Application', new $application());
        Registry::get('Application')->runActions();

        $OSCOM_Template->setApplication(Registry::get('Application'));

        $OSCOM_Template->setValue('html_tags', $OSCOM_Template->getHtmlTags());
        $OSCOM_Template->setValue('html_character_set', $OSCOM_Language->getCharacterSet());
        $OSCOM_Template->setValue('html_page_title', $OSCOM_Template->getPageTitle());
        $OSCOM_Template->setValue('html_page_contents_file', $OSCOM_Template->getPageContentsFile());
        $OSCOM_Template->setValue('html_base_href', $OSCOM_Template->getBaseUrl());
        $OSCOM_Template->setValue('html_header_tags', $OSCOM_Template->getHtmlElements('header'));
        $OSCOM_Template->setValue('site_req', [ 'site' => OSCOM::getSite(), 'app' => OSCOM::getSiteApplication(), 'actions' => Registry::get('Application')->getActionsRun() ]);
        $OSCOM_Template->setValue('site_version', OSCOM::getVersion(OSCOM::getSite()));
        $OSCOM_Template->setValue('current_year', date('Y'));
        $OSCOM_Template->setValue('in_ssl', OSCOM::getRequestType() == 'SSL');

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
