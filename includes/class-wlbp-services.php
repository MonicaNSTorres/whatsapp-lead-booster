<?php
if (!defined('ABSPATH')) exit;

class WLBP_Services {
    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'wlbp_services';
    }

    public static function all(bool $only_active = false): array {
        global $wpdb;
        $where = $only_active ? 'WHERE active = 1' : '';
        return $wpdb->get_results("SELECT * FROM " . self::table() . " {$where} ORDER BY name ASC", ARRAY_A) ?: [];
    }

    public static function create(string $name, string $description = ''): void {
        global $wpdb;
        if (!$name) return;
        $wpdb->insert(self::table(), [
            'name' => sanitize_text_field($name),
            'description' => sanitize_textarea_field($description),
            'active' => 1,
        ]);
    }

    public static function toggle(int $id): void {
        global $wpdb;
        $current = $wpdb->get_var($wpdb->prepare("SELECT active FROM " . self::table() . " WHERE id = %d", $id));
        $wpdb->update(self::table(), ['active' => $current ? 0 : 1], ['id' => $id]);
    }

    public static function delete(int $id): void {
        global $wpdb;
        $wpdb->delete(self::table(), ['id' => $id]);
    }
}
