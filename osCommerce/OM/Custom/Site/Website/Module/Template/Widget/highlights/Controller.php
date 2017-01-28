<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Template\Widget\highlights;

use osCommerce\OM\Core\HTML;
use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Registry;

class Controller extends \osCommerce\OM\Core\Template\WidgetAbstract
{
    static public function execute($param = null)
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_Template = Registry::get('Template');

        $file = OSCOM::BASE_DIRECTORY . 'Custom/Site/' . OSCOM::getSite() . '/Module/Template/Widget/highlights/pages/main.html';

        if (!file_exists($file)) {
            $file = OSCOM::BASE_DIRECTORY . 'Core/Site/' . OSCOM::getSite() . '/Module/Template/Widget/highlights/pages/main.html';
        }

        $languages = [
            $OSCOM_Language->getCode()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $languages[] = $OSCOM_Language->getCodeFromID($OSCOM_Language->getDefaultId());
        }

        $data = [
            'default_language_id' => $OSCOM_Language->getDefaultId()
        ];

        if ($OSCOM_Language->getID() != $OSCOM_Language->getDefaultId()) {
            $data['language_id'] = $OSCOM_Language->getID();
        }

        $carousels = [];

        foreach (OSCOM::callDB('Website\GetFrontPageCarousel', $data, 'Site') as $c) {
            if ($c['partner_id'] > 0) {
                foreach ($languages as $l) {
                    if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/partners/' . $l . '/' . $c['carousel_image'])) {
                        $carousels[] = [
                            'url' => $c['carousel_url'],
                            'newwin' => (int)$c['new_window'] === 1,
                            'image' => $OSCOM_Template->parseContent('{publiclink}images/partners/' . $l . '/' . $c['carousel_image'] . '{publiclink}'),
                            'title' => $c['carousel_title'],
                            'partner' => true
                        ];

                        break;
                    }
                }
            } else {
                $carousels[] = [
                    'url' => $OSCOM_Template->parseContent($c['url']),
                    'newwin' => (int)$c['new_window'] === 1,
                    'image' => $OSCOM_Template->parseContent($c['image']),
                    'title' => $c['title'],
                    'partner' => false
                ];
            }
        }

        $result = '';

        if (!empty($carousels)) {
            $counter = 1;

            foreach ($carousels as $p) {
                $result .= '<div class="' . (($counter === 1) ? 'active ' : '') . 'item">
  <a href="' . HTML::outputProtected($p['url']) . '"' . (($p['newwin'] === true) ? ' target="_blank"' : '') . '>' . (($p['partner'] === true) ? '<span class="label label-warning" style="position: absolute; padding: 7px; right: 0;">' . OSCOM::getDef('tag_partner') . '</span>' : '') . '<img src="' . HTML::outputProtected($p['image']) . '" ' . (!empty($p['title']) ? 'title="' . HTML::outputProtected($p['title']) . '" ' : '') . ' /></a>
</div>';

                $counter += 1;
            }
        }

        if (!empty($result)) {
            $OSCOM_Template->setValue('highlights_carousel_output', $result);

            return file_get_contents($file);
        }

        return '';
    }
}
