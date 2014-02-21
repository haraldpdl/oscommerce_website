<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website;

  use osCommerce\OM\Core\HttpRequest;
  use osCommerce\OM\Core\OSCOM;

  class Invision {
    public static function checkMemberExists($search, $key) {
      $search = static::_parseCleanValue($search);

      if ( empty($search) ) {
        return false;
      }

      if ( !in_array($key, ['email', 'username']) ) {
        return false;
      }

      if ( ($key == 'email') && !filter_var($search, FILTER_VALIDATE_EMAIL) ) {
        return false;
      }

      $request = xmlrpc_encode_request('checkMemberExists', ['api_key' => OSCOM::getConfig('community_api_key'),
                                                             'api_module' => OSCOM::getConfig('community_api_module'),
                                                             'search_type' => $key,
                                                             'search_string' => $search], ['encoding' => 'utf-8']);

      $response = json_decode(HttpRequest::getResponse(['url' => OSCOM::getConfig('community_api_address'),
                                                        'parameters' => $request]), true);

      return is_array($response) && isset($response['memberExists']) && ($response['memberExists'] === true);
    }

    public static function createUser($username, $email, $password) {
      $username = static::_parseCleanValue($username);
      $email = static::_parseCleanValue($email);
      $password = static::_parseCleanValue($password);

      if ( empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
        return false;
      }

      $request = xmlrpc_encode_request('createUser', ['api_key' => OSCOM::getConfig('community_api_key'),
                                                      'api_module' => 'oscommerce',
                                                      'username' => $username,
                                                      'email' => $email,
                                                      'md5_pass' => md5($password),
                                                      'ip' => OSCOM::getIPAddress()], ['encoding' => 'utf-8']);

      $response = json_decode(HttpRequest::getResponse(['url' => OSCOM::getConfig('community_api_address'),
                                                        'parameters' => $request]), true);

      return $response;
    }

    public static function verifyUserKey($user_id, $key) {
      $user_id = trim(str_replace(array("\r\n", "\n", "\r"), '', $user_id));
      $key = preg_replace('/[^a-zA-Z0-9\-\_]/', '', $key);

      if ( !is_numeric($user_id) || ($user_id < 1) ) {
        return false;
      }

      if ( strlen($key) !== 32 ) {
        return false;
      }

      $request = xmlrpc_encode_request('verifyUserKey', ['api_key' => OSCOM::getConfig('community_api_key'),
                                                         'api_module' => 'oscommerce',
                                                         'user_id' => $user_id,
                                                         'key' => $key], ['encoding' => 'utf-8']);

      $response = json_decode(HttpRequest::getResponse(['url' => OSCOM::getConfig('community_api_address'),
                                                        'parameters' => $request]), true);

      return $response;
    }

    public static function canLogin($username, $password) {
      $username = static::_parseCleanValue($username);
      $password = static::_parseCleanValue($password);

      $request = xmlrpc_encode_request('verifyMember', ['api_key' => OSCOM::getConfig('community_api_key'),
                                                        'api_module' => 'oscommerce',
                                                        'username' => $username,
                                                        'password' => md5($password)], ['encoding' => 'utf-8']);

      $response = json_decode(HttpRequest::getResponse(['url' => OSCOM::getConfig('community_api_address'),
                                                        'parameters' => $request]), true);

      if ( is_array($response) && !empty($response) && isset($response['result']) && ($response['result'] === true) && isset($response['member']['member_id']) && ($response['member']['member_id'] > 0) ) {
        $user = ['id' => (int)$response['member']['member_id'],
                 'name' => $response['member']['members_display_name'],
                 'email' => $response['member']['email'],
                 'group_id' => (int)$response['member']['member_group_id'],
                 'verified' => (int)$response['member']['member_group_id'] !== 1,
                 'banned' => in_array((int)$response['member']['member_group_id'], [2, 5]) || (!empty($response['member']['temp_ban']) && ($response['member']['temp_ban'] != '0')),
                 'restricted_post' => (!empty($response['member']['restrict_post']) && ($response['member']['restrict_post'] != '0')) || (!empty($response['member']['mod_posts']) && ($response['member']['mod_posts'] != '0')),
                 'login_key' => $response['member']['member_login_key']];

        return $user;
      }

      return false;
    }

    public static function canAutoLogin($id, $hash) {
      $id = trim(str_replace(array("\r\n", "\n", "\r"), '', $id));
      $hash = preg_replace('/[^a-zA-Z0-9\-\_]/', '', $hash);

      if ( !is_numeric($id) || ($id < 1) ) {
        return false;
      }

      if ( strlen($hash) !== 32 ) {
        return false;
      }

      $request = xmlrpc_encode_request('canAutoLogin', ['api_key' => OSCOM::getConfig('community_api_key'),
                                                        'api_module' => 'oscommerce',
                                                        'member_id' => $id,
                                                        'pass_hash' => $hash], ['encoding' => 'utf-8']);

      $response = json_decode(HttpRequest::getResponse(['url' => OSCOM::getConfig('community_api_address'),
                                                        'parameters' => $request]), true);

      if ( is_array($response) && !empty($response) && isset($response['result']) && ($response['result'] === true) && isset($response['member']['member_id']) && ($response['member']['member_id'] > 0) && ($response['member']['member_id'] == $id) ) {
        $user = ['id' => (int)$response['member']['member_id'],
                 'name' => $response['member']['members_display_name'],
                 'email' => $response['member']['email'],
                 'group_id' => (int)$response['member']['member_group_id'],
                 'verified' => (int)$response['member']['member_group_id'] !== 1,
                 'banned' => in_array((int)$response['member']['member_group_id'], [2, 5]) || (!empty($response['member']['temp_ban']) && ($response['member']['temp_ban'] != '0')),
                 'restricted_post' => (!empty($response['member']['restrict_post']) && ($response['member']['restrict_post'] != '0')) || (!empty($response['member']['mod_posts']) && ($response['member']['mod_posts'] != '0')),
                 'login_key' => $response['member']['member_login_key']];

        return $user;
      }

      return false;
    }

    protected static function _parseCleanValue($val) {
      if ( empty($val) ) {
        return '';
      }

      $val = preg_replace('/\\\(?!&amp;#|\?#)/', '&#092;', $val);

      $val = str_replace('&#032;', ' ', $val);

      $val = str_replace(array("\r\n", "\n\r", "\r"), "\n", $val);

      $val = str_replace('&', '&amp;', $val);
      $val = str_replace('<!--', '&#60;&#33;--', $val);
      $val = str_replace('-->', '--&#62;', $val);
      $val = str_ireplace('<script', '&#60;script', $val);
      $val = str_replace('>', '&gt;', $val);
      $val = str_replace('<', '&lt;', $val);
      $val = str_replace('"', '&quot;', $val);
      $val = str_replace("\n", '<br />', $val);
      $val = str_replace('$', '&#036;', $val);
      $val = str_replace('!', '&#33;', $val);
      $val = str_replace("'", '&#39;', $val);

      $val = preg_replace('/&amp;#([0-9]+);/s', "&#\\1;", $val);
      $val = preg_replace('/&#(\d+?)([^\d;])/i', "&#\\1;\\2", $val);

      return $val;
    }
  }
?>
