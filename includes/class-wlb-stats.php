<?php
if (!defined('ABSPATH')) exit;

class WLB_Stats {
    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'wlb_pro_events';
    }

    public static function track(string $event_type, string $source_url = ''): void {
        global $wpdb;
        $wpdb->insert(self::table(), [
            'event_type' => sanitize_text_field($event_type),
            'source_url' => esc_url_raw($source_url),
        ]);
    }

    public static function count_event(string $event_type): int {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . self::table() . ' WHERE event_type = %s', $event_type));
    }

    public static function conversion_rate(): float {
        $views = max(1, self::count_event('view'));
        return round((WLB_Leads::count_total() / $views) * 100, 2);
    }
}
