<?php
/**
 * Plugin Name: WhatsApp Lead Booster PRO
 * Description: Transforme visitantes do site em leads qualificados no WhatsApp, com dashboard, métricas, exportação CSV e eventos de conversão.
 * Version: 1.1.0
 * Author: Mônica Torres
 * Text Domain: whatsapp-lead-booster-pro
 */

if (!defined('ABSPATH')) exit;

define('WLB_PRO_VERSION', '1.1.0');
define('WLB_PRO_PATH', plugin_dir_path(__FILE__));
define('WLB_PRO_URL', plugin_dir_url(__FILE__));

require_once WLB_PRO_PATH . 'includes/class-wlb-activator.php';
require_once WLB_PRO_PATH . 'includes/class-wlb-settings.php';
require_once WLB_PRO_PATH . 'includes/class-wlb-leads.php';
require_once WLB_PRO_PATH . 'includes/class-wlb-stats.php';
require_once WLB_PRO_PATH . 'includes/class-wlb-whatsapp.php';
require_once WLB_PRO_PATH . 'admin/class-wlb-admin.php';
require_once WLB_PRO_PATH . 'public/class-wlb-public.php';

register_activation_hook(__FILE__, ['WLB_Activator', 'activate']);

final class WhatsApp_Lead_Booster_Pro {
    public function __construct() {
        new WLB_Admin();
        new WLB_Public();
    }
}

new WhatsApp_Lead_Booster_Pro();
