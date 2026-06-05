<?php
if (!defined('ABSPATH')) exit;

class WLBP_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'settings']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('admin_post_wlbp_export_csv', ['WLBP_Leads', 'export_csv']);
        add_action('admin_post_wlbp_delete_lead', [$this, 'delete_lead']);
        add_action('admin_post_wlbp_add_service', [$this, 'add_service']);
        add_action('admin_post_wlbp_toggle_service', [$this, 'toggle_service']);
        add_action('admin_post_wlbp_delete_service', [$this, 'delete_service']);
    }

    public function assets(string $hook): void {
        if (strpos($hook, 'wlbp') === false) return;
        wp_enqueue_style('wlbp-admin', WLBP_URL . 'assets/css/admin.css', [], WLBP_VERSION);
        wp_enqueue_script('wlbp-admin', WLBP_URL . 'assets/js/admin.js', [], WLBP_VERSION, true);
        wp_localize_script('wlbp-admin', 'WLBP_ADMIN_CHART', ['days' => WLBP_Leads::last_30_days()]);
    }

    public function menu(): void {
        add_menu_page('WhatsApp Lead Booster PRO', 'WhatsApp Booster', 'manage_options', 'wlbp-dashboard', [$this, 'dashboard_page'], 'dashicons-whatsapp', 26);
        add_submenu_page('wlbp-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'wlbp-dashboard', [$this, 'dashboard_page']);
        add_submenu_page('wlbp-dashboard', 'Leads', 'Leads', 'manage_options', 'wlbp-leads', [$this, 'leads_page']);
        add_submenu_page('wlbp-dashboard', 'Serviços', 'Serviços', 'manage_options', 'wlbp-services', [$this, 'services_page']);
        add_submenu_page('wlbp-dashboard', 'Configurações', 'Configurações', 'manage_options', 'wlbp-settings', [$this, 'settings_page']);
        add_submenu_page('wlbp-dashboard', 'Integrações', 'Integrações', 'manage_options', 'wlbp-integrations', [$this, 'integrations_page']);
    }

    public function settings(): void {
        register_setting('wlbp_settings_group', 'wlbp_settings', ['WLBP_Settings', 'sanitize']);
    }

    private function card(string $label, string $value): void {
        echo '<div class="wlbp-card"><span>' . esc_html($label) . '</span><strong>' . esc_html($value) . '</strong></div>';
    }

    public function dashboard_page(): void {
        ?>
        <div class="wrap wlbp-admin">
            <div class="wlbp-hero">
                <div>
                    <span class="wlbp-pill">PRO 1.2.3</span>
                    <h1>WhatsApp Lead Booster PRO</h1>
                    <p>Transforme visitantes do site em clientes qualificados no WhatsApp.</p>
                </div>

                <div class="wlbp-hero-actions">
                    <div class="wlbp-shortcode" id="wlbpShortcode">[whatsapp_lead_booster]</div>
                    <button type="button" class="button wlbp-copy-btn" data-copy-target="wlbpShortcode">Copiar shortcode</button>
                </div>
            </div>

            <div class="wlbp-grid">
                <?php
                $this->card('Total de leads', (string) WLBP_Leads::count_total());
                $this->card('Leads hoje', (string) WLBP_Leads::count_today());
                $this->card('Visualizações', (string) WLBP_Stats::count_event('view'));
                $this->card('Cliques no WhatsApp', (string) WLBP_Stats::count_event('whatsapp_click'));
                $this->card('Conversão', WLBP_Stats::conversion_rate() . '%');
                ?>
            </div>

            <div class="wlbp-dashboard-layout">
                <div class="wlbp-panel wlbp-chart-panel">
                    <div class="wlbp-panel-head">
                        <div>
                            <h2>Leads dos últimos 30 dias</h2>
                            <p>Acompanhe se suas páginas estão gerando mais oportunidades.</p>
                        </div>
                        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=wlbp-leads')); ?>">Ver leads</a>
                    </div>
                    <div class="wlbp-chart-box">
                        <canvas id="wlbpLeadsChart"></canvas>
                    </div>
                </div>

                <div class="wlbp-panel wlbp-help-panel">
                    <h2>Primeiros passos</h2>
                    <ol>
                        <li>Configure seu número em <strong>Configurações</strong>.</li>
                        <li>Cadastre seus serviços em <strong>Serviços</strong>.</li>
                        <li>Cole o shortcode em uma página ou no Elementor.</li>
                        <li>Acompanhe os leads e exporte em CSV.</li>
                    </ol>

                    <div class="wlbp-tip">
                        <strong>Dica de venda:</strong>
                        Use o formulário em páginas de orçamento, contato e serviços para aumentar conversões no WhatsApp.
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function leads_page(): void {
        $filters = [
            'service' => $_GET['service'] ?? '',
            'date_start' => $_GET['date_start'] ?? '',
            'date_end' => $_GET['date_end'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];
        $leads = WLBP_Leads::query($filters, 500);
        $services = WLBP_Services::all();
        ?>
        <div class="wrap wlbp-admin">
            <h1>Leads recebidos</h1><p class="wlbp-page-description">Filtre, exporte e acompanhe os contatos gerados pelo formulário.</p>

            <form method="get" class="wlbp-filters">
                <input type="hidden" name="page" value="wlbp-leads">
                <select name="service">
                    <option value="">Todos os serviços</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?php echo esc_attr($service['name']); ?>" <?php selected($filters['service'], $service['name']); ?>><?php echo esc_html($service['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">Todos os status</option>
                    <option value="novo" <?php selected($filters['status'], 'novo'); ?>>Novo</option>
                    <option value="atendido" <?php selected($filters['status'], 'atendido'); ?>>Atendido</option>
                </select>
                <input type="date" name="date_start" value="<?php echo esc_attr($filters['date_start']); ?>">
                <input type="date" name="date_end" value="<?php echo esc_attr($filters['date_end']); ?>">
                <button class="button button-primary">Filtrar</button>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=wlbp-leads')); ?>">Limpar</a>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin-post.php?action=wlbp_export_csv&' . http_build_query($filters))); ?>">Exportar CSV</a>
            </form>

            <div class="wlbp-table-wrap">
                <table class="widefat fixed striped wlbp-table">
                    <thead>
                        <tr>
                            <th>Data</th><th>Status</th><th>Nome</th><th>Telefone</th><th>E-mail</th><th>Cidade</th><th>Serviço</th><th>Orçamento</th><th>Urgência</th><th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$leads): ?><tr><td colspan="10">Nenhum lead encontrado.</td></tr><?php endif; ?>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($lead['created_at']))); ?></td>
                                <td><span class="wlbp-status"><?php echo esc_html($lead['status']); ?></span></td>
                                <td><?php echo esc_html($lead['name']); ?></td>
                                <td><?php echo esc_html($lead['phone']); ?></td>
                                <td><?php echo esc_html($lead['email']); ?></td>
                                <td><?php echo esc_html($lead['city']); ?></td>
                                <td><?php echo esc_html($lead['service']); ?></td>
                                <td><?php echo esc_html($lead['budget']); ?></td>
                                <td><?php echo esc_html($lead['urgency']); ?></td>
                                <td>
                                    <a class="wlbp-danger" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wlbp_delete_lead&id=' . (int) $lead['id']), 'wlbp_delete_lead')); ?>" onclick="return confirm('Excluir este lead?')">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function services_page(): void {
        $services = WLBP_Services::all();
        ?>
        <div class="wrap wlbp-admin">
            <h1>Serviços</h1><p class="wlbp-page-description">Cadastre os serviços que aparecerão no campo “Serviço de interesse” do formulário.</p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wlbp-panel wlbp-service-form">
                <?php wp_nonce_field('wlbp_add_service'); ?>
                <input type="hidden" name="action" value="wlbp_add_service">
                <input type="text" name="name" placeholder="Nome do serviço" required>
                <input type="text" name="description" placeholder="Descrição curta">
                <button class="button button-primary">Adicionar serviço</button>
            </form>

            <div class="wlbp-table-wrap">
                <table class="widefat fixed striped wlbp-table">
                    <thead><tr><th>Serviço</th><th>Descrição</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><strong><?php echo esc_html($service['name']); ?></strong></td>
                                <td><?php echo esc_html($service['description']); ?></td>
                                <td><?php echo $service['active'] ? '<span class="wlbp-active">Ativo</span>' : '<span class="wlbp-inactive">Inativo</span>'; ?></td>
                                <td>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wlbp_toggle_service&id=' . (int) $service['id']), 'wlbp_toggle_service')); ?>">Ativar/Inativar</a>
                                    |
                                    <a class="wlbp-danger" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wlbp_delete_service&id=' . (int) $service['id']), 'wlbp_delete_service')); ?>" onclick="return confirm('Excluir serviço?')">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function settings_page(): void {
        $s = WLBP_Settings::get();
        ?>
        <div class="wrap wlbp-admin">
            <h1>Configurações</h1><p class="wlbp-page-description">Personalize o formulário, número de WhatsApp, faixas de orçamento e mensagens exibidas ao visitante.</p>
            <form method="post" action="options.php" class="wlbp-panel">
                <?php settings_fields('wlbp_settings_group'); ?>
                <h2>WhatsApp e formulário</h2>
                <table class="form-table">
                    <tr><th>Número do WhatsApp</th><td><input type="text" name="wlbp_settings[whatsapp_number]" value="<?php echo esc_attr($s['whatsapp_number']); ?>" class="regular-text" placeholder="5512999999999"><p class="description">Formato: DDI + DDD + número.</p></td></tr>
                    <tr><th>Nome do formulário</th><td><input type="text" name="wlbp_settings[form_name]" value="<?php echo esc_attr($s['form_name']); ?>" class="regular-text"></td></tr>
                    <tr><th>Título</th><td><input type="text" name="wlbp_settings[form_title]" value="<?php echo esc_attr($s['form_title']); ?>" class="regular-text"></td></tr>
                    <tr><th>Subtítulo</th><td><textarea name="wlbp_settings[form_subtitle]" rows="3" class="large-text"><?php echo esc_textarea($s['form_subtitle']); ?></textarea></td></tr>
                    <tr><th>Texto do botão</th><td><input type="text" name="wlbp_settings[button_text]" value="<?php echo esc_attr($s['button_text']); ?>" class="regular-text"></td></tr>
                    <tr><th>Faixas de orçamento</th><td><textarea name="wlbp_settings[budgets]" rows="5" class="large-text"><?php echo esc_textarea($s['budgets']); ?></textarea><p class="description">Um item por linha.</p></td></tr>
                    <tr><th>Urgências</th><td><textarea name="wlbp_settings[urgencies]" rows="5" class="large-text"><?php echo esc_textarea($s['urgencies']); ?></textarea><p class="description">Um item por linha.</p></td></tr>
                    <tr><th>Mensagem de sucesso</th><td><input type="text" name="wlbp_settings[success_message]" value="<?php echo esc_attr($s['success_message']); ?>" class="regular-text"></td></tr>
                </table>
                <?php submit_button('Salvar configurações'); ?>
            </form>
        </div>
        <?php
    }

    public function integrations_page(): void {
        $s = WLBP_Settings::get();
        ?>
        <div class="wrap wlbp-admin">
            <h1>Integrações</h1><p class="wlbp-page-description">Ative apenas as integrações que você já usa no site. Os eventos são disparados quando um lead é enviado.</p>
            <form method="post" action="options.php" class="wlbp-panel">
                <?php settings_fields('wlbp_settings_group'); ?>

                <div class="wlbp-integration-card">
                    <h2>Notificação por e-mail</h2>
                    <label><input type="checkbox" name="wlbp_settings[enable_email_notification]" value="1" <?php checked($s['enable_email_notification'], '1'); ?>> Enviar e-mail quando chegar lead</label>
                    <input type="email" name="wlbp_settings[notification_email]" value="<?php echo esc_attr($s['notification_email']); ?>" class="regular-text" placeholder="email@empresa.com">
                </div>

                <div class="wlbp-integration-card">
                    <h2>Meta Pixel</h2>
                    <label><input type="checkbox" name="wlbp_settings[enable_meta_pixel]" value="1" <?php checked($s['enable_meta_pixel'], '1'); ?>> Disparar evento <code>Lead</code></label>
                    <input type="text" name="wlbp_settings[meta_pixel_id]" value="<?php echo esc_attr($s['meta_pixel_id']); ?>" class="regular-text" placeholder="ID do Pixel">
                </div>

                <div class="wlbp-integration-card">
                    <h2>Google Ads / GA4</h2>
                    <label><input type="checkbox" name="wlbp_settings[enable_google_ads]" value="1" <?php checked($s['enable_google_ads'], '1'); ?>> Disparar evento <code>generate_lead</code></label>
                    <input type="text" name="wlbp_settings[google_ads_id]" value="<?php echo esc_attr($s['google_ads_id']); ?>" class="regular-text" placeholder="ID da conversão">
                </div>

                <div class="wlbp-integration-card">
                    <h2>Google Sheets</h2>
                    <label><input type="checkbox" name="wlbp_settings[enable_google_sheets]" value="1" <?php checked($s['enable_google_sheets'], '1'); ?>> Enviar leads para webhook/planilha</label>
                    <input type="url" name="wlbp_settings[google_sheets_webhook]" value="<?php echo esc_attr($s['google_sheets_webhook']); ?>" class="large-text" placeholder="URL do webhook">
                </div>

                <?php submit_button('Salvar integrações'); ?>
            </form>
        </div>
        <?php
    }

    public function license_page(): void {
        ?>
        <div class="wrap wlbp-admin">
            <h1>Licença</h1>
            <div class="wlbp-panel">
                <h2>WhatsApp Lead Booster PRO</h2>
                <p>Área preparada para validação de licença em uma versão comercial futura.</p>
                <input type="text" class="regular-text" placeholder="XXXX-XXXX-XXXX-XXXX" disabled>
                <button class="button button-primary" disabled>Ativar licença</button>
            </div>
        </div>
        <?php
    }

    public function delete_lead(): void {
        if (!current_user_can('manage_options') || !check_admin_referer('wlbp_delete_lead')) wp_die('Sem permissão.');
        WLBP_Leads::delete((int) ($_GET['id'] ?? 0));
        wp_safe_redirect(admin_url('admin.php?page=wlbp-leads'));
        exit;
    }

    public function add_service(): void {
        if (!current_user_can('manage_options') || !check_admin_referer('wlbp_add_service')) wp_die('Sem permissão.');
        WLBP_Services::create($_POST['name'] ?? '', $_POST['description'] ?? '');
        wp_safe_redirect(admin_url('admin.php?page=wlbp-services'));
        exit;
    }

    public function toggle_service(): void {
        if (!current_user_can('manage_options') || !check_admin_referer('wlbp_toggle_service')) wp_die('Sem permissão.');
        WLBP_Services::toggle((int) ($_GET['id'] ?? 0));
        wp_safe_redirect(admin_url('admin.php?page=wlbp-services'));
        exit;
    }

    public function delete_service(): void {
        if (!current_user_can('manage_options') || !check_admin_referer('wlbp_delete_service')) wp_die('Sem permissão.');
        WLBP_Services::delete((int) ($_GET['id'] ?? 0));
        wp_safe_redirect(admin_url('admin.php?page=wlbp-services'));
        exit;
    }
}
