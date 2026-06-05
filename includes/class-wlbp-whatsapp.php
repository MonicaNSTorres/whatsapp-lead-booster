<?php
if (!defined('ABSPATH')) exit;

class WLBP_WhatsApp {
    public static function build_message(array $lead): string {
        return implode("\n", [
            'Olá! Recebi um novo lead pelo site.',
            '',
            'Nome: ' . ($lead['name'] ?? ''),
            'Telefone: ' . ($lead['phone'] ?? ''),
            'E-mail: ' . ($lead['email'] ?? ''),
            'Cidade: ' . ($lead['city'] ?? ''),
            'Serviço: ' . ($lead['service'] ?? ''),
            'Orçamento: ' . ($lead['budget'] ?? ''),
            'Urgência: ' . ($lead['urgency'] ?? ''),
            '',
            'Mensagem: ' . ($lead['message'] ?? ''),
            '',
            'Origem: ' . ($lead['source_url'] ?? ''),
        ]);
    }
}
