<?php
if (!defined('ABSPATH')) exit;

class WLBP_Activator {
    public static function activate(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $leads_table = $wpdb->prefix . 'wlbp_leads';
        $events_table = $wpdb->prefix . 'wlbp_events';
        $services_table = $wpdb->prefix . 'wlbp_services';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE {$leads_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            form_name VARCHAR(160) DEFAULT 'Principal',
            name VARCHAR(160) NOT NULL,
            phone VARCHAR(40) DEFAULT '',
            email VARCHAR(160) DEFAULT '',
            city VARCHAR(160) DEFAULT '',
            service VARCHAR(190) DEFAULT '',
            budget VARCHAR(100) DEFAULT '',
            urgency VARCHAR(100) DEFAULT '',
            message TEXT DEFAULT '',
            source_url TEXT DEFAULT '',
            status VARCHAR(40) DEFAULT 'novo',
            user_ip VARCHAR(100) DEFAULT '',
            user_agent TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY created_at (created_at),
            KEY service (service),
            KEY status (status)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$events_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(60) NOT NULL,
            source_url TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$services_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) NOT NULL,
            description TEXT DEFAULT '',
            active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY active (active)
        ) {$charset_collate};");

        if (!get_option('wlbp_settings')) {
            add_option('wlbp_settings', [
                'whatsapp_number' => '',
                'form_title' => 'Solicite seu orçamento',
                'form_subtitle' => 'Preencha os dados e fale diretamente com nossa equipe pelo WhatsApp.',
                'button_text' => 'Enviar via WhatsApp',
                'form_name' => 'Principal',
                'budgets' => "Até R$ 500\nR$ 500 a R$ 1.500\nR$ 1.500 a R$ 5.000\nAcima de R$ 5.000",
                'urgencies' => "Hoje\nEsta semana\nEste mês\nApenas pesquisando",
                'enable_email_notification' => '0',
                'notification_email' => get_option('admin_email'),
                'enable_meta_pixel' => '0',
                'enable_google_ads' => '0',
                'enable_google_sheets' => '0',
                'google_sheets_webhook' => '',
                'meta_pixel_id' => '',
                'google_ads_id' => '',
                'success_message' => 'Lead recebido! Vamos continuar pelo WhatsApp.',
            ]);
        }

        $count_services = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$services_table}");
        if ($count_services === 0) {
            foreach (['Orçamento', 'Agendamento', 'Consultoria', 'Dúvidas'] as $service) {
                $wpdb->insert($services_table, ['name' => $service, 'active' => 1]);
            }
        }
    }
}
