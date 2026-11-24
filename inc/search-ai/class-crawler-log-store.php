<?php
/** Bounded database storage and query layer for crawler observations. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Crawler_Log_Store {
    const SCHEMA_VERSION = 1;
    const VERSION_OPTION = 'ajnanda_crawler_log_schema_version';
    const CLEANUP_HOOK = 'ajnanda_crawler_log_cleanup';
    const VERIFY_HOOK = 'ajnanda_crawler_verify_pending';
    const RELABEL_OPTION = 'ajnanda_crawler_relabel_version';
    const RELABEL_VERSION = 1;
    const SESSION_GAP = 300; // Requests from one crawler less than 5 minutes apart are one crawl session.
    const SESSION_ROW_CAP = 20000;

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'maybe_install'), 1);
        add_action('admin_init', array(__CLASS__, 'maybe_relabel_unknown'), 2);
        add_action('after_switch_theme', array(__CLASS__, 'maybe_install'));
        add_filter('cron_schedules', array(__CLASS__, 'cron_schedules'));
        add_action(self::CLEANUP_HOOK, array(__CLASS__, 'cleanup'));
        add_action(self::VERIFY_HOOK, array('AJNanda_Search_AI_Crawler_Verifier', 'process_pending'));
        add_action('admin_init', array(__CLASS__, 'ensure_schedules'), 20);
        add_action('after_switch_theme', array(__CLASS__, 'ensure_schedules'), 20);
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'ajnanda_crawler_events';
    }

    public static function maybe_install() {
        if ((int) get_option(self::VERSION_OPTION, 0) >= self::SCHEMA_VERSION) { return; }
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $table = self::table();
        dbDelta("CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            observed_at datetime NOT NULL,
            request_path varchar(1000) NOT NULL,
            http_method varchar(10) NOT NULL DEFAULT 'GET',
            http_status smallint(5) unsigned DEFAULT NULL,
            crawler_key varchar(64) NOT NULL,
            provider_key varchar(64) NOT NULL,
            category varchar(64) NOT NULL,
            user_agent text NOT NULL,
            reported_identity varchar(191) NOT NULL,
            verification_state varchar(32) NOT NULL DEFAULT 'reported_only',
            verification_method varchar(64) NOT NULL DEFAULT '',
            verification_reason text NOT NULL,
            ip_value varchar(64) NOT NULL DEFAULT '',
            ip_mode varchar(16) NOT NULL DEFAULT 'anonymized',
            source varchar(32) NOT NULL DEFAULT 'wordpress',
            PRIMARY KEY  (id),
            KEY observed_at (observed_at),
            KEY provider_time (provider_key, observed_at),
            KEY verification_time (verification_state, observed_at),
            KEY category_time (category, observed_at),
            KEY request_path (request_path(191)),
            KEY source_time (source, observed_at)
        ) {$charset};");
        if (self::table_exists()) { update_option(self::VERSION_OPTION, self::SCHEMA_VERSION, false); }
    }

    public static function table_exists() {
        global $wpdb;
        $table = self::table();
        return $table === $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
    }

    public static function insert($event) {
        if (! self::table_exists()) { return 0; }
        global $wpdb;
        $defaults = array('observed_at' => current_time('mysql', true), 'request_path' => '/', 'http_method' => 'GET', 'http_status' => null, 'crawler_key' => 'unknown', 'provider_key' => 'unknown', 'category' => 'unknown', 'user_agent' => '', 'reported_identity' => '', 'verification_state' => 'reported_only', 'verification_method' => '', 'verification_reason' => '', 'ip_value' => '', 'ip_mode' => 'anonymized', 'source' => 'wordpress');
        $event = wp_parse_args($event, $defaults);
        $ok = $wpdb->insert(self::table(), $event, array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
        return false === $ok ? 0 : (int) $wpdb->insert_id;
    }

    public static function update_verification($id, $result) {
        global $wpdb;
        return false !== $wpdb->update(self::table(), array('verification_state' => $result['state'], 'verification_method' => $result['method'], 'verification_reason' => $result['reason']), array('id' => absint($id)), array('%s', '%s', '%s'), array('%d'));
    }

    public static function get($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE id = %d', absint($id)), ARRAY_A);
    }

    public static function query($filters = array()) {
        global $wpdb;
        $filters = self::sanitize_filters($filters);
        list($where, $args) = self::where($filters);
        $offset = ($filters['paged'] - 1) * $filters['per_page'];
        $sql = 'SELECT * FROM ' . self::table() . " {$where} ORDER BY observed_at DESC, id DESC LIMIT %d OFFSET %d";
        $rows = $wpdb->get_results($wpdb->prepare($sql, array_merge($args, array($filters['per_page'], $offset))), ARRAY_A);
        $total = (int) $wpdb->get_var($args ? $wpdb->prepare('SELECT COUNT(*) FROM ' . self::table() . " {$where}", $args) : 'SELECT COUNT(*) FROM ' . self::table() . " {$where}");
        return array('rows' => $rows, 'total' => $total, 'pages' => max(1, (int) ceil($total / $filters['per_page'])), 'filters' => $filters);
    }

    public static function aggregates($filters = array()) {
        global $wpdb;
        $filters = self::sanitize_filters($filters);
        list($where, $args) = self::where($filters);
        $prepare = static function ($sql) use ($wpdb, $args) { return $args ? $wpdb->prepare($sql, $args) : $sql; };
        $table = self::table();
        return array(
            'total' => (int) $wpdb->get_var($prepare("SELECT COUNT(*) FROM {$table} {$where}")),
            'identity_count' => (int) $wpdb->get_var($prepare("SELECT COUNT(DISTINCT crawler_key) FROM {$table} {$where}")),
            'latest' => $wpdb->get_var($prepare("SELECT MAX(observed_at) FROM {$table} {$where}")),
            'providers' => $wpdb->get_results($prepare("SELECT provider_key, reported_identity, COUNT(*) count, MAX(observed_at) latest FROM {$table} {$where} GROUP BY provider_key, reported_identity ORDER BY count DESC LIMIT 10"), ARRAY_A),
            'claimed_providers' => $wpdb->get_results($prepare("SELECT provider_key, reported_identity, COUNT(*) count, MAX(observed_at) latest FROM {$table} {$where} GROUP BY provider_key, reported_identity ORDER BY count DESC LIMIT 10"), ARRAY_A),
            'verified_providers' => $wpdb->get_results($prepare("SELECT provider_key, reported_identity, COUNT(*) count, MAX(observed_at) latest FROM {$table} {$where} AND verification_state = 'verified' GROUP BY provider_key, reported_identity ORDER BY count DESC LIMIT 10"), ARRAY_A),
            'categories' => $wpdb->get_results($prepare("SELECT category, COUNT(*) count FROM {$table} {$where} GROUP BY category ORDER BY count DESC LIMIT 10"), ARRAY_A),
            'verification' => $wpdb->get_results($prepare("SELECT verification_state, COUNT(*) count FROM {$table} {$where} GROUP BY verification_state ORDER BY count DESC"), ARRAY_A),
            'verification_evidence' => $wpdb->get_results($prepare("SELECT verification_state, verification_method, verification_reason, ip_mode, COUNT(*) count FROM {$table} {$where} GROUP BY verification_state, verification_method, verification_reason, ip_mode ORDER BY count DESC LIMIT 20"), ARRAY_A),
            'trust_notice' => __('Provider and crawler names are User-Agent claims unless verification_state is verified. Claimed traffic must not be presented as confirmed provider engagement.', 'ajnanda'),
            'paths' => $wpdb->get_results($prepare("SELECT request_path, COUNT(*) count, MAX(observed_at) latest FROM {$table} {$where} GROUP BY request_path ORDER BY count DESC LIMIT 10"), ARRAY_A),
        );
    }

    /**
     * Per-crawler summary for the "Who's crawling" table.
     * Grouped by crawler key + reported identity so each unknown bot gets its own row.
     */
    public static function crawlers($filters = array()) {
        global $wpdb;
        $filters = self::sanitize_filters($filters);
        list($where, $args) = self::where($filters);
        $sql = 'SELECT crawler_key, provider_key, category, reported_identity, COUNT(*) count, COUNT(DISTINCT request_path) pages, SUM(http_status >= 400) errors, COUNT(DISTINCT ip_value) ips, MIN(observed_at) first_seen, MAX(observed_at) latest, MAX(user_agent) user_agent, SUM(verification_state = \'verified\') verified, SUM(verification_state = \'failed\') failed FROM ' . self::table() . " {$where} GROUP BY crawler_key, provider_key, category, reported_identity ORDER BY count DESC LIMIT 50";
        return $wpdb->get_results($args ? $wpdb->prepare($sql, $args) : $sql, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Group requests into crawl sessions: one crawler identity, consecutive requests
     * no more than SESSION_GAP seconds apart. Sessions are built in PHP (portable across
     * MySQL 5.7/MariaDB without window functions) from at most SESSION_ROW_CAP recent rows.
     *
     * @return array{rows: array, total: int, pages: int, truncated: bool, per_identity: array<string,int>}
     */
    public static function sessions($filters = array()) {
        global $wpdb;
        $filters = self::sanitize_filters($filters);
        list($where, $args) = self::where($filters);
        $sql = 'SELECT id, observed_at, crawler_key, provider_key, category, reported_identity, verification_state, request_path, http_status, ip_value FROM ' . self::table() . " {$where} ORDER BY observed_at DESC, id DESC LIMIT %d";
        $rows = $wpdb->get_results($wpdb->prepare($sql, array_merge($args, array(self::SESSION_ROW_CAP + 1))), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $truncated = count($rows) > self::SESSION_ROW_CAP;
        if ($truncated) { array_pop($rows); }

        // Rows arrive newest first; walk oldest first per identity.
        $by_identity = array();
        foreach (array_reverse($rows) as $row) {
            $by_identity[$row['crawler_key'] . '|' . $row['reported_identity']][] = $row;
        }
        $sessions = array();
        $per_identity = array();
        foreach ($by_identity as $key => $events) {
            $current = null;
            $last_ts = 0;
            foreach ($events as $event) {
                $ts = strtotime($event['observed_at'] . ' UTC');
                if (! $current || $ts - $last_ts > self::SESSION_GAP) {
                    if ($current) { $sessions[] = self::finish_session($current); }
                    $current = array('identity' => $event['reported_identity'], 'crawler_key' => $event['crawler_key'], 'provider_key' => $event['provider_key'], 'category' => $event['category'], 'start' => $event['observed_at'], 'end' => $event['observed_at'], 'events' => array());
                    $per_identity[$key] = ($per_identity[$key] ?? 0) + 1;
                }
                $current['end'] = $event['observed_at'];
                $current['events'][] = $event;
                $last_ts = $ts;
            }
            if ($current) { $sessions[] = self::finish_session($current); }
        }
        usort($sessions, static function ($a, $b) { return strcmp($b['end'], $a['end']); });
        $total = count($sessions);
        $offset = ($filters['paged'] - 1) * $filters['per_page'];
        return array('rows' => array_slice($sessions, $offset, $filters['per_page']), 'total' => $total, 'pages' => max(1, (int) ceil($total / $filters['per_page'])), 'truncated' => $truncated, 'per_identity' => $per_identity, 'filters' => $filters);
    }

    private static function finish_session($session) {
        $statuses = array();
        $states = array();
        $paths = array();
        $ips = array();
        foreach ($session['events'] as $event) {
            $status = $event['http_status'] ? (string) $event['http_status'] : '—';
            $statuses[$status] = ($statuses[$status] ?? 0) + 1;
            $states[$event['verification_state']] = ($states[$event['verification_state']] ?? 0) + 1;
            $paths[$event['request_path']] = true;
            if ('' !== $event['ip_value']) { $ips[$event['ip_value']] = true; }
        }
        arsort($statuses);
        arsort($states);
        $session['count'] = count($session['events']);
        $session['pages'] = count($paths);
        $session['ips'] = count($ips);
        $session['statuses'] = $statuses;
        $session['verification_state'] = (string) key($states);
        $session['duration'] = max(0, strtotime($session['end'] . ' UTC') - strtotime($session['start'] . ' UTC'));
        return $session;
    }

    /**
     * One-time re-label of unknown-crawler rows logged before the classifier named bots by
     * their own product token (older rows were grouped under "Mozilla") and before the
     * registry recognized more crawlers. Runs on admin_init in bounded batches.
     */
    public static function maybe_relabel_unknown() {
        if ((int) get_option(self::RELABEL_OPTION, 0) >= self::RELABEL_VERSION || ! self::table_exists()) { return; }
        global $wpdb;
        $table = self::table();
        $offset = (int) get_option(self::RELABEL_OPTION . '_offset', 0);
        $batch = 200;
        $agents = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT user_agent FROM {$table} WHERE crawler_key = 'unknown-crawler' ORDER BY user_agent LIMIT %d OFFSET %d", $batch, $offset)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $changed = 0;
        foreach ($agents as $agent) {
            $class = AJNanda_Search_AI_Crawler_Logger::classify($agent);
            if (! $class) { continue; }
            $data = array('crawler_key' => sanitize_key($class['crawler_key']), 'provider_key' => sanitize_key($class['provider_key']), 'category' => sanitize_key($class['category']), 'reported_identity' => substr(sanitize_text_field($class['reported_identity']), 0, 191));
            if ('unknown-crawler' !== $data['crawler_key']) { $changed++; }
            $wpdb->update($table, $data, array('crawler_key' => 'unknown-crawler', 'user_agent' => $agent), array('%s', '%s', '%s', '%s'), array('%s', '%s'));
        }
        // Rows that became known crawlers leave the unknown set, so the offset only advances past the rest.
        if (count($agents) < $batch) {
            update_option(self::RELABEL_OPTION, self::RELABEL_VERSION, false);
            delete_option(self::RELABEL_OPTION . '_offset');
        } else {
            update_option(self::RELABEL_OPTION . '_offset', $offset + count($agents) - $changed, false);
        }
    }

    public static function sanitize_filters($input) {
        $days = absint($input['days'] ?? 7);
        if (! in_array($days, array(1, 7, 30, 90, 180, 365), true)) { $days = 7; }
        $states = array('verified', 'reported_only', 'failed', 'not_verifiable', 'pending');
        return array('days' => $days, 'provider' => sanitize_key($input['provider'] ?? ''), 'category' => sanitize_key($input['category'] ?? ''), 'verification' => in_array($input['verification'] ?? '', $states, true) ? $input['verification'] : '', 'identity' => substr(sanitize_text_field(wp_unslash($input['identity'] ?? '')), 0, 191), 'view' => 'requests' === ($input['view'] ?? '') ? 'requests' : 'sessions', 'paged' => max(1, absint($input['paged'] ?? 1)), 'per_page' => 25);
    }

    private static function where($filters) {
        $parts = array('observed_at >= %s');
        $args = array(gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * $filters['days']));
        foreach (array('provider' => 'provider_key', 'category' => 'category', 'verification' => 'verification_state', 'identity' => 'reported_identity') as $filter => $column) {
            if ($filters[$filter]) { $parts[] = "{$column} = %s"; $args[] = $filters[$filter]; }
        }
        return array('WHERE ' . implode(' AND ', $parts), $args);
    }

    public static function cron_schedules($schedules) {
        $schedules['ajnanda_five_minutes'] = array('interval' => 5 * MINUTE_IN_SECONDS, 'display' => __('Every five minutes', 'ajnanda'));
        return $schedules;
    }

    public static function ensure_schedules() {
        if (! wp_next_scheduled(self::CLEANUP_HOOK)) { wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK); }
        if (AJNanda_Search_AI_Settings::get('search_ai_crawler_logging_enabled')) {
            if (! wp_next_scheduled(self::VERIFY_HOOK)) { wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, 'ajnanda_five_minutes', self::VERIFY_HOOK); }
        } else {
            wp_clear_scheduled_hook(self::VERIFY_HOOK);
        }
    }

    public static function unschedule() {
        wp_clear_scheduled_hook(self::CLEANUP_HOOK);
        wp_clear_scheduled_hook(self::VERIFY_HOOK);
    }

    public static function cleanup() {
        if (! self::table_exists()) { return 0; }
        global $wpdb;
        $days = (int) AJNanda_Search_AI_Settings::get('search_ai_log_retention_days', 90);
        if (! in_array($days, array(7, 30, 90, 180, 365), true)) { $days = 90; }
        return (int) $wpdb->query($wpdb->prepare('DELETE FROM ' . self::table() . ' WHERE observed_at < %s ORDER BY observed_at ASC LIMIT 1000', gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * $days)));
    }
}
