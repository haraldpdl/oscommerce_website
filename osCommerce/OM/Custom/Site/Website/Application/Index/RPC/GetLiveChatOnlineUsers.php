<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\{
    HttpRequest,
    Registry
};

class GetLiveChatOnlineUsers
{
    public static function execute()
    {
        $OSCOM_Cache = Registry::get('Cache');

        $online = 0;

        if ($OSCOM_Cache->read('stats_live_chat_online_users')) {
            $online = $OSCOM_Cache->getCache();
        }

        if (!$OSCOM_Cache->read('stats_live_chat_online_users', 30)) {
            $source = HttpRequest::getResponse(['url' => 'https://discordapp.com/api/servers/106369341515145216/widget.json']);

            if (!empty($source)) {
                $data = json_decode($source);

                if (isset($data->members)) {
                    $online = count($data->members);

                    $OSCOM_Cache->write($online);
                }
            }
        }

        header('Cache-Control: max-age=1800, must-revalidate');
        header_remove('Pragma');
        header('Content-Type: application/javascript');

        $output = <<<JAVASCRIPT
var liveChatOnlineCounter = $online;

document.observe('dom:loaded', function() {
  if (liveChatOnlineCounter > 0) {
    $('chat-tab-count').innerHTML = parseInt(liveChatOnlineCounter);

    var _thisHtml = $('nav_app_discordchat').down('a').innerHTML;
    _thisHtml = _thisHtml + $('chat-tab-count-wrap').innerHTML;
    $('nav_app_discordchat').down('a').update( _thisHtml ).setStyle( { position: 'relative' } );
    $('chat-tab-count-wrap').remove();
    $('chat-tab-count').show();
  }
});
JAVASCRIPT;

        echo $output;
    }
}
