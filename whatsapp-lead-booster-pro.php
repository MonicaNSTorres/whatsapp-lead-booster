<?php
/**
 * Plugin Name: WhatsApp Lead Booster PRO
 * Description: Capture leads qualificados pelo WhatsApp com dashboard, serviços, filtros, integrações e exportação CSV.
 * Version: 1.2.3
 * Author: Mônica Torres
 * Text Domain: whatsapp-lead-booster-pro
 */

if (!defined('ABSPATH')) exit;

define('WLBP_VERSION', '1.2.3');
define('WLBP_PATH', plugin_dir_path(__FILE__));
define('WLBP_URL', plugin_dir_url(__FILE__));

require_once WLBP_PATH . 'includes/class-wlbp-activator.php';
require_once WLBP_PATH . 'includes/class-wlbp-settings.php';
require_once WLBP_PATH . 'includes/class-wlbp-services.php';
require_once WLBP_PATH . 'includes/class-wlbp-leads.php';
require_once WLBP_PATH . 'includes/class-wlbp-stats.php';
require_once WLBP_PATH . 'includes/class-wlbp-whatsapp.php';
require_once WLBP_PATH . 'admin/class-wlbp-admin.php';
require_once WLBP_PATH . 'public/class-wlbp-public.php';

register_activation_hook(__FILE__, ['WLBP_Activator', 'activate']);

final class WhatsApp_Lead_Booster_Pro_Safe {
    public function __construct() {
        new WLBP_Admin();
        new WLBP_Public();
    }
}

new WhatsApp_Lead_Booster_Pro_Safe();
