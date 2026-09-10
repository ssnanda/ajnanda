<?php
/** Optional, read-only Hostinger Tools adapter. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Hostinger {
    public static function status() {
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
}
