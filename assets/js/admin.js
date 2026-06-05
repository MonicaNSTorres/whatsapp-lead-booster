document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('wlbpLeadsChart');
    if (!canvas || !window.WLBP_ADMIN_CHART) return;

    const ctx = canvas.getContext('2d');
    const data = WLBP_ADMIN_CHART.days || [];
    const labels = data.map(item => item.label);
    const values = data.map(item => Number(item.total || 0));
    const max = Math.max(1, ...values);

    const cssWidth = canvas.offsetWidth || 900;
    const cssHeight = 220;
    canvas.width = cssWidth * 2;
    canvas.height = cssHeight * 2;
    ctx.scale(2, 2);

    const padding = 48;
    ctx.clearRect(0, 0, cssWidth, cssHeight);
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 1;

    for (let i = 0; i <= 4; i++) {
        const y = padding / 2 + ((cssHeight - padding) / 4) * i;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(cssWidth - 20, y);
        ctx.stroke();
    }

    ctx.strokeStyle = '#16a34a';
    ctx.lineWidth = 3;
    ctx.beginPath();

    values.forEach((value, index) => {
        const x = padding + (index * ((cssWidth - padding - 24) / Math.max(1, values.length - 1)));
        const y = cssHeight - padding - ((value / max) * (cssHeight - padding - 24));
        if (index === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    });

    ctx.stroke();

    ctx.fillStyle = '#16a34a';
    values.forEach((value, index) => {
        const x = padding + (index * ((cssWidth - padding - 24) / Math.max(1, values.length - 1)));
        const y = cssHeight - padding - ((value / max) * (cssHeight - padding - 24));
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, Math.PI * 2);
        ctx.fill();
    });

    ctx.fillStyle = '#64748b';
    ctx.font = '11px Arial';
    labels.forEach((label, index) => {
        if (index % 4 !== 0 && index !== labels.length - 1) return;
        const x = padding + (index * ((cssWidth - padding - 24) / Math.max(1, labels.length - 1)));
        ctx.fillText(label, x - 12, cssHeight - 14);
    });
});
