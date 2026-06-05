<?php
if (!defined('ABSPATH')) exit;

class WLBP_Leads {
    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'wlbp_leads';
    }

    public static function create(array $data): int {
        global $wpdb;

        $inserted = $wpdb->insert(self::table(), [
            'form_name' => sanitize_text_field($data['form_name'] ?? 'Principal'),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'service' => sanitize_text_field($data['service'] ?? ''),
            'budget' => sanitize_text_field($data['budget'] ?? ''),
            'urgency' => sanitize_text_field($data['urgency'] ?? ''),
            'message' => sanitize_textarea_field($data['message'] ?? ''),
            'source_url' => esc_url_raw($data['source_url'] ?? ''),
            'status' => 'novo',
            'user_ip' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => sanitize_textarea_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ]);

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    public static function query(array $filters = [], int $limit = 300): array {
        global $wpdb;

        $where = ['1=1'];
        $params = [];

        if (!empty($filters['service'])) {
            $where[] = 'service = %s';
            $params[] = sanitize_text_field($filters['service']);
        }

        if (!empty($filters['date_start'])) {
            $where[] = 'DATE(created_at) >= %s';
            $params[] = sanitize_text_field($filters['date_start']);
        }

        if (!empty($filters['date_end'])) {
            $where[] = 'DATE(created_at) <= %s';
            $params[] = sanitize_text_field($filters['date_end']);
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = sanitize_text_field($filters['status']);
        }

        $limit = max(1, min(5000, $limit));
        $sql = "SELECT * FROM " . self::table() . " WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT {$limit}";

        if ($params) {
            $results = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        } else {
            $results = $wpdb->get_results($sql, ARRAY_A);
        }

        return $results ?: [];
    }

    public static function latest(int $limit = 100): array {
        return self::query([], $limit);
    }

    public static function delete(int $id): void {
        global $wpdb;
        $wpdb->delete(self::table(), ['id' => $id]);
    }

    public static function count_total(): int {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM " . self::table());
    }

    public static function count_today(): int {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM " . self::table() . " WHERE DATE(created_at) = CURDATE()");
    }

    public static function last_30_days(): array {
        global $wpdb;

        $rows = $wpdb->get_results("
            SELECT DATE(created_at) AS day, COUNT(*) AS total
            FROM " . self::table() . "
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ", ARRAY_A) ?: [];

        $map = [];
        foreach ($rows as $row) $map[$row['day']] = (int) $row['total'];

        $days = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $days[] = ['label' => date('d/m', strtotime($day)), 'total' => $map[$day] ?? 0];
        }

        return $days;
    }

    public static function export_csv(): void {
        if (!current_user_can('manage_options')) wp_die('Sem permissão.');

        $leads = self::query([
            'service' => $_GET['service'] ?? '',
            'date_start' => $_GET['date_start'] ?? '',
            'date_end' => $_GET['date_end'] ?? '',
            'status' => $_GET['status'] ?? '',
        ], 5000);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=whatsapp-leads-' . date('Y-m-d-H-i') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Data', 'Status', 'Nome', 'Telefone', 'E-mail', 'Cidade', 'Serviço', 'Orçamento', 'Urgência', 'Mensagem', 'Origem'], ';');

        foreach ($leads as $lead) {
            fputcsv($output, [
                $lead['created_at'], $lead['status'], $lead['name'], $lead['phone'], $lead['email'],
                $lead['city'], $lead['service'], $lead['budget'], $lead['urgency'], $lead['message'], $lead['source_url']
            ], ';');
        }

        fclose($output);
        exit;
    }

    public static function maybe_notify(array $lead): void {
        $settings = WLBP_Settings::get();
        if ($settings['enable_email_notification'] !== '1') return;

        $to = $settings['notification_email'] ?: get_option('admin_email');
        $subject = 'Novo lead recebido pelo WhatsApp Lead Booster';

        $body = "Novo lead recebido:\n\n";
        foreach (['name'=>'Nome','phone'=>'Telefone','email'=>'E-mail','city'=>'Cidade','service'=>'Serviço','budget'=>'Orçamento','urgency'=>'Urgência','message'=>'Mensagem','source_url'=>'Origem'] as $key => $label) {
            $body .= $label . ': ' . ($lead[$key] ?? '') . "\n";
        }

        wp_mail($to, $subject, $body);
    }

    public static function send_to_google_sheets(array $lead): void {
        $settings = WLBP_Settings::get();
        if ($settings['enable_google_sheets'] !== '1' || empty($settings['google_sheets_webhook'])) return;

        wp_remote_post($settings['google_sheets_webhook'], [
            'timeout' => 10,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode($lead),
        ]);
    }
}
