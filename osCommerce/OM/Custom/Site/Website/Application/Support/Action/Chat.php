<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Support\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM
};

class Chat
{
    public static function execute(ApplicationAbstract $application)
    {
        $application->setPageContent('chat.html');
        $application->setPageTitle(OSCOM::getDef('live_chat_html_page_title'));
    }
}
