<?php
if (!defined('ABSPATH')) exit;

class WLBP_Public {
    public function __construct() {
        add_shortcode('whatsapp_lead_booster', [$this, 'render_form']);
        add_shortcode('whatsapp_orcamento', [$this, 'render_form']);
        add_action('wp_enqueue_scripts', [$this, 'assets']);
        add_action('wp_ajax_wlbp_save_lead', [$this, 'save_lead']);
        add_action('wp_ajax_nopriv_wlbp_save_lead', [$this, 'save_lead']);
        add_action('wp_ajax_wlbp_track_event', [$this, 'track_event']);
        add_action('wp_ajax_nopriv_wlbp_track_event', [$this, 'track_event']);
    }

    public function assets(): void {
        wp_enqueue_style('wlbp-public', WLBP_URL . 'assets/css/public.css', [], WLBP_VERSION);
        wp_enqueue_script('wlbp-public', WLBP_URL . 'assets/js/public.js', [], WLBP_VERSION, true);
        wp_localize_script('wlbp-public', 'WLBP_PUBLIC', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wlbp_nonce'),
        ]);
    }

    public function render_form(): string {
        $s = WLBP_Settings::get();
        $services = WLBP_Services::all(true);
        $budgets = WLBP_Settings::lines_to_options($s['budgets']);
        $urgencies = WLBP_Settings::lines_to_options($s['urgencies']);

        ob_start();
        ?>
        <div class="wlbp-public-card" data-whatsapp="<?php echo esc_attr($s['whatsapp_number']); ?>" data-form-name="<?php echo esc_attr($s['form_name']); ?>" data-success-message="<?php echo esc_attr($s['success_message']); ?>" data-meta-pixel="<?php echo esc_attr($s['enable_meta_pixel']); ?>" data-google-ads="<?php echo esc_attr($s['enable_google_ads']); ?>">
            <div class="wlbp-public-header">
                <span class="wlbp-public-badge">Atendimento rápido</span>
                <h3><?php echo esc_html($s['form_title']); ?></h3>
                <p><?php echo esc_html($s['form_subtitle']); ?></p>
            </div>
            <form class="wlbp-public-form">
                <div class="wlbp-public-grid">
                    <div class="wlbp-public-field"><label>Nome *</label><input type="text" name="name" placeholder="Seu nome" required></div>
                    <div class="wlbp-public-field"><label>WhatsApp *</label><input type="text" name="phone" placeholder="(12) 99999-9999" required></div>
                </div>
                <div class="wlbp-public-grid">
                    <div class="wlbp-public-field"><label>E-mail</label><input type="email" name="email" placeholder="seuemail@email.com"></div>
                    <div class="wlbp-public-field"><label>Cidade</label><input type="text" name="city" placeholder="Sua cidade"></div>
                </div>
                <div class="wlbp-public-field"><label>Serviço de interesse *</label><select name="service" required><option value="">Selecione o serviço</option><?php foreach ($services as $service): ?><option value="<?php echo esc_attr($service['name']); ?>"><?php echo esc_html($service['name']); ?></option><?php endforeach; ?></select></div>
                <div class="wlbp-public-grid">
                    <div class="wlbp-public-field"><label>Faixa de orçamento</label><select name="budget"><option value="">Selecione</option><?php foreach ($budgets as $item): ?><option value="<?php echo esc_attr($item); ?>"><?php echo esc_html($item); ?></option><?php endforeach; ?></select></div>
                    <div class="wlbp-public-field"><label>Urgência</label><select name="urgency"><option value="">Selecione</option><?php foreach ($urgencies as $item): ?><option value="<?php echo esc_attr($item); ?>"><?php echo esc_html($item); ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="wlbp-public-field"><label>Mensagem</label><textarea name="message" rows="4" placeholder="Digite sua mensagem..."></textarea></div>
                <input type="hidden" name="source_url" value="">
                <button type="submit" class="wlbp-public-button"><?php echo esc_html($s['button_text']); ?></button>
                <p class="wlbp-public-alert" hidden></p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function save_lead(): void {
        check_ajax_referer('wlbp_nonce', 'nonce');

        $lead = [
            'form_name' => sanitize_text_field($_POST['form_name'] ?? 'Principal'),
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'service' => sanitize_text_field($_POST['service'] ?? ''),
            'budget' => sanitize_text_field($_POST['budget'] ?? ''),
            'urgency' => sanitize_text_field($_POST['urgency'] ?? ''),
            'message' => sanitize_textarea_field($_POST['message'] ?? ''),
            'source_url' => esc_url_raw($_POST['source_url'] ?? ''),
        ];

        if (!$lead['name'] || !$lead['phone'] || !$lead['service']) {
            wp_send_json_error(['message' => 'Preencha nome, WhatsApp e serviço.']);
        }

        $lead_id = WLBP_Leads::create($lead);
        if (!$lead_id) wp_send_json_error(['message' => 'Não foi possível salvar o lead.']);

        WLBP_Stats::track('lead', $lead['source_url']);
        WLBP_Leads::maybe_notify($lead);
        WLBP_Leads::send_to_google_sheets($lead);

        $s = WLBP_Settings::get();
        $url = 'https://wa.me/' . $s['whatsapp_number'] . '?text=' . rawurlencode(WLBP_WhatsApp::build_message($lead));

        wp_send_json_success(['lead_id' => $lead_id, 'whatsapp_url' => $url, 'message' => $s['success_message']]);
    }

    public function track_event(): void {
        check_ajax_referer('wlbp_nonce', 'nonce');
        $event_type = sanitize_text_field($_POST['event_type'] ?? '');
        $source_url = esc_url_raw($_POST['source_url'] ?? '');

        if (!in_array($event_type, ['view', 'whatsapp_click'], true)) {
            wp_send_json_error(['message' => 'Evento inválido.']);
        }

        WLBP_Stats::track($event_type, $source_url);
        wp_send_json_success(['tracked' => true]);
    }
}
