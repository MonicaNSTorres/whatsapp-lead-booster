<?php
if (!defined('ABSPATH')) exit;

class WLB_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'settings']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('admin_post_wlb_export_csv', ['WLB_Leads', 'export_csv']);
    }

    public function assets(string $hook): void {
        if (strpos($hook, 'wlb-pro') === false) return;
        wp_enqueue_style('wlb-pro-admin', WLB_PRO_URL . 'assets/css/admin.css', [], WLB_PRO_VERSION);
    }

    public function menu(): void {
        add_menu_page('WhatsApp Lead Booster PRO', 'WhatsApp Booster', 'manage_options', 'wlb-pro-dashboard', [$this, 'dashboard_page'], 'dashicons-whatsapp', 26);
        add_submenu_page('wlb-pro-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'wlb-pro-dashboard', [$this, 'dashboard_page']);
        add_submenu_page('wlb-pro-dashboard', 'Leads', 'Leads', 'manage_options', 'wlb-pro-leads', [$this, 'leads_page']);
        add_submenu_page('wlb-pro-dashboard', 'Configurações', 'Configurações', 'manage_options', 'wlb-pro-settings', [$this, 'settings_page']);
    }

    public function settings(): void {
        register_setting('wlb_pro_settings_group', 'wlb_pro_settings', ['WLB_Settings', 'sanitize']);
    }

    public function dashboard_page(): void {
        $total_leads = WLB_Leads::count_total();
        $today_leads = WLB_Leads::count_today();
        $views = WLB_Stats::count_event('view');
        $clicks = WLB_Stats::count_event('whatsapp_click');
        $rate = WLB_Stats::conversion_rate();
        ?>
        <div class="wrap wlb-admin">
            <h1>WhatsApp Lead Booster PRO</h1>
            <p class="wlb-muted">Transforme visitantes do site em clientes no WhatsApp.</p>
            <div class="wlb-grid">
                <div class="wlb-card"><span>Total de leads</span><strong><?php echo esc_html($total_leads); ?></strong></div>
                <div class="wlb-card"><span>Leads hoje</span><strong><?php echo esc_html($today_leads); ?></strong></div>
                <div class="wlb-card"><span>Visualizações</span><strong><?php echo esc_html($views); ?></strong></div>
                <div class="wlb-card"><span>Cliques no WhatsApp</span><strong><?php echo esc_html($clicks); ?></strong></div>
                <div class="wlb-card"><span>Conversão</span><strong><?php echo esc_html($rate); ?>%</strong></div>
            </div>
            <div class="wlb-panel">
                <h2>Como usar</h2>
                <p>Cole este shortcode em qualquer página, post ou widget do Elementor:</p>
                <code>[whatsapp_lead_booster]</code>
            </div>
        </div>
        <?php
    }

    public function leads_page(): void {
        $leads = WLB_Leads::latest(200);
        $export_url = admin_url('admin-post.php?action=wlb_export_csv');
        ?>
        <div class="wrap wlb-admin">
            <h1>Leads recebidos</h1>
            <p><a href="<?php echo esc_url($export_url); ?>" class="button button-primary">Exportar CSV</a></p>
            <table class="widefat fixed striped">
                <thead><tr><th>Data</th><th>Nome</th><th>Telefone</th><th>E-mail</th><th>Cidade</th><th>Serviço</th><th>Orçamento</th><th>Urgência</th><th>Origem</th></tr></thead>
                <tbody>
                <?php if (!$leads): ?><tr><td colspan="9">Nenhum lead recebido ainda.</td></tr><?php endif; ?>
                <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($lead['created_at']))); ?></td>
                        <td><?php echo esc_html($lead['name']); ?></td>
                        <td><?php echo esc_html($lead['phone']); ?></td>
                        <td><?php echo esc_html($lead['email']); ?></td>
                        <td><?php echo esc_html($lead['city']); ?></td>
                        <td><?php echo esc_html($lead['service']); ?></td>
                        <td><?php echo esc_html($lead['budget']); ?></td>
                        <td><?php echo esc_html($lead['urgency']); ?></td>
                        <td><?php echo esc_html(wp_parse_url($lead['source_url'], PHP_URL_PATH) ?: $lead['source_url']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function settings_page(): void {
        $s = WLB_Settings::get();
        ?>
        <div class="wrap wlb-admin">
            <h1>Configurações</h1>
            <form method="post" action="options.php" class="wlb-panel">
                <?php settings_fields('wlb_pro_settings_group'); ?>
                <h2>WhatsApp e formulário</h2>
                <table class="form-table">
                    <tr><th>Número do WhatsApp</th><td><input type="text" name="wlb_pro_settings[whatsapp_number]" value="<?php echo esc_attr($s['whatsapp_number']); ?>" class="regular-text" placeholder="5512999999999"><p class="description">Formato: DDI + DDD + número.</p></td></tr>
                    <tr><th>Nome do formulário</th><td><input type="text" name="wlb_pro_settings[form_name]" value="<?php echo esc_attr($s['form_name']); ?>" class="regular-text"></td></tr>
                    <tr><th>Título</th><td><input type="text" name="wlb_pro_settings[form_title]" value="<?php echo esc_attr($s['form_title']); ?>" class="regular-text"></td></tr>
                    <tr><th>Subtítulo</th><td><textarea name="wlb_pro_settings[form_subtitle]" rows="3" class="large-text"><?php echo esc_textarea($s['form_subtitle']); ?></textarea></td></tr>
                    <tr><th>Texto do botão</th><td><input type="text" name="wlb_pro_settings[button_text]" value="<?php echo esc_attr($s['button_text']); ?>" class="regular-text"></td></tr>
                    <tr><th>Serviços</th><td><textarea name="wlb_pro_settings[services]" rows="6" class="large-text"><?php echo esc_textarea($s['services']); ?></textarea><p class="description">Um item por linha.</p></td></tr>
                    <tr><th>Faixas de orçamento</th><td><textarea name="wlb_pro_settings[budgets]" rows="5" class="large-text"><?php echo esc_textarea($s['budgets']); ?></textarea></td></tr>
                    <tr><th>Urgências</th><td><textarea name="wlb_pro_settings[urgencies]" rows="5" class="large-text"><?php echo esc_textarea($s['urgencies']); ?></textarea></td></tr>
                </table>
                <h2>Notificações e conversões</h2>
                <table class="form-table">
                    <tr><th>Notificação por e-mail</th><td><label><input type="checkbox" name="wlb_pro_settings[enable_email_notification]" value="1" <?php checked($s['enable_email_notification'], '1'); ?>> Enviar e-mail quando chegar lead</label></td></tr>
                    <tr><th>E-mail de destino</th><td><input type="email" name="wlb_pro_settings[notification_email]" value="<?php echo esc_attr($s['notification_email']); ?>" class="regular-text"></td></tr>
                    <tr><th>Eventos</th><td><label><input type="checkbox" name="wlb_pro_settings[enable_meta_pixel]" value="1" <?php checked($s['enable_meta_pixel'], '1'); ?>> Meta Pixel <code>Lead</code></label><br><label><input type="checkbox" name="wlb_pro_settings[enable_google_ads]" value="1" <?php checked($s['enable_google_ads'], '1'); ?>> Google Ads/GA4 <code>generate_lead</code></label></td></tr>
                    <tr><th>Mensagem de sucesso</th><td><input type="text" name="wlb_pro_settings[success_message]" value="<?php echo esc_attr($s['success_message']); ?>" class="regular-text"></td></tr>
                </table>
                <?php submit_button('Salvar configurações'); ?>
            </form>
        </div>
        <?php
    }
}
