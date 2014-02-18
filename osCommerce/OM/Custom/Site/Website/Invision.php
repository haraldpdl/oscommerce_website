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
                                                             'search_string' => $search]);

      $response = xmlrpc_decode(HttpRequest::getResponse(['url' => OSCOM::getConfig('community_api_address'),
                                                          'parameters' => $request]));

      return is_array($response) && isset($response['memberExists']) && ($response['memberExists'] === true);
    }

    public static function createUser($username, $email, $password) {
      $username = trim(str_replace(array("\r\n", "\n", "\r"), '', $username));
      $email = trim(str_replace(array("\r\n", "\n", "\r"), '', $email));
      $password = str_replace(array("\r\n", "\n", "\r"), '', $password);

      if ( (strlen($username) < 3) || (strlen($username) > 26) ) {
        return false;
      }

      if ( empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
        return false;
      }

      if ( (strlen($password) < 3) || (strlen($password) > 32) ) {
        return false;
      }

      $request = xmlrpc_encode_request('createUser', ['api_key' => OSCOM::getConfig('community_api_key'),
                                                      'api_module' => 'oscommerce',
                                                      'username' => $username,
                                                      'email' => $email,
                                                      'md5_pass' => md5($password),
                                                      'ip' => OSCOM::getIPAddress()]);

      $response = xmlrpc_decode(HttpRequest::getResponse(['url' => OSCOM::getConfig('community_api_address'),
                                                          'parameters' => $request]));

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
                                                         'key' => $key]);

      $response = xmlrpc_decode(HttpRequest::getResponse(['url' => OSCOM::getConfig('community_api_address'),
                                                          'parameters' => $request]));

      return $response;
    }

    public static function canLogin($username, $password) {
      $username = trim(str_replace(array("\r\n", "\n", "\r"), '', $username));
      $password = str_replace(array("\r\n", "\n", "\r"), '', $password);

      if ( (strlen($username) < 3) || (strlen($username) > 26) ) {
        return false;
      }

      if ( (strlen($password) < 3) || (strlen($password) > 32) ) {
        return false;
      }

      $request = xmlrpc_encode_request('verifyMember', ['api_key' => OSCOM::getConfig('community_api_key'),
                                                        'api_module' => 'oscommerce',
                                                        'username' => $username,
                                                        'password' => md5($password)]);

      $response = xmlrpc_decode(HttpRequest::getResponse( ['url' => OSCOM::getConfig('community_api_address'),
                                                           'parameters' => $request] ));

      if ( is_array($response) && !empty($response) && isset($response['result']) && ($response['result'] === true) && isset($response['member'][0]['member_id']) && ($response['member'][0]['member_id'] > 0) ) {
        $user = ['id' => (int)$response['member'][0]['member_id'],
                 'name' => $response['member'][0]['members_display_name'],
                 'email' => $response['member'][0]['email'],
                 'group_id' => (int)$response['member'][0]['member_group_id'],
                 'verified' => (int)$response['member'][0]['member_group_id'] !== 1,
                 'banned' => in_array((int)$response['member'][0]['member_group_id'], [2, 5]) || (!empty($response['member'][0]['temp_ban']) && ($response['member'][0]['temp_ban'] != '0')),
                 'restricted_post' => (!empty($response['member'][0]['restrict_post']) && ($response['member'][0]['restrict_post'] != '0')) || (!empty($response['member'][0]['mod_posts']) && ($response['member'][0]['mod_posts'] != '0'))];

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
                                                        'pass_hash' => $hash]);

      $response = xmlrpc_decode(HttpRequest::getResponse(['url' => OSCOM::getConfig('community_api_address'),
                                                          'parameters' => $request]));

      if ( is_array($response) && !empty($response) && isset($response['result']) && ($response['result'] === true) && isset($response['member'][0]['member_id']) && ($response['member'][0]['member_id'] > 0) && ($response['member'][0]['member_id'] == $id) ) {
        $user = ['id' => (int)$response['member'][0]['member_id'],
                 'name' => $response['member'][0]['members_display_name'],
                 'email' => $response['member'][0]['email'],
                 'group_id' => (int)$response['member'][0]['member_group_id'],
                 'verified' => (int)$response['member'][0]['member_group_id'] !== 1,
                 'banned' => in_array((int)$response['member'][0]['member_group_id'], [2, 5]) || (!empty($response['member'][0]['temp_ban']) && ($response['member'][0]['temp_ban'] != '0')),
                 'restricted_post' => (!empty($response['member'][0]['restrict_post']) && ($response['member'][0]['restrict_post'] != '0')) || (!empty($response['member'][0]['mod_posts']) && ($response['member'][0]['mod_posts'] != '0'))];

        return $user;
      }

      return false;
    }
  }
?>
