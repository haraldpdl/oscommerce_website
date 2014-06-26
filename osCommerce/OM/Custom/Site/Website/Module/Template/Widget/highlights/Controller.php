<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Module\Template\Widget\highlights;

  use osCommerce\OM\Core\HTML;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  class Controller extends \osCommerce\OM\Core\Template\WidgetAbstract {
    static public function execute($param = null) {
      $OSCOM_Template = Registry::get('Template');

      $file = OSCOM::BASE_DIRECTORY . 'Custom/Site/' . OSCOM::getSite() . '/Module/Template/Widget/highlights/pages/main.html';

      if ( !file_exists($file) ) {
        $file = OSCOM::BASE_DIRECTORY . 'Core/Site/' . OSCOM::getSite() . '/Module/Template/Widget/highlights/pages/main.html';
      }

      $result = '';

      if ( file_exists(__DIR__ . '/data.json') ) {
        $data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);

        if ( !empty($data) && is_array($data) ) {
          $counter = 1;

          foreach ( $data as $p ) {
            $result .= '<div class="' . (($counter === 1) ? 'active ' : '') . 'item">
  <a href="' . $p['url'] . '"' . (($p['newwin'] === true) ? ' target="_blank"' : '') . '>' . (($p['partner'] === true) ? '<span class="label label-warning" style="position: absolute; padding: 7px; right: 0;">' . OSCOM::getDef('tag_partner') . '</span>' : '') . '<img src="' . $p['image'] . '" ' . (!empty($p['title']) ? 'title="' . HTML::outputProtected($p['title']) . '" ' : '') . ' /></a>
</div>';

            $counter++;
          }
        }
      }

      if ( !empty($result) ) {
        $OSCOM_Template->setValue('highlights_carousel_output', $OSCOM_Template->parseContent($result));

        return file_get_contents($file);
      }

      return '';
    }
  }
?>
