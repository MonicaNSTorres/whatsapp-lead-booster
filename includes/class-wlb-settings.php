<?php
if (!defined('ABSPATH')) exit;

class WLB_Settings {
    public static function get(): array {
        $defaults = [
            'whatsapp_number' => '',
            'form_title' => 'Solicite seu orçamento',
            'form_subtitle' => 'Preencha os dados e fale diretamente com nossa equipe pelo WhatsApp.',
            'button_text' => 'Receber atendimento no WhatsApp',
            'form_name' => 'Principal',
            'services' => "Orçamento\nAgendamento\nConsultoria\nDúvidas",
            'budgets' => "Até R$ 500\nR$ 500 a R$ 1.500\nR$ 1.500 a R$ 5.000\nAcima de R$ 5.000",
            'urgencies' => "Hoje\nEsta semana\nEste mês\nApenas pesquisando",
            'enable_email_notification' => '0',
            'notification_email' => get_option('admin_email'),
            'enable_meta_pixel' => '0',
            'enable_google_ads' => '0',
            'success_message' => 'Lead recebido! Vamos continuar pelo WhatsApp.',
        ];
        return wp_parse_args(get_option('wlb_pro_settings', []), $defaults);
    }

    public static function sanitize(array $input): array {
        return [
            'whatsapp_number' => preg_replace('/\D+/', '', sanitize_text_field($input['whatsapp_number'] ?? '')),
            'form_title' => sanitize_text_field($input['form_title'] ?? ''),
            'form_subtitle' => sanitize_textarea_field($input['form_subtitle'] ?? ''),
            'button_text' => sanitize_text_field($input['button_text'] ?? ''),
            'form_name' => sanitize_text_field($input['form_name'] ?? 'Principal'),
            'services' => sanitize_textarea_field($input['services'] ?? ''),
            'budgets' => sanitize_textarea_field($input['budgets'] ?? ''),
            'urgencies' => sanitize_textarea_field($input['urgencies'] ?? ''),
            'enable_email_notification' => !empty($input['enable_email_notification']) ? '1' : '0',
            'notification_email' => sanitize_email($input['notification_email'] ?? get_option('admin_email')),
            'enable_meta_pixel' => !empty($input['enable_meta_pixel']) ? '1' : '0',
            'enable_google_ads' => !empty($input['enable_google_ads']) ? '1' : '0',
            'success_message' => sanitize_text_field($input['success_message'] ?? ''),
        ];
    }

    public static function lines_to_options(string $value): array {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value))));
    }
}
