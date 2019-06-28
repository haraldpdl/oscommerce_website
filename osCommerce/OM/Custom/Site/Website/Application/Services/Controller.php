<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Services;

use osCommerce\OM\Core\{
    HTML,
    HttpRequest,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Partner;

class Controller extends \osCommerce\OM\Core\Site\Website\ApplicationAbstract
{
    protected function initialize()
    {
        $OSCOM_Template = Registry::get('Template');

        $this->_page_contents = 'main.html';
        $this->_page_title = OSCOM::getDef('services_html_page_title');

        if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/highlights/services.jpg')) {
            $OSCOM_Template->setValue('highlights_image', 'images/highlights/services.jpg');
        } else {
            $OSCOM_Template->setValue('highlights_image', 'images/940x285.gif');
        }

        $OSCOM_Template->setValue('services_action', '');
        $OSCOM_Template->setValue('partner_categories', Partner::getCategories());
    }

    public function runActions()
    {
        parent::runActions();

        if (OSCOM::isRPC()) { // avoid 404 http status set below
            return false;
        }

        $OSCOM_Template = Registry::get('Template');

        if ($this->getCurrentAction() === false) {
            $actions = [];

            $key_pos = (int)array_search(OSCOM::getSiteApplication(), array_keys($_GET));

            if (count($_GET) > ($key_pos + 1)) {
                $actions = array_keys(array_slice($_GET, ($key_pos + 1), 2, true));
            }

            $category = null;

            if (!empty($actions)) {
                array_walk($actions, function (&$key) {
                    $key = HTML::sanitize(strtolower(basename($key)));
                });

                if (Partner::categoryExists($actions[0])) {
                    $category = Partner::getCategory($actions[0]);

                    if (isset($actions[1]) && Partner::exists($actions[1], $actions[0])) {
                        $partner = Partner::get($actions[1]);

                        $this->_page_contents = 'info.html';
                        $this->_page_title = OSCOM::getDef('partner_html_page_title', [':partner_title' => $partner['title']]);

                        $OSCOM_Template->setValue('partner', $partner);
                    } else {
                        if (isset($actions[1])) {
                            HttpRequest::setResponseCode(404);
                        }

                        $this->_page_contents = 'list.html';
                        $this->_page_title = OSCOM::getDef('listing_html_page_title', [':category_title' => $category['title']]);

                        $OSCOM_Template->setValue('page_title', $category['title']);
                        $OSCOM_Template->setValue('category_partners', Partner::getInCategory($actions[0]));
                    }

                    $OSCOM_Template->setValue('services_action', $actions[0], true);
                } else {
                    HttpRequest::setResponseCode(404);
                }
            }

            if (!isset($category)) {
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
            }
        }
    }
}
