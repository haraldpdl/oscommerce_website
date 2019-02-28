<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\OSCOM;

class MessageStack extends \osCommerce\OM\Core\MessageStack
{
    public function get(string $group = null): string
    {
        if (empty($group)) {
            $group = OSCOM::getSiteApplication();
        }

        $result = '';

        if ($this->exists($group)) {
            $result = '';

            foreach ($this->_data[$group] as $message) {
                if ($message['type'] == 'error') {
                    $message['type'] = 'danger';
                }

                $result .= '<div class="alert alert-' . $message['type'] . '">' . $message['text'] . '</div>';
            }

            unset($this->_data[$group]);
        }

        return $result;
    }
}
