<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Products\Action;

  use osCommerce\OM\Core\ApplicationAbstract;
  use osCommerce\OM\Core\Events;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  use osCommerce\OM\Core\Site\Website\Download as DownloadClass;
  use osCommerce\OM\Core\Site\Website\Partner;

  class Download {
    public static function execute(ApplicationAbstract $application) {
      $OSCOM_Template = Registry::get('Template');

      $file = null;

      if ( !empty($_GET['Download']) && is_numeric($_GET['Download']) && ($_GET['Download'] > 0) ) {
        $file = $_GET['Download'];
      } elseif ( !empty($_GET['Download']) && ($_SERVER['REQUEST_METHOD'] == 'POST') ) {
        $file = $_GET['Download'];
      } elseif ( isset($_POST['get']) && !empty($_POST['get']) && isset($_POST['do']) ) { //legacy
        $file = $_POST['get'];
      }

      if ( isset($file) && DownloadClass::exists($file) ) {
        Events::fire('download-before', $file);

        $file_source = OSCOM::PUBLIC_DIRECTORY . 'public/sites/Website/files/' . basename(DownloadClass::get($file, 'filename'));

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
      if ( isset($_POST['get']) && !empty($_POST['get']) && DownloadClass::exists($_POST['get']) ) {
        OSCOM::redirect(OSCOM::getLink(null, null, 'Download=' . DownloadClass::get($_POST['get'], 'code')));
      }

      if ( !empty($_GET['Download']) && DownloadClass::exists($_GET['Download']) ) {
        $OSCOM_Template->addHtmlElement('header', '<meta name="robots" content="noindex, nofollow" />');

        $OSCOM_Template->setValue('download_file_title', DownloadClass::get($_GET['Download'], 'title') . ' ' . DownloadClass::get($_GET['Download'], 'version'));

        $OSCOM_Template->setValue('partner_promotions', Partner::getPromotions());

        $promotion_categories = [];

        foreach ( $OSCOM_Template->getValue('partner_promotions') as $p ) {
          if ( !isset($promotion_categories[$p['category_code']]) ) {
            $promotion_categories[$p['category_code']] = [ 'title' => $p['category_title'],
                                                           'code' => $p['category_code'],
                                                           'sort' => $p['category_sort_order'] ];
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
?>
