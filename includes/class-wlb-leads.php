<?php
if (!defined('ABSPATH')) exit;

class WLB_Leads {
    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'wlb_pro_leads';
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
            'user_ip' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => sanitize_textarea_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ]);
        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    public static function latest(int $limit = 100): array {
        global $wpdb;
        $limit = max(1, min(500, $limit));
        return $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . self::table() . ' ORDER BY created_at DESC LIMIT %d', $limit), ARRAY_A) ?: [];
    }

    public static function count_total(): int {
        global $wpdb;
        return (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . self::table());
    }

    public static function count_today(): int {
        global $wpdb;
        return (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . self::table() . ' WHERE DATE(created_at) = CURDATE()');
    }

    public static function export_csv(): void {
        if (!current_user_can('manage_options')) wp_die('Sem permissão.');
        $leads = self::latest(5000);
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=whatsapp-leads-' . date('Y-m-d-H-i') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Data', 'Formulário', 'Nome', 'Telefone', 'E-mail', 'Cidade', 'Serviço', 'Orçamento', 'Urgência', 'Mensagem', 'Origem'], ';');
        foreach ($leads as $lead) {
            fputcsv($output, [$lead['created_at'], $lead['form_name'], $lead['name'], $lead['phone'], $lead['email'], $lead['city'], $lead['service'], $lead['budget'], $lead['urgency'], $lead['message'], $lead['source_url']], ';');
        }
        fclose($output);
        exit;
    }

    public static function maybe_notify(array $lead): void {
        $settings = WLB_Settings::get();
        if ($settings['enable_email_notification'] !== '1') return;
        $to = $settings['notification_email'] ?: get_option('admin_email');
        $subject = 'Novo lead recebido pelo WhatsApp Lead Booster';
        $body = "Novo lead recebido:\n\n";
        foreach (['name'=>'Nome','phone'=>'Telefone','email'=>'E-mail','city'=>'Cidade','service'=>'Serviço','budget'=>'Orçamento','urgency'=>'Urgência','message'=>'Mensagem','source_url'=>'Origem'] as $key => $label) {
            $body .= $label . ': ' . ($lead[$key] ?? '') . "\n";
        }
        wp_mail($to, $subject, $body);
    }
}
