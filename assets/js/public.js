document.addEventListener('DOMContentLoaded', function () {
    const cards = document.querySelectorAll('.wlbp-public-card');

    function track(eventType, sourceUrl) {
        const payload = new FormData();
        payload.append('action', 'wlbp_track_event');
        payload.append('nonce', WLBP_PUBLIC.nonce);
        payload.append('event_type', eventType);
        payload.append('source_url', sourceUrl || window.location.href);

        return fetch(WLBP_PUBLIC.ajaxUrl, {
            method: 'POST',
            body: payload,
            credentials: 'same-origin'
        }).catch(function () {});
    }

    cards.forEach(function (card) {
        const form = card.querySelector('.wlbp-public-form');
        const button = form.querySelector('.wlbp-public-button');
        const alert = form.querySelector('.wlbp-public-alert');
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
            payload.append('action', 'wlbp_save_lead');
            payload.append('nonce', WLBP_PUBLIC.nonce);
            payload.append('form_name', card.dataset.formName || 'Principal');

            try {
                const response = await fetch(WLBP_PUBLIC.ajaxUrl, {
                    method: 'POST',
                    body: payload,
                    credentials: 'same-origin'
                });

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
