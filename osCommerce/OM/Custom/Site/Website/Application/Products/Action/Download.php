<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Products\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    Events,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\{
    Download as DownloadClass,
    Partner,
    Users
};

class Download
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Session = Registry::get('Session');
        $OSCOM_Template = Registry::get('Template');

        if (!$OSCOM_Session->hasStarted()) {
            $OSCOM_Session->start();
        }

        $file = null;

        if (!empty($_GET['Download']) && is_numeric($_GET['Download']) && ($_GET['Download'] > 0)) {
            $file = $_GET['Download'];
        } elseif (!empty($_GET['Download']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
            $file = $_GET['Download'];
        } elseif (isset($_POST['get']) && !empty($_POST['get']) && isset($_POST['do'])) { // legacy
            $file = $_POST['get'];
        }

        if (isset($file) && DownloadClass::exists($file)) {
            Events::fire('download-before', $file);

            $file_source = DownloadClass::FILE_DIRECTORY . basename(DownloadClass::get($file, 'filename'));

            if (file_exists($file_source)) {
                DownloadClass::incrementDownloadCounter($file);

                Events::fire('download-after', $file);

                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($file_source) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file_source));

                readfile($file_source);

                exit;
            }
        }

// redirect to a copy friendly url
        if (isset($_POST['get']) && !empty($_POST['get']) && DownloadClass::exists($_POST['get'])) {
            OSCOM::redirect(OSCOM::getLink(null, null, 'Download=' . DownloadClass::get($_POST['get'], 'code')));
        }

        if (!empty($_GET['Download']) && DownloadClass::exists($_GET['Download'])) {
            $OSCOM_Template->addHtmlElement('header', '<meta name="robots" content="noindex, nofollow">');

            $OSCOM_Template->setValue('download_file_title', DownloadClass::get($_GET['Download'], 'title') . ' ' . DownloadClass::get($_GET['Download'], 'version'));

            $OSCOM_Template->setValue('is_ambassador', (isset($_SESSION[OSCOM::getSite()]['Account']) && ($_SESSION[OSCOM::getSite()]['Account']['amb_level'] > 0)));
            $OSCOM_Template->setValue('ambassador_user_next_level', isset($_SESSION[OSCOM::getSite()]['Account']) ? $_SESSION[OSCOM::getSite()]['Account']['amb_level'] + 1 : 1);

            $amb_members = [];

            foreach (Users::getNewestAmbassadors(3) as $a) {
                $m = Users::get($a);

                $amb_members[] = [
                    'name' => $m['name'],
                    'profile_url' => $m['profile_url'],
                    'photo_url' => $m['photo_url']
                ];
            }

            $OSCOM_Template->setValue('amb_members', $amb_members);

            $OSCOM_Template->setValue('partner_promotions', Partner::getPromotions());

            $promotion_categories = [];

            foreach ($OSCOM_Template->getValue('partner_promotions') as $p) {
                if (!isset($promotion_categories[$p['category_code']])) {
                    $promotion_categories[$p['category_code']] = [
                        'title' => $p['category_title'],
                        'code' => $p['category_code'],
                        'sort' => $p['category_sort_order']
                    ];
                }
            }

            usort($promotion_categories, function ($a, $b) {
                return strcmp($a['sort'], $b['sort']);
            });

            $OSCOM_Template->setValue('partner_promotion_categories', $promotion_categories);

            $application->setPageContent('download.html');
            $application->setPageTitle(OSCOM::getDef('download_html_title', [':download_file_title' => $OSCOM_Template->getValue('download_file_title')]));
        } else {
            OSCOM::redirect(OSCOM::getLink());
        }
    }
}
