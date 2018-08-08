<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2018 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\OSCOM;

require_once(OSCOM::getConfig('forum_dir_path', 'Website') . 'init_extern.php');

class Invision
{
    const COOKIE_DEVICE_KEY = 'ips4_device_key';
    const COOKIE_LOGIN_KEY = 'ips4_login_key';
    const COOKIE_MEMBER_ID = 'ips4_member_id';
    const COOKIE_SESSION_NAME = 'ips4_IPSSessionFront';

    const DEFAULT_TOTAL_ONLINE_USERS = 700;
    const DEFAULT_TOTAL_POSTINGS = 1600000;
    const DEFAULT_TOTAL_USERS = 300000;

    const FORUM_ADDONS_CATEGORY_IDS = [
        51,
        88
    ];

    public static function fetchMember($search, $key)
    {
        if (empty($search)) {
            return false;
        }

        if (!in_array($key, ['id', 'email', 'username'])) {
            return false;
        }

        if (($key == 'id') && !is_numeric($search)) {
            return false;
        }

        if (($key == 'email') && !filter_var($search, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $load_member_key = null;

        if ($key === 'username') {
            $load_member_key = 'name';
        } elseif ($key === 'email') {
            $load_member_key = 'email';
        }

        $member = \IPS\Member::load($search, $load_member_key);

        if ($member->member_id) {
            return static::getUserDataArray($member);
        }

        return false;
    }

    public static function checkMemberExists($search, $key): bool
    {
        return is_array(static::fetchMember($search, $key));
    }

    public static function createUser($username, $email, $password)
    {
        if (empty($username) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
            return false;
        }

        $result = false;

        try {
            $member = new \IPS\Member;

            $member->name = $username;
            $member->email = $email;
            $member->setLocalPassword($password);
            $member->member_group_id = Users::GROUP_MEMBER_ID;
            $member->members_bitoptions['view_sigs'] = true;
            $member->members_bitoptions['validating'] = true;
            $member->last_visit = time();

            $member->save();

            $member->invalidateSessionsAndLogins();

            // Prevent duplicates from double clicking, etc
            \IPS\Db::i()->delete('core_validating', ['member_id=? and new_reg=1', $member->member_id]);

            $vid = md5($member->members_pass_hash . \IPS\Login::generateRandomString());

            \IPS\Db::i()->insert('core_validating', [
                'vid' => $vid,
                'member_id' => $member->member_id,
                'entry_date' => time(),
                'new_reg' => 1,
                'ip_address' => $member->ip_address,
                'spam_flag' => false,
                'user_verified' => false,
                'email_sent' => time(),
                'do_not_delete' => false
            ]);

            $result = static::getUserDataArray($member);
        } catch (\Exception $e) {
            trigger_error($e->getMessage());
        }

        return $result;
    }

    public static function verifyUserKey($user_id, $key)
    {
        if (!is_numeric($user_id) || ($user_id < 1)) {
            return false;
        }

        if (strlen($key) !== 32) {
            return false;
        }

        $user = static::fetchMember($user_id, 'id');

        if (($user !== false) && is_array($user) && isset($user['id']) && ($user['id'] > 0)) {
            if (($user['verified'] === false) && !empty($user['val_newreg_id'])) {
                if ($user['val_newreg_id'] == $key) {
                    $member = \IPS\Member::load($user_id);

                    \IPS\Db::i()->delete('core_validating', ['member_id=?', $member->member_id]);

                    /* Reset the flag */
                    $member->members_bitoptions['validating'] = false;
                    $member->save();

                    /* Sync */
                    $member->memberSync('onValidate');

                    return true;
                } else {
                    return [
                        'error' => 'invalid_key'
                    ];
                }
            } else {
                return [
                    'error' => 'already_verified'
                ];
            }
        } else {
            return [
                'error' => 'invalid_member'
            ];
        }

        return false;
    }

    public static function getPasswordResetKey($user_id, bool $generate_new = false): array
    {
        $result = [];

        if (!is_numeric($user_id) || ($user_id < 1)) {
            $result['error'] = 'invalid_member';

            return $result;
        }

        $member = \IPS\Member::load($user_id);

        if (isset($member->member_id) && ($member->member_id > 0)) {
            $send_email = true;

            try {
                $existing = \IPS\Db::i()->select(array('vid', 'email_sent'), 'core_validating', array('member_id=? AND lost_pass=1', $member->member_id))->first();

                $vid = $existing['vid'];

                /* If we sent a lost password email within the last 15 minutes, don't send another one otherwise someone could be a nuisence */
                if ($existing['email_sent'] && ($existing['email_sent'] > (time() - 900))) {
                    $send_email = false;
                } else {
                    \IPS\Db::i()->update('core_validating', array('email_sent' => time()), array('vid=?', $vid));
                }
            } catch (\UnderflowException $e) {
                if ($generate_new === true) {
                    $vid = md5($member->members_pass_hash . \IPS\Login::generateRandomString());

                    \IPS\Db::i()->insert('core_validating', [
                        'vid' => $vid,
                        'member_id' => $member->member_id,
                        'entry_date' => time(),
                        'lost_pass' => 1,
                        'ip_address' => $member->ip_address,
                        'email_sent' => time()
                    ]);
                } else {
                    $result['error'] = 'not_found';
                }
            }

            if (isset($vid)) {
                $result['key'] = $vid;
                $result['id'] = $member->member_id;
                $result['name'] = $member->name;
                $result['email'] = $member->email;
                $result['send_email'] = $send_email;
            }
        } else {
            $result['error'] = 'invalid_member';
        }

        return $result;
    }

    public static function deletePasswordResetKey($user_id): bool
    {
        if (is_numeric($user_id) && ($user_id > 0)) {
            $member = \IPS\Member::load($user_id);

            if (isset($member->member_id) && ($member->member_id > 0)) {
                /* Reset the failed logins storage - we don't need to save because the login handler will do that for us later */
                $member->failed_logins = array();

                $member->save();

                $member->invalidateSessionsAndLogins();

                \IPS\Member\Device::loadOrCreate($member)->updateAfterAuthentication(isset($_COOKIES[static::COOKIE_LOGIN_KEY]));

                /* Delete validating record and log in */
                \IPS\Db::i()->delete('core_validating', array('member_id=? AND lost_pass=1', $member->member_id));

                return true;
            }
        }

        return false;
    }

    public static function isFilterBanned(string $source, string $key = 'email'): bool
    {
        if (!in_array($key, ['email', 'name', 'ip'])) {
            trigger_error('OSCOM\Invision::isFilterBanned() unknown key: ' . $key);

            return true;
        }

        foreach (\IPS\Db::i()->select('ban_content', 'core_banfilters', array('ban_type=?', $key)) as $filter) {
            if (preg_match('/^' . str_replace('\*', '.*', preg_quote($filter, '/')) . '$/i', $source)) {
                trigger_error('OSCOM\Invision::isFilterBanned(): ' . $source . ' (' . $key . ')');

                return true;
            }
        }

        return false;
    }

    public static function saveUser(int $id, array $data)
    {
        $member = \IPS\Member::load($id);

        if (isset($member->member_id) && ($member->member_id > 0)) {
            $old_email = null;

            if (isset($data['name'])) {
                $member->name = $data['name'];
            }

            if (isset($data['email'])) {
                $old_email = $member->email;

                $member->email = $data['email'];
            }

            if (isset($data['password'])) {
                $member->setLocalPassword($data['password']);
            }

            if (isset($data['group'])) {
                $member->member_group_id = $data['group'];
            }

            if (isset($data['customFields'])) {
                try {
                    $profileFields = \IPS\Db::i()->select('*', 'core_pfields_content', array('member_id=?', $member->member_id))->first();
                } catch (\UnderflowException $e) {
                    $profileFields = array();
                }

                /* If \IPS\Db::i()->select()->first() has only one column, then the contents of that column is returned. We do not want this here. */
                if (!is_array($profileFields)) {
                    $profileFields = array();
                }

                $profileFields['member_id'] = $member->member_id;

                foreach ($data['customFields'] as $k => $v) {
                    $profileFields['field_' . $k] = $v;
                }

                \IPS\Db::i()->replace('core_pfields_content', $profileFields);

                $member->changedCustomFields = $profileFields;
            }

            $member->save();

            if (isset($data['email'])) {
                $member->memberSync('onEmailChange', array($data['email'], $old_email));
            }

            if (isset($data['password'])) {
                $member->memberSync('onPassChange', array($data['password']));
            }

            return static::getUserDataArray($member);
        }

        return false;
    }

    public static function canLogin(string $username, string $password)
    {
        $login = new \IPS\Login(\IPS\Http\Url::external(OSCOM::getLink('Website', 'Account', 'Login', 'SSL')));

        $success = null;
        $fails = [];

        foreach ($login->usernamePasswordMethods() as $method) {
            try {
                $member = $method->authenticateUsernamePassword($login, $username, $password);

                if ($member->member_id) {
                    \IPS\Login::checkIfAccountIsLocked($member, true);
                    $success = new \IPS\Login\Success($member, $method, isset( \IPS\Request::i()->remember_me ));
                    break;
                }
            } catch (\IPS\Login\Exception $e) {
                if ($e->getCode() === \IPS\Login\Exception::BAD_PASSWORD and $e->member) {
                    $fails[$e->member->member_id] = $e->member;
                }
            }
        }

        foreach ($fails as $failedMember) {
            if (!$success or $success->member->member_id != $failedMember->member_id) {
                $failedLogins = is_array($failedMember->failed_logins) ? $failedMember->failed_logins : array();
                $failedLogins[\IPS\Request::i()->ipAddress()][] = time();
                $failedMember->failed_logins = $failedLogins;
                $failedMember->save();
            }
        }

        if ($success) {
            return static::getUserDataArray(\IPS\Member::load($success->member->member_id));
        }

        return false;
    }

    public static function canAutoLogin()
    {
        $device = null;

        if (isset($_COOKIE[static::COOKIE_DEVICE_KEY]) && isset($_COOKIE[static::COOKIE_MEMBER_ID]) && isset($_COOKIE[static::COOKIE_LOGIN_KEY])) {
            /* Get the member we're trying to authenticate against - do not process cookie-based login if the account is locked */
            $member = \IPS\Member::load((int)$_COOKIE[static::COOKIE_MEMBER_ID]);

            if ($member->member_id && $member->unlockTime() === false) {
                /* Load and authenticate device device data */
                try {
                    /* Authenticate */
                    $device = \IPS\Member\Device::loadAndAuthenticate($_COOKIE[static::COOKIE_DEVICE_KEY], $member, $_COOKIE[static::COOKIE_LOGIN_KEY]);

                    /* Refresh the device key cookie */
                    $expire = new \DateTime();
                    $expire->add(new \DateInterval('P1Y'));

                    OSCOM::setCookie(static::COOKIE_DEVICE_KEY, $_COOKIE[static::COOKIE_DEVICE_KEY], $expire->getTimestamp(), null, null, true, true);

                    /* Update device */
                    $device->updateAfterAuthentication( TRUE, NULL, FALSE );

                    return static::getUserDataArray($member);
                } catch (\OutOfRangeException $e) {
                    /* If the device_key/login_key combination wasn't valid, this may be someone trying to bruteforce... */
                    /* ... so log it as a failed login */
                    $failedLogins = is_array($member->failed_logins) ? $member->failed_logins : array();
                    $failedLogins[OSCOM::getIPAddress()][] = time();

                    $member->failed_logins = $failedLogins;
                    $member->save();

                    static::killCookies();
                }
            } else {
                // If the member no longer exists, or the account is locked, set us as a guest and clear out those cookies
                static::killCookies();
            }
        }

        return false;
    }

    public static function findMembers(string $search): array
    {
        $result = [];

        if (empty($search)) {
            return $result;
        }

        try {
            $result = \IPS\Db::i()->select('member_id as id, name', 'core_members', array('name like ? and member_group_id not in (2, 5)', $search . '%'), 'name', 5);

            $result = iterator_to_array($result);
        } catch (\UnderflowException $e) {
        }

        if (!is_array($result)) {
            $result = [];
        }

        return $result;
    }

    public static function findMemberTopics(int $user_id, string $search, array $forum_filter = null): array
    {
        $result = [];

        if (empty($search)) {
            return $result;
        }

        $table_prefix = \IPS\Db::i()->prefix;

        $sql_query = 'select t.tid as id, t.title, t.title_seo, t.forum_id, l.word_default as forum_title from ' . $table_prefix . 'forums_topics t, ' . $table_prefix . 'core_sys_lang_words l where t.starter_id = ? and t.title like ? and t.state = "open" ';

        if (!empty($forum_filter)) {
            $ids = [];

            foreach ($forum_filter as $filter) {
                if (is_numeric($filter) && !in_array((int)$filter, $ids)) {
                    $ids[] = (int)$filter;
                }
            }

            if (!empty($ids)) {
                $filter_ids = implode(', ', $ids);

                $sql_query .= <<<EOD
and t.forum_id in (
  select id from {$table_prefix}forums_forums where parent_id in ({$filter_ids})
    UNION
      select id from {$table_prefix}forums_forums where parent_id in (select id from {$table_prefix}forums_forums where parent_id in ({$filter_ids}))
)
EOD;
            }
        }

        $sql_query .= ' and l.word_key = concat("forums_forum_", t.forum_id) order by title limit 5';

        try {
            $stmt = \IPS\Db::i()->preparedQuery($sql_query, [
                (int)$user_id,
                '%' . $search . '%'
            ]);

            $stmt->execute();

            $result = $stmt->get_result();

            $result = $result->fetch_all(\MYSQLI_ASSOC);
        } catch (\UnderflowException $e) {
        }

        if (!is_array($result)) {
            $result = [];
        }

        return $result;
    }

    public static function getMemberTopic(int $user_id, int $topic_id, array $forum_filter = null): array
    {
        $table_prefix = \IPS\Db::i()->prefix;

        $result = [];

        $sql_query = 'select t.tid as id, t.title, t.title_seo, t.forum_id, l.word_default as forum_title from ' . $table_prefix . 'forums_topics t, ' . $table_prefix . 'core_sys_lang_words l where t.tid = ? and t.starter_id = ? and t.state = "open" ';

        if (!empty($forum_filter)) {
            $ids = [];

            foreach ($forum_filter as $filter) {
                if (is_numeric($filter) && !in_array((int)$filter, $ids)) {
                    $ids[] = (int)$filter;
                }
            }

            if (!empty($ids)) {
                $filter_ids = implode(', ', $ids);

                $sql_query .= <<<EOD
and t.forum_id in (
  select id from {$table_prefix}forums_forums where parent_id in ({$filter_ids})
    UNION
      select id from {$table_prefix}forums_forums where parent_id in (select id from {$table_prefix}forums_forums where parent_id in ({$filter_ids}))
)
EOD;
            }
        }

        $sql_query .= ' and l.word_key = concat("forums_forum_", t.forum_id)';

        try {
            $stmt = \IPS\Db::i()->preparedQuery($sql_query, [
                (int)$topic_id,
                (int)$user_id
            ]);

            $stmt->execute();

            $result = $stmt->get_result();

            $result = $result->fetch_array(\MYSQLI_ASSOC);
        } catch (\UnderflowException $e) {
        }

        if (!is_array($result)) {
            $result = [];
        }

        return $result;
    }

    protected static function getUserDataArray(\IPS\Member $member): array
    {
//        $extra = $member->apiOutput();

        $group = \IPS\Member\Group::load($member->member_group_id);

        $extra = [
            'customFields' => []
        ];

        $fieldData = \IPS\core\ProfileFields\Field::fieldData();

        try {
            $fieldValues = \IPS\Db::i()->select('*', 'core_pfields_content', array('member_id=?', $member->member_id))->first();
        } catch (\UnderflowException $e) {
            $fieldValues = array();
        }

        /* If \IPS\Db::i()->select()->first() has only one column, then the contents of that column is returned. We do not want this here. */
        if (!is_array($fieldValues)) {
            $fieldValues = array();
        }

        foreach ($fieldData as $profileFieldGroup => $profileFields) {
            foreach ($profileFields as $field) {
                $extra['customFields'][$profileFieldGroup]['fields'][$field['pf_id']]['value'] = $fieldValues['field_' . $field['pf_id']] ?? null;
            }
        }

        $val_newreg_id = null;

        try {
            $val_newreg_id = \IPS\Db::i()->select('*', 'core_validating', array('member_id=? AND new_reg=1', (int)$member->member_id))->first()['vid'];
        } catch (\UnderflowException $e) {
        }

        return [
            'id' => (int)$member->member_id,
            'name' => $member->name,
            'formatted_name' => $group->formatName($member->name),
            'full_name' => $extra['customFields'][2]['fields'][1]['value'],
            'title' => $member->member_title,
            'email' => $member->email,
            'group_id' => (int)$member->member_group_id,
            'is_ambassador' => (int)$member->member_group_id === Users::GROUP_AMBASSADOR_ID,
            'amb_level' => (int)$extra['customFields'][3]['fields'][23]['value'] ?? 0,
            'admin' => (int)$member->member_group_id === Users::GROUP_ADMIN_ID,
            'team' => in_array((int)$member->member_group_id, [Users::GROUP_TEAM_CORE_ID, Users::GROUP_TEAM_COMMUNITY_ID]),
            'verified' => (bool)$member->members_bitoptions['validating'] === false,
            'banned' => (int)$member->temp_ban !== 0,
            'restricted_post' => ((int)$member->restrict_post !== 0) || ((int)$member->mod_posts !== 0),
            'joined' => $member->joined->rfc3339(),
            'posts' => (int)$member->member_posts,
            'photo_url' => static::getPhotoUrl($member->getDataArray(), false),
            'val_newreg_id' => $val_newreg_id
        ];
    }

    public static function setCookies(array $member, bool $remember_me)
    {
        $member = \IPS\Member::load((int)$member['id']);

        \IPS\Member\Device::loadOrCreate($member)->updateAfterAuthentication($remember_me);
    }

    public static function killCookies()
    {
        $cookies = [
            static::COOKIE_LOGIN_KEY,
            static::COOKIE_MEMBER_ID,
            static::COOKIE_SESSION_NAME
        ];

        foreach ($cookies as $c) {
            if (isset($_COOKIE[$c])) {
                unset($_COOKIE[$c]);

                OSCOM::setCookie($c, '', time() - 31536000, null, null, true, true);
            }
        }
    }

    public static function getTotalUsers(): int
    {
        try {
            $result = \IPS\Db::i()->select('count(*)', 'core_members')->first();
        } catch (\UnderflowException $e) {
        }

        if (!isset($result) || !is_numeric($result)) {
            $result = static::DEFAULT_TOTAL_USERS;
        }

        return $result;
    }

    public static function getTotalOnlineUsers(): int
    {
        try {
            $result = \IPS\Db::i()->select('count(*)', 'core_sessions', 'running_time > unix_timestamp(date_sub(now(), interval 60 minute))')->first();
        } catch (\UnderflowException $e) {
        }

        if (!isset($result) || !is_numeric($result)) {
            $result = static::DEFAULT_TOTAL_ONLINE_USERS;
        }

        return $result;
    }

    public static function getTotalPostings(): int
    {
        $table_prefix = \IPS\Db::i()->prefix;

        $sql_query = 'select (select count(*) from ' . $table_prefix . 'forums_posts) + (select count(*) from ' . $table_prefix . 'forums_archive_posts) as total';

        try {
            $stmt = \IPS\Db::i()->query($sql_query);

            $result = $stmt->fetch_array(\MYSQLI_ASSOC);

            $result = $result['total'];
        } catch (\UnderflowException $e) {
        }

        if (!isset($result) || !is_numeric($result)) {
            $result = static::DEFAULT_TOTAL_POSTINGS;
        }

        return $result;
    }

    public static function getPhotoUrl(array $memberData, $thumb = true, $email = false, $useDefaultPhoto = true)
    {
        $gravatar = false;
        $photoUrl = null;

        /* All this only applies to members... */
        if (isset($memberData['member_id']) and $memberData['member_id']) {
            /* Is Gravatar disabled for them? */
            $gravatarDisabled = false;
            if (isset($memberData['members_bitoptions'])) {
                if (is_object($memberData['members_bitoptions'])) {
                    $gravatarDisabled = $memberData['members_bitoptions']['bw_disable_gravatar'];
                } else {
                    $gravatarDisabled = $memberData['members_bitoptions'] & \IPS\Member::$bitOptions['members_bitoptions']['members_bitoptions']['bw_disable_gravatar'];
                }
            }

            /* Either uploaded or synced from social media */
            if ($memberData['pp_main_photo'] and (mb_substr($memberData['pp_photo_type'], 0, 5 ) === 'sync-' or $memberData['pp_photo_type'] === 'custom' or (\IPS\Settings::i()->letter_photos == 'letters' AND $memberData['pp_photo_type'] == 'letter' and $useDefaultPhoto and ($gravatarDisabled OR !\IPS\Settings::i()->allow_gravatars)))) {
                try {
                    $photoUrl = \IPS\File::get('core_Profile', ($thumb and $memberData['pp_thumb_photo']) ? $memberData['pp_thumb_photo'] : $memberData['pp_main_photo'])->url;
                } catch (\InvalidArgumentException $e) {
                }
            }
            /* Gravatar */
            elseif(\IPS\Settings::i()->allow_gravatars and (($memberData['pp_photo_type'] === 'letter' OR $memberData['pp_photo_type'] === 'gravatar') or (!$memberData['pp_photo_type'] and !$gravatarDisabled))) {
//                $photoUrl = \IPS\Theme::i()->resource( 'default_photo.png', 'core', 'global' );
                $photoUrl = OSCOM::getConfig('https_server', 'Website') . OSCOM::getConfig('dir_ws_https_server', 'Website') . OSCOM::getPublicSiteLink('images/default_photo.png', null, 'Website');

                if (empty($memberData['pp_main_photo'])) {
                    if ($photo = \IPS\Member::generateLetterPhoto($memberData)) {
                        $photoUrl = $photo;
                    }
                } else {
                    $photoUrl = \IPS\File::get('core_Profile', ($thumb and $memberData['pp_thumb_photo']) ? $memberData['pp_thumb_photo'] : $memberData['pp_main_photo'] )->url;
                }
                $gravatar = true;
            } elseif(\IPS\Settings::i()->letter_photos == 'letters' AND empty($memberData['pp_main_photo'])) {
                if ($photo = \IPS\Member::generateLetterPhoto($memberData)) {
                    $photoUrl = $photo;

                    if(!$gravatarDisabled AND \IPS\Settings::i()->allow_gravatars) {
                        $gravatar = true;
                    }
                }
            }

            /* Other - This allows an app (such as Gallery) to set the pp_photo_type to a storage container to support custom images without duplicating them */
            elseif ($memberData['pp_photo_type'] and $memberData['pp_photo_type'] != 'none' and mb_strpos($memberData['pp_photo_type'], '_' ) !== false) {
                try {
                    $photoUrl = \IPS\File::get($memberData['pp_photo_type'], $memberData['pp_main_photo'])->url;
                } catch (\InvalidArgumentException $e) {
                    /* If there was an exception, clear these values out - most likely the image or storage container is no longer valid */
                    $member = \IPS\Member::load($memberData['member_id']);
                    $member->pp_photo_type = null;
                    $member->pp_main_photo = null;
                    $member->save();
                }
            }

            if ($gravatar) {
                /* Construct the URL - Gravatar will error for localhost URLs, so if IN_DEV, don't send this (this way also allows us to easily see what is loading from Gravatar).*/
                $photoUrl = \IPS\Http\Url::external('https://secure.gravatar.com/avatar/' . md5(trim(mb_strtolower($memberData['pp_gravatar'] ?: $memberData['email']))))->setQueryString(array(
                    'd'	=> \IPS\IN_DEV ? '' : ($photoUrl instanceof \IPS\Http\Url ? (string) $photoUrl->setScheme(\IPS\Request::i()->isSecure() ? 'https' : 'http') : '')
                ));
            }

            /* If we're in the ACP, munge because this is an external resource, but not for locally uploaded files or letter avatars */
            if (
                \IPS\Dispatcher::hasInstance() AND
                \IPS\Dispatcher::i()->controllerLocation === 'admin' AND
                ($photoUrl instanceof \IPS\Http\Url) AND
                ($gravatar === TRUE OR !in_array($memberData['pp_photo_type'], array('custom', 'letter')))
            )
            {
                $photoUrl = $photoUrl->makeSafeForAcp( TRUE );
            }

            /* Return */
            if ($photoUrl !== null) {
                return (string) $photoUrl;
            }
        }

        /* Still here? Return default photo */
        if (!$photoUrl and $useDefaultPhoto) {
            if ($email) {
                return rtrim( \IPS\Settings::i()->base_url, '/' ) . '/applications/core/interface/email/default_photo.png';
            } else {
                if ($photo = \IPS\Member::generateLetterPhoto($memberData)) {
                    return (string) $photo;
                }

//                return (string) \IPS\Theme::i()->resource( 'default_photo.png', 'core', 'global' );
                return OSCOM::getConfig('https_server', 'Website') . OSCOM::getConfig('dir_ws_https_server', 'Website') . OSCOM::getPublicSiteLink('images/default_photo.png', null, 'Website');
            }
        }

        return null;
    }
}
