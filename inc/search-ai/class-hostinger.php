<?php
/** Optional Hostinger Tools adapter. Reads status; writes only the Web2Agent opt-in, and only when an administrator asks. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Hostinger {
    public static function status($refresh = false) {
        // Views and the llms.txt renderer both ask for this within one request.
        static $cached = null;
        if (! $refresh && null !== $cached) { return $cached; }
        $cached = self::read_status();
        return $cached;
    }

    private static function read_status() {
        // Match Hostinger's own platform signal; plugin presence alone is not hosting proof.
        $platform = ! empty($_SERVER['H_PLATFORM']);
        $active = defined('HOSTINGER_WORDPRESS_PLUGIN_VERSION');
        $result = array(
            'relevant' => $platform || $active,
            'platform' => $platform,
            'active' => $active,
            'version' => $active ? HOSTINGER_WORDPRESS_PLUGIN_VERSION : '',
            'llms_enabled' => null,
            'web2agent_enabled' => null,
            'available' => false,
            'temporary_domain' => false,
            'endpoint' => '',
            'settings_url' => '',
        );
        if (! $active) { return $result; }

        if (defined('Hostinger\\Admin\\Menu::MENU_SLUG')) {
            $result['settings_url'] = admin_url('admin.php?page=' . constant('Hostinger\\Admin\\Menu::MENU_SLUG'));
        }
        $domain = strtolower((string) wp_parse_url(site_url(), PHP_URL_HOST));
        $result['temporary_domain'] = (bool) preg_match('/(^|\.)hostingersite\.(com|dev)$/i', $domain);
        // Read only the two feature flags through the plugin's public settings accessor.
        try {
            if (class_exists('Hostinger\\Admin\\PluginSettings') && method_exists('Hostinger\\Admin\\PluginSettings', 'get_plugin_settings')) {
                $settings = (new \Hostinger\Admin\PluginSettings())->get_plugin_settings();
                if (is_callable(array($settings, 'get_enable_llms_txt'))) {
                    $result['llms_enabled'] = (bool) $settings->get_enable_llms_txt();
                }
                if (is_callable(array($settings, 'get_optin_mcp'))) {
                    $result['web2agent_enabled'] = (bool) $settings->get_optin_mcp();
                }
            }
        } catch (\Throwable $error) {
            // An incompatible optional plugin must never break Search & AI.
            return $result;
        }
        $result['available'] = $platform && ! $result['temporary_domain'] && null !== $result['web2agent_enabled'];
        if ($result['available'] && $result['web2agent_enabled'] && $domain) {
            // Hostinger's LlmsTxtParser uses the site_url hostname, not a customer constant.
            $result['endpoint'] = 'https://websites-agents.hostinger.com/' . rawurlencode($domain) . '/mcp';
        }
        return $result;
    }

    /**
     * The llms.txt entry advertising this site's Web2Agent endpoint.
     *
     * Hostinger only advertises the agent from inside its own generated file.
     * Emitting it here means an administrator can disable that competing file
     * without losing the advertisement.
     */
    public static function agent_link() {
        if (! AJNanda_Search_AI_Settings::get('search_ai_llms_advertise_agent', true)) { return ''; }
        $status = self::status();
        if (empty($status['available']) || empty($status['web2agent_enabled']) || '' === $status['endpoint']) { return ''; }
        return '- [Agent (MCP protocol)](' . $status['endpoint'] . '): Hostinger Web2Agent endpoint for AI clients that speak MCP.';
    }

    /**
     * Turn Web2Agent on through Hostinger's own settings API.
     *
     * Hostinger hooks the generic updated_option, so saving through its public
     * accessor fires the same opt-in notification its own screen does. The
     * file-generation flag is deliberately left untouched.
     *
     * @return true|WP_Error
     */
    public static function enable_web2agent() {
        $status = self::status(true);
        if (! $status['active']) {
            return new WP_Error('ajnanda_hostinger_inactive', __('Hostinger Tools is not active on this site.', 'ajnanda'));
        }
        if (! empty($status['web2agent_enabled'])) { return true; }
        if (null === $status['web2agent_enabled']) {
            return new WP_Error('ajnanda_hostinger_unsupported', __('This version of Hostinger Tools does not expose the Web2Agent setting. Use the Hostinger Tools screen.', 'ajnanda'));
        }
        if ($status['temporary_domain']) {
            return new WP_Error('ajnanda_hostinger_temporary', __('Hostinger disables Web2Agent on temporary domains. Connect a domain in Hostinger first.', 'ajnanda'));
        }
        try {
            if (! class_exists('Hostinger\\Admin\\PluginSettings')) {
                return new WP_Error('ajnanda_hostinger_unsupported', __('Hostinger settings are unavailable.', 'ajnanda'));
            }
            $api = new \Hostinger\Admin\PluginSettings();
            $options = $api->get_plugin_settings();
            if (! is_callable(array($options, 'set_optin_mcp')) || ! is_callable(array($api, 'save_plugin_settings'))) {
                return new WP_Error('ajnanda_hostinger_unsupported', __('This version of Hostinger Tools does not support saving the Web2Agent setting.', 'ajnanda'));
            }
            $options->set_optin_mcp(true);
            $api->save_plugin_settings($options);
        } catch (\Throwable $error) {
            return new WP_Error('ajnanda_hostinger_error', __('Hostinger Tools rejected the change.', 'ajnanda'));
        }
        $after = self::status(true);
        if (empty($after['web2agent_enabled'])) {
            return new WP_Error('ajnanda_hostinger_unsaved', __('Hostinger did not record the Web2Agent opt-in. Use the Hostinger Tools screen.', 'ajnanda'));
        }
        return true;
    }
}
