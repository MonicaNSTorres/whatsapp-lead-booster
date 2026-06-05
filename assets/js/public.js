document.addEventListener('DOMContentLoaded', function () {
    const cards = document.querySelectorAll('.wlb-pro-card');

    function track(eventType, sourceUrl) {
        const payload = new FormData();
        payload.append('action', 'wlb_pro_track_event');
        payload.append('nonce', WLB_PRO.nonce);
        payload.append('event_type', eventType);
        payload.append('source_url', sourceUrl || window.location.href);
        return fetch(WLB_PRO.ajaxUrl, { method: 'POST', body: payload, credentials: 'same-origin' }).catch(function () {});
    }

    cards.forEach(function (card) {
        const form = card.querySelector('.wlb-pro-form');
        const button = form.querySelector('.wlb-pro-button');
        const alert = form.querySelector('.wlb-pro-alert');
        const sourceInput = form.querySelector('input[name="source_url"]');
        const originalButtonText = button.textContent;
        sourceInput.value = window.location.href;
        track('view', window.location.href);

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!card.dataset.whatsapp) {
                alert.hidden = false;
                alert.textContent = 'WhatsApp ainda não foi configurado.';
                return;
            }
            button.disabled = true;
            button.textContent = 'Preparando atendimento...';
            alert.hidden = true;
            const payload = new FormData(form);
            payload.append('action', 'wlb_pro_save_lead');
            payload.append('nonce', WLB_PRO.nonce);
            payload.append('form_name', card.dataset.formName || 'Principal');
            try {
                const response = await fetch(WLB_PRO.ajaxUrl, { method: 'POST', body: payload, credentials: 'same-origin' });
                const result = await response.json();
                if (!result.success) throw new Error(result.data?.message || 'Erro ao salvar lead.');
                await track('whatsapp_click', window.location.href);
                if (card.dataset.metaPixel === '1' && typeof fbq === 'function') fbq('track', 'Lead');
                if (card.dataset.googleAds === '1' && typeof gtag === 'function') gtag('event', 'generate_lead');
                alert.hidden = false;
                alert.textContent = result.data.message || card.dataset.successMessage || 'Lead recebido.';
                window.open(result.data.whatsapp_url, '_blank');
                form.reset();
                sourceInput.value = window.location.href;
            } catch (error) {
                alert.hidden = false;
                alert.textContent = error.message || 'Não foi possível enviar agora.';
            } finally {
                button.disabled = false;
                button.textContent = originalButtonText;
            }
        });
    });
});
