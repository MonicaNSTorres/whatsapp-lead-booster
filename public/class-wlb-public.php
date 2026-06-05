<?php
if (!defined('ABSPATH')) exit;

class WLB_Public {
    public function __construct() {
        add_shortcode('whatsapp_lead_booster', [$this, 'render_form']);
        add_shortcode('whatsapp_orcamento', [$this, 'render_form']);
        add_action('wp_enqueue_scripts', [$this, 'assets']);
        add_action('wp_ajax_wlb_pro_save_lead', [$this, 'save_lead']);
        add_action('wp_ajax_nopriv_wlb_pro_save_lead', [$this, 'save_lead']);
        add_action('wp_ajax_wlb_pro_track_event', [$this, 'track_event']);
        add_action('wp_ajax_nopriv_wlb_pro_track_event', [$this, 'track_event']);
    }

    public function assets(): void {
        wp_enqueue_style('wlb-pro-public', WLB_PRO_URL . 'assets/css/public.css', [], WLB_PRO_VERSION);
        wp_enqueue_script('wlb-pro-public', WLB_PRO_URL . 'assets/js/public.js', [], WLB_PRO_VERSION, true);
        wp_localize_script('wlb-pro-public', 'WLB_PRO', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wlb_pro_nonce'),
        ]);
    }

    public function render_form(): string {
        $s = WLB_Settings::get();
        $services = WLB_Settings::lines_to_options($s['services']);
        $budgets = WLB_Settings::lines_to_options($s['budgets']);
        $urgencies = WLB_Settings::lines_to_options($s['urgencies']);
        ob_start(); ?>
        <div class="wlb-pro-card" data-whatsapp="<?php echo esc_attr($s['whatsapp_number']); ?>" data-form-name="<?php echo esc_attr($s['form_name']); ?>" data-success-message="<?php echo esc_attr($s['success_message']); ?>" data-meta-pixel="<?php echo esc_attr($s['enable_meta_pixel']); ?>" data-google-ads="<?php echo esc_attr($s['enable_google_ads']); ?>">
            <div class="wlb-pro-header">
                <span class="wlb-pro-badge">Atendimento rápido</span>
                <h3><?php echo esc_html($s['form_title']); ?></h3>
                <p><?php echo esc_html($s['form_subtitle']); ?></p>
            </div>
            <form class="wlb-pro-form">
                <div class="wlb-pro-grid">
                    <div class="wlb-pro-field"><label>Nome *</label><input type="text" name="name" placeholder="Seu nome" required></div>
                    <div class="wlb-pro-field"><label>WhatsApp *</label><input type="text" name="phone" placeholder="(12) 99999-9999" required></div>
                </div>
                <div class="wlb-pro-grid">
                    <div class="wlb-pro-field"><label>E-mail</label><input type="email" name="email" placeholder="seuemail@email.com"></div>
                    <div class="wlb-pro-field"><label>Cidade</label><input type="text" name="city" placeholder="Sua cidade"></div>
                </div>
                <div class="wlb-pro-field"><label>Serviço desejado *</label><select name="service" required><option value="">Selecione uma opção</option><?php foreach ($services as $item): ?><option value="<?php echo esc_attr($item); ?>"><?php echo esc_html($item); ?></option><?php endforeach; ?></select></div>
                <div class="wlb-pro-grid">
                    <div class="wlb-pro-field"><label>Faixa de orçamento</label><select name="budget"><option value="">Selecione</option><?php foreach ($budgets as $item): ?><option value="<?php echo esc_attr($item); ?>"><?php echo esc_html($item); ?></option><?php endforeach; ?></select></div>
                    <div class="wlb-pro-field"><label>Urgência</label><select name="urgency"><option value="">Selecione</option><?php foreach ($urgencies as $item): ?><option value="<?php echo esc_attr($item); ?>"><?php echo esc_html($item); ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="wlb-pro-field"><label>Mensagem</label><textarea name="message" rows="4" placeholder="Conte rapidamente o que você precisa"></textarea></div>
                <input type="hidden" name="source_url" value="">
                <button type="submit" class="wlb-pro-button"><?php echo esc_html($s['button_text']); ?></button>
                <p class="wlb-pro-alert" hidden></p>
            </form>
        </div>
        <?php return ob_get_clean();
    }

    public function save_lead(): void {
        check_ajax_referer('wlb_pro_nonce', 'nonce');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $service = sanitize_text_field($_POST['service'] ?? '');
        if (!$name || !$phone || !$service) wp_send_json_error(['message' => 'Preencha nome, WhatsApp e serviço.']);
        $lead = [
            'form_name' => sanitize_text_field($_POST['form_name'] ?? 'Principal'),
            'name' => $name,
            'phone' => $phone,
            'email' => sanitize_email($_POST['email'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'service' => $service,
            'budget' => sanitize_text_field($_POST['budget'] ?? ''),
            'urgency' => sanitize_text_field($_POST['urgency'] ?? ''),
            'message' => sanitize_textarea_field($_POST['message'] ?? ''),
            'source_url' => esc_url_raw($_POST['source_url'] ?? ''),
        ];
        $lead_id = WLB_Leads::create($lead);
        if (!$lead_id) wp_send_json_error(['message' => 'Não foi possível salvar o lead.']);
        WLB_Stats::track('lead', $lead['source_url']);
        WLB_Leads::maybe_notify($lead);
        $settings = WLB_Settings::get();
        $url = 'https://wa.me/' . $settings['whatsapp_number'] . '?text=' . rawurlencode(WLB_WhatsApp::build_message($lead));
        wp_send_json_success(['lead_id' => $lead_id, 'whatsapp_url' => $url, 'message' => $settings['success_message']]);
    }

    public function track_event(): void {
        check_ajax_referer('wlb_pro_nonce', 'nonce');
        $event_type = sanitize_text_field($_POST['event_type'] ?? '');
        $source_url = esc_url_raw($_POST['source_url'] ?? '');
        if (!in_array($event_type, ['view', 'whatsapp_click'], true)) wp_send_json_error(['message' => 'Evento inválido.']);
        WLB_Stats::track($event_type, $source_url);
        wp_send_json_success(['tracked' => true]);
    }
}
