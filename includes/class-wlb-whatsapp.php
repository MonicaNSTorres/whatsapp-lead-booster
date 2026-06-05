<?php
if (!defined('ABSPATH')) exit;

class WLB_WhatsApp {
    public static function build_message(array $lead): string {
        return "Olá! Recebi um lead pelo site.\n\n" .
            "Nome: " . ($lead['name'] ?? '') . "\n" .
            "Telefone: " . ($lead['phone'] ?? '') . "\n" .
            "E-mail: " . ($lead['email'] ?? '') . "\n" .
            "Cidade: " . ($lead['city'] ?? '') . "\n" .
            "Serviço: " . ($lead['service'] ?? '') . "\n" .
            "Orçamento: " . ($lead['budget'] ?? '') . "\n" .
            "Urgência: " . ($lead['urgency'] ?? '') . "\n\n" .
            "Mensagem: " . ($lead['message'] ?? '') . "\n\n" .
            "Origem: " . ($lead['source_url'] ?? '');
    }
}
