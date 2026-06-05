document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.wlbp-copy-btn').forEach(function (button) {
        button.addEventListener('click', async function () {
            const targetId = button.getAttribute('data-copy-target');
            const target = document.getElementById(targetId);
            if (!target) return;

            try {
                await navigator.clipboard.writeText(target.textContent.trim());
                const original = button.textContent;
                button.textContent = 'Copiado!';
                setTimeout(function () {
                    button.textContent = original;
                }, 1400);
            } catch (error) {
                alert('Não foi possível copiar automaticamente.');
            }
        });
    });

    const canvas = document.getElementById('wlbpLeadsChart');
    if (!canvas || !window.WLBP_ADMIN_CHART) return;

    const data = WLBP_ADMIN_CHART.days || [];
    const labels = data.map(item => item.label);
    const values = data.map(item => Number(item.total || 0));
    const max = Math.max(1, ...values);
    const ctx = canvas.getContext('2d');

    function drawChart() {
        const parent = canvas.parentElement;
        const cssWidth = parent.clientWidth || 800;
        const cssHeight = parent.clientHeight || 310;
        const ratio = window.devicePixelRatio || 1;

        canvas.width = cssWidth * ratio;
        canvas.height = cssHeight * ratio;
        canvas.style.width = cssWidth + 'px';
        canvas.style.height = cssHeight + 'px';

        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.clearRect(0, 0, cssWidth, cssHeight);

        const paddingLeft = 46;
        const paddingRight = 18;
        const paddingTop = 22;
        const paddingBottom = 52;
        const chartW = cssWidth - paddingLeft - paddingRight;
        const chartH = cssHeight - paddingTop - paddingBottom;

        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 1;

        for (let i = 0; i <= 4; i++) {
            const y = paddingTop + (chartH / 4) * i;
            ctx.beginPath();
            ctx.moveTo(paddingLeft, y);
            ctx.lineTo(cssWidth - paddingRight, y);
            ctx.stroke();

            const value = Math.round(max - (max / 4) * i);
            ctx.fillStyle = '#94a3b8';
            ctx.font = '11px Arial';
            ctx.fillText(String(value), 10, y + 4);
        }

        if (values.length === 0) return;

        const points = values.map((value, index) => {
            const x = paddingLeft + index * (chartW / Math.max(1, values.length - 1));
            const y = paddingTop + chartH - (value / max) * chartH;
            return {x, y, value};
        });

        const gradient = ctx.createLinearGradient(0, paddingTop, 0, paddingTop + chartH);
        gradient.addColorStop(0, 'rgba(22, 163, 74, 0.22)');
        gradient.addColorStop(1, 'rgba(22, 163, 74, 0.02)');

        ctx.beginPath();
        points.forEach((point, index) => {
            if (index === 0) ctx.moveTo(point.x, point.y);
            else ctx.lineTo(point.x, point.y);
        });
        ctx.lineTo(points[points.length - 1].x, paddingTop + chartH);
        ctx.lineTo(points[0].x, paddingTop + chartH);
        ctx.closePath();
        ctx.fillStyle = gradient;
        ctx.fill();

        ctx.beginPath();
        points.forEach((point, index) => {
            if (index === 0) ctx.moveTo(point.x, point.y);
            else ctx.lineTo(point.x, point.y);
        });
        ctx.strokeStyle = '#16a34a';
        ctx.lineWidth = 3;
        ctx.stroke();

        points.forEach(point => {
            ctx.beginPath();
            ctx.arc(point.x, point.y, 4, 0, Math.PI * 2);
            ctx.fillStyle = '#16a34a';
            ctx.fill();
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;
            ctx.stroke();
        });

        ctx.fillStyle = '#64748b';
        ctx.font = '11px Arial';

        labels.forEach((label, index) => {
            const shouldShow = index % 5 === 0 || index === labels.length - 1;
            if (!shouldShow) return;

            const x = paddingLeft + index * (chartW / Math.max(1, labels.length - 1));
            ctx.save();
            ctx.translate(x, cssHeight - 22);
            ctx.rotate(-Math.PI / 7);
            ctx.fillText(label, -12, 0);
            ctx.restore();
        });
    }

    drawChart();
    window.addEventListener('resize', drawChart);
});
