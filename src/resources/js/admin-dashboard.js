import '../css/admin-dashboard.css';

const root = document.getElementById('business-dashboard');
if (root) initializeDashboard(root);

function initializeDashboard(root) {
    const byId = id => root.querySelector(`#${id}`);
    const initial = JSON.parse(byId('dashboard-initial').textContent);
    let report = initial.report;
    let inventory = initial.inventory;
    let busy = false;
    let customBack = null;
    let requestController;
    let inventoryController;
    let dialogController;
    let stockPage = 1;
    let inventoryFilters = byId('stock-filters') ? Object.fromEntries(new FormData(byId('stock-filters'))) : {};
    const animations = new Map();
    const months = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogo', 'Sep', 'Okt', 'Nov', 'Dis'];
    const full = ['Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'];
    const weekdays = ['Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu'];
    const weekday = row => {
        const date = row.start ? new Date(`${row.start}T00:00:00Z`) : new Date(Date.UTC(Number(report.filters.year), Number(report.filters.month) - 1, Number(row.period)));
        return weekdays[date.getUTCDay()];
    };
    const colors = ['#3b92f7', '#a192de', '#28b997', '#ddb36b', '#79a8bd'];
    const money = value => value === null ? 'Belum diketahui' : `RM ${Number(value).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
    const daily = () => report.granularity === 'day' || (!report.filters.start_date && !!report.filters.month);
    const period = () => report.filters.start_date ? `${report.filters.start_date} – ${report.filters.end_date}` : report.filters.month ? `${full[report.filters.month - 1]} ${report.filters.year}` : `Tahun ${report.filters.year}`;
    const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    const percent = (value, base) => base > 0 ? value / base * 100 : 0;
    const meter = (value, color = colors[0]) => `<div class="meter"><span style="--width:${Math.min(100, Math.max(0, value))}%;--color:${color}"></span></div>`;
    const empty = text => `<p class="empty">${escape(text)}</p>`;
    const query = values => new URLSearchParams(Object.entries(values).filter(([, value]) => value !== null && value !== '' && value !== undefined)).toString();
    const request = async (url, values, signal) => {
        const response = await fetch(`${url}?${query(values)}`, { headers: { Accept: 'application/json' }, signal, credentials: 'same-origin' });
        if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
            throw new Error(response.status === 403 ? 'Akses kepada laporan ini tidak dibenarkan.' : response.status === 422 ? 'Semak pilihan tarikh dan penapis.' : 'Laporan tidak dapat dimuatkan. Cuba semula atau log masuk semula.');
        }
        return response.json();
    };
    function count(id, value, currency = true) {
        const node = byId(id);
        if (!node) return;
        if (animations.has(id)) cancelAnimationFrame(animations.get(id));
        let start;
        const frame = time => {
            start ??= time;
            const progress = reduced() ? 1 : Math.min(1, (time - start) / 850);
            const amount = value * (1 - (1 - progress) ** 3);
            node.textContent = currency ? money(amount) : Math.round(amount).toLocaleString('en-MY');
            if (progress < 1) animations.set(id, requestAnimationFrame(frame));
        };
        animations.set(id, requestAnimationFrame(frame));
    }
    function openModal(title, html) {
        byId('modal-title').textContent = title;
        byId('modal-body').innerHTML = html;
        if (!byId('dashboard-modal').open) byId('dashboard-modal').showModal();
    }
    byId('close-modal').onclick = () => byId('dashboard-modal').close();
    byId('dashboard-modal').addEventListener('close', () => dialogController?.abort());
    byId('dashboard-modal').addEventListener('click', event => {
        if (event.target !== byId('dashboard-modal')) return;
        const rect = event.target.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) event.target.close();
    });
    async function load(changes = {}) {
        requestController?.abort();
        requestController = new AbortController();
        const controller = requestController;
        busy = true;
        root.setAttribute('aria-busy', 'true');
        byId('export-report').disabled = true;
        byId('dashboard-status').textContent = 'Memuatkan prestasi…';
        try {
            const next = await request(root.dataset.reportUrl, { ...report.filters, ...changes, page: 1 }, controller.signal);
            if (controller !== requestController) return;
            report = next;
            render();
            byId('dashboard-status').textContent = '';
        } catch (error) {
            if (error.name === 'AbortError') return;
            restoreFilters();
            byId('dashboard-status').textContent = `${error.message} Paparan sebelumnya dikekalkan.`;
        } finally {
            if (controller === requestController) {
                busy = false;
                root.removeAttribute('aria-busy');
                byId('export-report').disabled = false;
            }
        }
    }
    function restoreFilters() {
        byId('year-filter').value = report.filters.year;
        byId('start-date-filter').value = report.period.start;
        byId('end-date-filter').value = report.period.end.substring(0, 10);
        byId('channel-filter').value = report.filters.channel;
        if (byId('site-filter')) byId('site-filter').value = report.filters.site || '';
        byId('comparison-filter').value = report.filters.comparison;
    }
    byId('performance').onsubmit = event => {
        event.preventDefault();
        const start = byId('start-date-filter'), end = byId('end-date-filter');
        end.setCustomValidity(end.value < start.value ? 'Tarikh hingga mesti sama atau selepas tarikh mula.' : '');
        if (!byId('performance').reportValidity()) return;
        customBack = null;
        load({start_date: start.value, end_date: end.value, month: null, day: null});
    };
    byId('performance').addEventListener('change', event => {
        if (['start-date-filter', 'end-date-filter'].includes(event.target.id)) {
            byId('end-date-filter').setCustomValidity('');
            return;
        }
        const values = Object.fromEntries(new FormData(byId('performance')));
        if (event.target.id === 'year-filter') { values.month = null; values.start_date = null; values.end_date = null; customBack = null; }
        load(values);
    });
    byId('back-monthly').onclick = () => { if (customBack) { const previous = customBack; customBack = null; load(previous); } else load({ month: null }); };
    function adjacentMonth(offset) {
        const date = new Date(Number(report.filters.year), Number(report.filters.month) - 1 + offset, 1);
        load({ year: date.getFullYear(), month: date.getMonth() + 1 });
    }
    byId('previous-month').onclick = () => adjacentMonth(-1);
    byId('next-month').onclick = () => adjacentMonth(1);
    byId('export-report').onclick = () => { window.location.href = `${root.dataset.exportUrl}?${query(report.filters)}`; };
    function chart() {
        const data = report.series;
        const known = data.filter(row => !row.future);
        const values = known.flatMap(row => [row.sales, row.cost, row.sales - row.cost]);
        const rawMax = Math.max(1, ...values), rawMin = Math.min(0, ...values);
        const stepSize = 10 ** Math.max(0, Math.floor(Math.log10(Math.max(rawMax, Math.abs(rawMin)))) - 1);
        const max = Math.ceil(rawMax / stepSize) * stepSize, min = Math.floor(rawMin / stepSize) * stepSize;
        const left = 78, width = 1027, top = 25, bottom = 310, step = width / data.length, bar = Math.min(23, step * .24);
        const y = value => bottom - (value - min) / (max - min) * (bottom - top);
        const zero = y(0);
        let html = '<defs><linearGradient id="dashboard-blue" x2="0" y2="1"><stop stop-color="#5aafff"/><stop offset="1" stop-color="#2663cb"/></linearGradient><linearGradient id="dashboard-gold" x2="0" y2="1"><stop stop-color="#e9c68c"/><stop offset="1" stop-color="#b58445"/></linearGradient><linearGradient id="dashboard-area" x2="0" y2="1"><stop stop-color="#3addb0" stop-opacity=".12"/><stop offset="1" stop-color="#3addb0" stop-opacity="0"/></linearGradient></defs>';
        for (let i = 0; i <= 4; i++) {
            const value = min + (max - min) * i / 4;
            const label = Math.abs(value) >= 1000000 ? `${(value / 1000000).toFixed(1)}m` : Math.abs(value) >= 1000 ? `${(value / 1000).toFixed(1)}k` : value.toFixed(0);
            html += `<line x1="${left}" x2="1105" y1="${y(value)}" y2="${y(value)}" stroke="#a0bbd4" stroke-opacity=".12" stroke-dasharray="3 6"/><text x="62" y="${y(value) + 4}" fill="#8299b0" text-anchor="end" font-size="11">${escape(label)}</text>`;
        }
        html += `<line x1="${left}" x2="1105" y1="${zero}" y2="${zero}" stroke="#71899e" stroke-opacity=".4"/>`;
        const points = known.map(row => [left + step * (row.period - .5), y(row.sales - row.cost)]);
        const path = points.map((point, i) => `${i ? 'L' : 'M'}${point.join(',')}`).join(' ');
        if (points.length) html += `<path d="${path} L${points.at(-1)[0]},${zero} L${points[0][0]},${zero} Z" fill="url(#dashboard-area)"/>`;
        data.forEach((row, i) => {
            const x = left + step * (i + .5), label = row.label || (report.filters.month ? row.period : months[i]);
            const tick = daily() || i % Math.max(1, Math.ceil(data.length / 24)) === 0 ? label : '';
            const dayLabel = daily() ? `<tspan x="${x}" dy="14" font-size="8">${weekday(row)}</tspan>` : '';
            const accessibleLabel = daily() ? `${weekday(row)}, ${label}` : label;
            if (row.future) {
                html += `<text x="${x}" y="340" text-anchor="middle" fill="#405872" font-size="11">${escape(tick)}${dayLabel}</text>`;
                return;
            }
            html += `<g class="period" role="button" tabindex="0" data-period="${row.period}" aria-label="${escape(`${accessibleLabel}, jualan ${money(row.sales)}, POS ${money(row.pos_sales || 0)}, Order Ejen ${money(row.order_sales || 0)}, Pelanggan ${money(row.customer_sales || 0)}, kos ${money(row.cost)}. Klik untuk butiran.`)}"><rect class="highlight" x="${left + step * i + 2}" y="15" width="${step - 4}" height="331" rx="8" fill="#459fff" fill-opacity=".09"/>`;
            let stackedSales = 0;
            [['pos_sales', '#429aff'], ['order_sales', '#a192de'], ['customer_sales', '#79a8bd']].forEach(([key, color]) => {
                const value = Number(row[key] || 0);
                const base = stackedSales;
                stackedSales += value;
                html += `<rect class="bar" style="--delay:${i * 22}ms" x="${x - bar - 2}" y="${y(stackedSales)}" width="${bar}" height="${Math.max(0, y(base) - y(stackedSales))}" fill="${color}"/>`;
            });
            [['cost', x + 2, 'gold']].forEach(([key, bx, color]) => {
                html += `<rect class="bar" style="--delay:${i * 22}ms" x="${bx}" y="${Math.min(y(row[key]), zero)}" width="${bar}" height="${Math.abs(zero - y(row[key]))}" rx="3" fill="url(#dashboard-${color})"/>`;
            });
            html += `<rect x="${left + step * i}" y="15" width="${step}" height="331" fill="transparent"/><text class="tick" x="${x}" y="340" text-anchor="middle" fill="#9bb0c7" font-size="11">${escape(tick)}${dayLabel}</text></g>`;
        });
        html += `<g pointer-events="none"><path id="dashboard-profit-line" class="profitpath" d="${path}" fill="none" stroke="#53e2bb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>${points.map((p, i) => `<circle class="profitpoint" style="--delay:${400 + i * 35}ms" cx="${p[0]}" cy="${p[1]}" r="${report.filters.month ? 2.5 : 4}" fill="${known[i].sales - known[i].cost < 0 ? '#ee8690' : '#14382e'}" stroke="${known[i].sales - known[i].cost < 0 ? '#ee8690' : '#6ee5c4'}" stroke-width="2"/>`).join('')}</g>`;
        byId('chart').innerHTML = html;
        const line = byId('dashboard-profit-line');
        line.style.setProperty('--length', Math.max(1, line.getTotalLength()));
        byId('tooltip').classList.remove('visible');
        byId('chart').querySelectorAll('[data-period]').forEach(node => {
            const row = data[Number(node.dataset.period) - 1];
            const dateTitle = row.start ? `${row.start} – ${row.end}` : report.filters.month ? `${row.period} ${period()}` : `${full[row.period - 1]} ${report.filters.year}`;
            const title = daily() ? `${weekday(row)}, ${dateTitle}` : dateTitle;
            const show = () => {
                const tip = byId('tooltip');
                tip.innerHTML = `<strong>${escape(title)}</strong>${[['Jumlah jualan', row.sales], ['POS', row.pos_sales || 0], ['Order Ejen', row.order_sales || 0], ['Pelanggan', row.customer_sales || 0], ['Kos', row.cost], ['Anggaran untung', row.sales - row.cost]].map(([label, value]) => `<div class="tiprow"><span>${label}</span><b>${money(value)}</b></div>`).join('')}<small>Klik untuk ${daily() ? 'transaksi' : 'harian'} →</small>`;
                const rect = node.getBoundingClientRect(), wrap = tip.parentElement.getBoundingClientRect();
                const mobile = innerWidth <= 760;
                tip.style.left = `${mobile ? Math.max(8, Math.min(innerWidth - 220, rect.left)) : Math.max(0, Math.min(wrap.width - 215, rect.left - wrap.left - 55))}px`;
                tip.style.top = mobile ? `${Math.max(12, rect.top - 160)}px` : '8px';
                tip.classList.add('visible');
            };
            const hide = () => byId('tooltip').classList.remove('visible');
            const choose = () => {
                if (busy) return;
                hide();
                if (report.filters.start_date) {
                    if (daily()) showTransactions(null, 1, {start_date: row.start, end_date: row.end});
                    else { customBack = {...report.filters}; load({start_date: row.start, end_date: row.end}); }
                } else report.filters.month ? showTransactions(row.period, 1) : load({month: row.period});
            };
            node.addEventListener('pointerenter', show); node.addEventListener('focus', show);
            node.addEventListener('pointerleave', hide); node.addEventListener('blur', hide);
            node.addEventListener('click', choose);
            node.addEventListener('keydown', event => { if (['Enter', ' '].includes(event.key)) { event.preventDefault(); choose(); } if (event.key === 'Escape') hide(); });
        });
    }
    function costMarkup() {
        return [['Modal produk', 'capital'], ['Komisen pelanggan', 'commission'], ['Gaji direkodkan', 'salary'], ['Bonus weekly closing', 'bonus']].map(([label, key], i) => `<div class="cost-row"><span>${label}</span><strong>${money(report.summary[key])}</strong></div>${meter(percent(report.summary[key], report.summary.cost), colors[i])}`).join('');
    }
    const donutColors = ['#348afa', '#14a181', '#d99c35', '#9867bc', '#d96676', '#71808d'];
    const channelColor = key => ({pos: donutColors[0], orders: donutColors[1], customer: donutColors[2]})[key];
    function donutMarkup(rows, label) {
        const total = rows.reduce((sum, row) => sum + Math.round(Number(row.value) * 100), 0) / 100;
        const invalid = rows.some(row => !Number.isFinite(row.value) || row.value < 0);
        let offset = 0;
        const segments = !invalid && total > 0 ? rows.map((row, index) => ({...row, index})).filter(row => row.value > 0).map(row => {
            const share = percent(row.value, total);
            const description = `${row.name}: ${money(row.value)} (${share.toFixed(1)}%)`;
            const segment = `<circle data-donut-index="${row.index}" cx="110" cy="110" r="88" pathLength="100" fill="none" stroke="${row.color}" stroke-width="24" stroke-dasharray="${share} ${100 - share}" stroke-dashoffset="${-offset}" transform="rotate(-90 110 110)" tabindex="0" role="button" aria-pressed="false" aria-label="${escape(description)}"><title>${escape(description)}</title></circle>`;
            offset += share;
            return segment;
        }).join('') : '';
        return `<div class="donut-stage"><div class="breakdown-donut"><svg viewBox="0 0 220 220" aria-label="${escape(label)}"><circle cx="110" cy="110" r="88" fill="none" stroke="#e9eef3" stroke-width="24"/>${segments}</svg><div class="donut-center"><span>${escape(label)}</span><strong>${money(total)}</strong>${!segments ? `<small>${invalid ? 'Pecahan mengandungi nilai negatif' : 'Tiada data'}</small>` : ''}</div></div></div>`;
    }
    function breakdownRows(rows) {
        const total = rows.reduce((sum, row) => sum + Math.round(Number(row.value) * 100), 0) / 100;
        const valid = total > 0 && rows.every(row => row.value >= 0);
        return `<div class="donut-breakdown">${rows.map((row, index) => `<div class="breakdown-row" data-donut-row="${index}"><span class="breakdown-name"><i style="background:${row.color}" aria-hidden="true"></i>${escape(row.name)}</span><strong class="${row.value < 0 ? 'negative' : ''}">${money(row.value)}</strong><span class="breakdown-percent">${valid ? percent(row.value, total).toFixed(1) + '%' : '—'}</span></div>`).join('')}</div>`;
    }
    function bindDonutSelections() {
        for (const id of ['cost-breakdown', 'site-ranking', 'product-ranking', 'transaction-preview']) {
            const panel = byId(id);
            const rows = [...panel.querySelectorAll('[data-donut-row]')];
            const segments = [...panel.querySelectorAll('[data-donut-index]')];
            rows.forEach(row => {
                row.id = id + '-row-' + row.dataset.donutRow;
                row.tabIndex = -1;
            });
            segments.forEach(segment => {
                const target = rows.find(row => row.dataset.donutRow === segment.dataset.donutIndex);
                if (!target) return;
                segment.setAttribute('aria-controls', target.id);
                const select = () => {
                    rows.forEach(row => row.classList.toggle('donut-selected', row === target));
                    segments.forEach(item => item.setAttribute('aria-pressed', String(item === segment)));
                    target.style.setProperty('--selection-color', segment.getAttribute('stroke'));
                    target.focus({preventScroll: true});
                    const bounds = target.getBoundingClientRect();
                    if (bounds.top < 110 || bounds.bottom > innerHeight - 16) {
                        target.scrollIntoView({behavior: reduced() ? 'instant' : 'smooth', block: 'center', inline: 'nearest'});
                    }
                };
                segment.addEventListener('click', select);
                segment.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        select();
                    }
                });
            });
        }
    }
    function costModal() {
        openModal(`Pecahan kos · ${period()}`, `<h3>Jumlah ${money(report.summary.cost)}</h3>${costMarkup()}<section class="agent-discount-note"><div class="cost-row"><span>Potensi keuntungan ejen (diskaun belian)</span><strong>${money(report.summary.agent_discount_potential ?? 0)}</strong></div><p>Pesanan ejen yang telah dibayar dalam penapis laporan: harga asal × kuantiti − jumlah belian selepas diskaun. Potensi jika dijual semula pada harga asal; harga jualan semula sebenar belum direkodkan. Maklumat tambahan sahaja — sudah diambil kira dalam jualan bersih, tidak ditambah kepada jumlah kos. Bonus upline berasingan.</p></section><p style="margin-top:20px">Kos operasi lain belum termasuk. ${report.summary.estimated} unit menggunakan kos semasa; ${report.summary.missing} unit / rekod tanpa kos lengkap.</p>`);
    }
    byId('cost-open').onclick = costModal;
    function updateTarget() {
        const input = byId('target-value'), value = input.valueAsNumber;
        const valid = Number.isFinite(value) && value > 0 && value <= 1000000000;
        let targetMonths = report.filters.month ? 1 : 12;
        if (report.filters.start_date) {
            targetMonths = 0;
            const end = new Date(report.filters.end_date + 'T00:00:00Z');
            for (let date = new Date(report.filters.start_date + 'T00:00:00Z'); date <= end; date.setUTCDate(date.getUTCDate() + 1)) {
                targetMonths += 1 / new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0)).getUTCDate();
            }
        }
        const target = valid ? value * targetMonths : 0;
        const pct = percent(report.summary.sales, target);
        byId('target-period').textContent = `${period()} · ${report.filters.start_date ? 'Sasaran bulanan diprorata mengikut hari' : 'Mengikut penapis jualan'}`;
        byId('target-percent').textContent = valid ? `${pct.toFixed(1)}%` : '—';
        byId('target-ring').style.strokeDashoffset = 314 * (1 - Math.min(1, pct / 100));
        byId('target-badge').textContent = valid && pct >= 100 ? 'Sasaran dicapai' : 'Simulasi';
        byId('target-remaining').textContent = !valid ? 'Masukkan sasaran untuk melihat pencapaian.' : `${money(report.summary.sales)} / ${money(target)} · ${report.summary.sales >= target ? 'Melebihi sasaran ' + money(report.summary.sales - target) : 'Baki ' + money(target - report.summary.sales)}`;
    }
    byId('target-value').addEventListener('input', updateTarget);
    function render() {
        restoreFilters();
        const summary = report.summary, previous = report.previous;
        byId('period-label').textContent = `${period()}${report.period.partial ? ' · Setakat ' + report.period.end.substring(0, 10) : ''}`;
        byId('chart-title').textContent = report.filters.start_date ? (daily() ? 'Prestasi Harian' : 'Prestasi Bulanan') : report.filters.month ? `Prestasi ${full[report.filters.month - 1]}` : 'Prestasi Bulanan';
        byId('eyebrow').textContent = `${daily() ? 'DAILY PERFORMANCE' : 'THE BIG PICTURE'} / ${report.filters.start_date ? period() : report.filters.year}`;
        byId('chart-hint').textContent = summary.transactions === 0 && summary.cost === 0 ? 'Tiada rekod dalam tempoh dan penapis ini.' : `Klik ${daily() ? 'hari untuk melihat transaksi dan kos' : 'bulan untuk melihat prestasi harian'}.`;
        byId('updated-at').textContent = `Dikemas kini ${new Date().toLocaleTimeString('ms-MY', {hour:'2-digit', minute:'2-digit'})}`;
        byId('back-monthly').hidden = report.filters.start_date ? !customBack : !report.filters.month;
        byId('back-monthly').textContent = customBack ? '← Julat asal' : '← Bulanan';
        byId('previous-month').hidden = !!report.filters.start_date || !report.filters.month;
        byId('next-month').hidden = !!report.filters.start_date || !report.filters.month;
        const today = new Date(report.period.end.substring(0, 10) + 'T00:00:00');
        byId('next-month').disabled = !!report.period.partial;
        byId('previous-month').disabled = report.filters.year === 2000 && report.filters.month === 1;
        count('sales', summary.sales); count('cost', summary.cost); count('profit', summary.profit);
        byId('profit').classList.toggle('negative', summary.profit < 0);
        const formatReportDate = value => {
            const [year, month, day] = value.substring(0, 10).split('-').map(Number);
            return `${day} ${months[month - 1]} ${year}`;
        };
        byId('sales-caption').textContent = `${summary.transactions} transaksi`;
        byId('sales-product-count').textContent = `${summary.units.toLocaleString('en-MY')} unit produk`;
        byId('report-range').textContent = `Tempoh: ${formatReportDate(report.period.start)} – ${formatReportDate(report.period.end)}`;
        byId('margin').textContent = `Margin ${summary.margin === null ? '—' : summary.margin.toFixed(1) + '%'} · Anggaran`;
        byId('margin-insight').textContent = summary.margin === null ? '—' : `${summary.margin.toFixed(1)}%`;
        byId('data-quality').hidden = summary.missing === 0;
        byId('data-quality').textContent = `${summary.missing} unit / rekod memerlukan semakan kos atau maklumat varian. Anggaran keuntungan mungkin terlebih nyata. Klik untuk lihat rekod →`;
        chart();
        const known = report.series.filter(row => !row.future), best = known.reduce((a, b) => b.sales > a.sales ? b : a, {sales: 0});
        byId('best').textContent = best.period ? (best.start ? `${best.start} – ${best.end}` : report.filters.month ? `${best.period} ${period()}` : `${full[best.period - 1]} ${report.filters.year}`) : 'Belum ada jualan';
        byId('best-value').textContent = best.period ? money(best.sales) : '—';
        byId('average').textContent = money(known.length ? summary.sales / known.length : 0);
        byId('average-caption').textContent = `Bagi ${known.length} ${daily() ? 'hari' : 'bulan'} dalam tempoh laporan`;
        const growth = previous.sales > 0 ? (summary.sales / previous.sales - 1) * 100 : null;
        const costGrowth = previous.cost > 0 ? (summary.cost / previous.cost - 1) * 100 : null;
        byId('summary').innerHTML = `<div class="eyebrow">SOROTAN PRESTASI</div><h3>${growth === null ? (summary.sales > 0 ? 'Jualan dicatat pada tempoh ini.' : 'Belum ada rekod jualan.') : `Jualan ${growth >= 0 ? 'meningkat' : 'menurun'} ${Math.abs(growth).toFixed(1)}%.`}</h3><p>Banding ${escape(report.period.start)} – ${escape(report.period.end.substring(0, 10))} dengan ${escape(report.period.previous_start)} – ${escape(report.period.previous_end.substring(0, 10))}.${costGrowth === null ? '' : ` Kos ${costGrowth >= 0 ? 'meningkat' : 'menurun'} ${Math.abs(costGrowth).toFixed(1)}%.`}</p><div class="compare-row"><div><small>Jualan tempoh lalu</small><b>${money(previous.sales)}</b></div><div><small>Margin semasa</small><b>${summary.margin === null ? '—' : summary.margin + '%'}</b></div><div><small>Margin tempoh lalu</small><b>${previous.margin === null ? '—' : previous.margin + '%'}</b></div></div><small style="display:block;margin-top:12px">${report.period.partial ? 'Tempoh semasa dibanding sehingga tarikh dan masa yang setara.' : 'Perbandingan tempoh penuh.'} ${growth === null ? 'Peratus pertumbuhan tiada apabila jualan tempoh lalu sifar.' : ''}</small>`;
        updateTarget();
        const mixTotal = report.channels.reduce((sum, row) => sum + row.sales, 0);
        byId('channels').innerHTML = report.channels.length ? report.channels.map((row, i) => `<button type="button" class="channel-card" data-channel="${escape(row.key)}"><span class="name"><i class="dot" style="--c:${colors[i]}"></i>${escape(row.name)}</span><strong>${money(row.sales)}</strong>${meter(percent(row.sales, mixTotal), colors[i])}<small>${percent(row.sales, mixTotal).toFixed(1)}% jualan dalam lokasi dipilih</small></button>`).join('') : empty('Tiada saluran dengan rekod dalam pilihan ini.');
        const businessTotal = report.channels.filter(row => ['pos', 'orders'].includes(row.key)).reduce((sum, row) => sum + row.sales, 0);
        byId('channels').insertAdjacentHTML('beforeend', `<div class="channel-card"><span class="name"><i class="dot" style="--c:#28b997"></i>Jumlah Perniagaan</span><strong>${money(businessTotal)}</strong>${meter(100, '#28b997')}<small>POS + Pesanan Ejen · ${escape(period())}</small></div>`);
        byId('channels').querySelectorAll('button').forEach(button => button.onclick = () => load({channel: button.dataset.channel}));
        const costRows = [['Modal produk', 'capital'], ['Komisen pelanggan', 'commission'], ['Gaji direkodkan', 'salary'], ['Bonus weekly closing', 'bonus']].map(([name, key], i) => ({name, value: summary[key], color: donutColors[i]}));
        byId('cost-breakdown').innerHTML = `<div class="breakdown-heading"><h3>Di sebalik setiap ringgit.</h3><small>Pecahan kos · ${escape(period())}</small></div>${donutMarkup(costRows, 'Jumlah kos')}${breakdownRows(costRows)}<button type="button" class="textbtn" id="cost-details">Lihat asas pengiraan →</button>`;
        byId('cost-details').onclick = costModal;
        const siteRows = report.sites.map((row, i) => ({name: row.name, value: row.sales, color: donutColors[i % donutColors.length]}));
        const siteTotal = siteRows.reduce((sum, row) => sum + row.value, 0);
        byId('site-ranking').innerHTML = '<div class="breakdown-heading"><h3>Prestasi business site</h3><small>Sumbangan jualan POS · ' + escape(period()) + '</small></div>' + donutMarkup(siteRows, 'Jualan lokasi') + (report.sites.length ? report.sites.map((row, i) => `<div class="rank donut-rank" data-donut-row="${i}"><i class="breakdown-swatch" style="background:${siteRows[i].color}" aria-hidden="true"></i><div class="rank-body"><div class="rank-title"><button class="textbtn" data-site="${row.id}">${escape(row.name)} →</button><b>${money(row.sales)}</b><span class="breakdown-percent">${siteTotal > 0 ? percent(row.sales, siteTotal).toFixed(1) + '%' : '—'}</span></div><small>Kos ${money(row.cost)} · Anggaran untung ${money(row.sales - row.cost)}</small></div></div>`).join('') : empty('Tiada rekod POS / gaji lokasi dalam pilihan ini.'));
        byId('site-ranking').querySelectorAll('button').forEach(button => button.onclick = () => load({site: button.dataset.site, channel: 'pos'}));
        const distribution = report.product_distribution;
        const productRows = report.products.map((row, i) => ({name: row.name, value: row.profit, color: donutColors[i]}));
        if (distribution.other_profit > 0) productRows.push({name: 'Produk lain', value: distribution.other_profit, color: donutColors[5]});
        byId('product-ranking').innerHTML = '<div class="breakdown-heading"><h3>Produk penyumbang untung</h3><small>Selepas modal & komisen pelanggan; sebelum gaji / bonus</small></div>' + donutMarkup(productRows, 'Untung positif') + (report.products.length ? report.products.map((row, i) => `<div class="rank donut-rank" data-donut-row="${i}"><i class="breakdown-swatch" style="background:${productRows[i].color}" aria-hidden="true"></i><div class="rank-body"><div class="rank-title"><button class="textbtn" data-product="${i}">${escape(row.name)} ↗</button><b>${money(row.profit)}</b><span class="breakdown-percent">${percent(row.profit, distribution.positive_total).toFixed(1)}%</span></div><small>${row.units} unit · Jualan ${money(row.sales)}</small></div></div>`).join('') : empty('Tiada sumbangan untung positif dalam tempoh ini.')) + (distribution.other_profit > 0 ? `<div class="breakdown-row" data-donut-row="${report.products.length}"><span class="breakdown-name"><i style="background:${donutColors[5]}" aria-hidden="true"></i>Produk lain</span><strong>${money(distribution.other_profit)}</strong><span class="breakdown-percent">${percent(distribution.other_profit, distribution.positive_total).toFixed(1)}%</span></div>` : '') + (distribution.losses.length ? `<div class="product-losses"><h4>Produk rugi</h4><p>Jumlah ${money(distribution.loss_total)} · Tidak termasuk dalam donat untung positif</p>${distribution.losses.map(row => `<div class="cost-row"><span>${escape(row.name)}</span><strong class="negative">${money(row.profit)}</strong></div>`).join('')}</div>` : '');
        byId('product-ranking').querySelectorAll('button').forEach(button => button.onclick = () => { const row = report.products[Number(button.dataset.product)]; openModal(row.name, `<p>${escape(period())} · ${row.units} unit</p><div class="cost-row"><span>Jualan bersih produk</span><b>${money(row.sales)}</b></div><div class="cost-row"><span>Anggaran untung produk</span><b>${money(row.profit)}</b></div><p>Selepas modal dan komisen pelanggan, sebelum gaji serta bonus weekly closing. Kos semasa digunakan apabila snapshot tiada.</p>`); });
        const transactionRows = report.transaction_channels.map(row => ({name: row.name, value: row.sales, color: channelColor(row.key)}));
        byId('transaction-preview').innerHTML = `<div class="breakdown-heading"><h3>Jejak setiap transaksi.</h3><small>Nilai jualan mengikut saluran · ${escape(period())}</small></div>` + donutMarkup(transactionRows, 'Jumlah jualan') + breakdownRows(transactionRows) + `<div class="transaction-recent"><h4>Transaksi terkini</h4><small>${report.transactions.total} rekod jualan / kos dalam penapis semasa</small>` + report.transactions.rows.slice(0,4).map(row => `<div class="rank"><span class="rank-index">↗</span><div class="rank-body"><div class="rank-title"><a class="textbtn" href="${escape(row.url)}">${escape(row.reference)}</a><b>${money(row.kind === 'sale' ? row.sales : row.cost)}</b></div><small>${escape(row.date.substring(0,10))} · ${escape(row.channel)} · ${escape(row.kind)}</small></div></div>`).join('') + '</div><button type="button" class="textbtn" id="all-transactions">Lihat rekod & butiran →</button>';
        byId('all-transactions').onclick = () => showTransactions(null, 1);
        bindDonutSelections();
    }
    byId('data-quality').onclick = () => showCostQuality('missing', 1);
    async function showCostQuality(category, page) {
        dialogController?.abort();
        dialogController = new AbortController();
        const controller = dialogController;
        openModal(`Rekod kos perlu semakan · ${period()}`, '<p role="status">Memuatkan rekod kos…</p>');
        try {
            const data = await request(root.dataset.reportUrl, { ...report.filters, cost_quality: category, page }, controller.signal);
            if (controller !== dialogController || !byId('dashboard-modal').open) return;
            const transactions = data.transactions;
            const sourceLabels = { missing: 'Kos belum ditetapkan', variant_missing: 'Maklumat varian belum lengkap', current: 'Kos semasa (anggaran)', snapshot: 'Kos asal tersimpan' };
            byId('modal-body').innerHTML = `<p>Senarai mengikut tempoh, saluran dan lokasi dashboard. Kos clicker: kuantiti casing × kos casing lengkap mengikut bilangan huruf (Character Pricing). Hanya transaksi yang kosnya belum dapat dikira sepenuhnya disenaraikan. Rekod dengan kos lengkap tidak termasuk, walaupun menggunakan kos semasa.</p><p class="subtle">${transactions.total} transaksi terlibat. Klik rujukan untuk membuka rekod asal.</p>` + (transactions.rows.length ? transactions.rows.map(row => `<article class="cost-record"><div class="cost-record-head"><div><a class="textbtn" href="${escape(row.url)}">${escape(row.reference)} ↗</a><small>${escape(row.date)} · ${escape(row.channel)}${row.site ? ' · ' + escape(row.site) : ''}</small></div><span class="badge ${row.missing_units ? 'warn' : ''}">${row.missing_units ? 'Kos belum lengkap' : 'Anggaran kos'}</span></div><p class="quality-counts">${row.estimated_units} unit tanpa snapshot · ${row.missing_units} unit / rekod tanpa kos lengkap · Modal yang dapat dikira: ${money(row.capital)}</p>${row.cost_items.length ? `<div class="tablewrap"><table><thead><tr><th>Produk</th><th>Kuantiti casing / unit</th><th>Kos / unit lengkap</th><th>Jumlah kos</th><th>Sumber kos</th></tr></thead><tbody>${row.cost_items.map(item => `<tr><td>${escape(item.name)}</td><td>${item.quantity}</td><td>${money(item.unit_cost)}</td><td>${money(item.total_cost)}</td><td>${sourceLabels[item.source]}</td></tr>`).join('')}</tbody></table></div>` : '<p>Tiada item aktif untuk menentukan kos rekod ini. Semak rekod asal.</p>'}</article>`).join('') + `<div class="pagination">${pagination(transactions.page, transactions.total, transactions.per_page)}</div>` : empty('Tiada rekod dalam kategori ini.'));
            byId('modal-body').querySelectorAll('[data-page]').forEach(button => button.onclick = () => showCostQuality(category, Number(button.dataset.page)));
        } catch (error) {
            if (error.name !== 'AbortError') byId('modal-body').innerHTML = `<p role="alert">${escape(error.message)}</p>`;
        }
    }

    function pagination(page, total, perPage) {
        const pages = Math.max(1, Math.ceil(total / perPage));
        return `<span>Halaman ${page} / ${pages} · ${total} rekod</span><button type="button" data-page="${page - 1}" ${page <= 1 ? 'disabled' : ''}>←</button><button type="button" data-page="${page + 1}" ${page >= pages ? 'disabled' : ''}>→</button>`;
    }
    async function showTransactions(day, page, dates = {}) {
        dialogController?.abort();
        dialogController = new AbortController();
        const controller = dialogController;
        openModal(dates.start_date ? `Rekod · ${dates.start_date}` : day ? `${day} ${period()}` : `Rekod · ${period()}`, '<p role="status">Memuatkan rekod…</p>');
        try {
            const data = await request(root.dataset.reportUrl, {...report.filters, ...dates, day, page}, controller.signal);
            if (controller !== dialogController || !byId('dashboard-modal').open) return;
            const transactions = data.transactions;
            byId('modal-body').innerHTML = `<p>Rekod asal boleh dibuka melalui nombor rujukan. Angka kos termasuk anggaran apabila snapshot tiada.</p><a class="textbtn" href="${escape(root.dataset.exportUrl + '?' + query({...report.filters, ...dates, day}))}">↓ Eksport tempoh ini</a>` + (transactions.rows.length ? `<div class="tablewrap"><table><thead><tr><th>Rujukan / Tarikh</th><th>Saluran / Lokasi</th><th>Jualan</th><th>Kos</th><th>Anggaran untung</th></tr></thead><tbody>${transactions.rows.map(row => `<tr><td><a href="${escape(row.url)}" class="textbtn">${escape(row.reference)}</a><small>${escape(row.date)} · ${escape(row.kind)}</small></td><td>${escape(row.channel)}<small>${escape(row.site || 'Tanpa lokasi')}</small></td><td>${money(row.sales)}</td><td>${money(row.cost)}</td><td>${money(row.profit)}</td></tr>`).join('')}</tbody></table></div><div class="pagination">${pagination(transactions.page, transactions.total, transactions.per_page)}</div>` : empty('Tiada rekod bagi tarikh / penapis ini.'));
            byId('modal-body').querySelectorAll('[data-page]').forEach(button => button.onclick = () => showTransactions(day, Number(button.dataset.page), dates));
        } catch (error) {
            if (error.name !== 'AbortError') byId('modal-body').innerHTML = `<p role="alert">${escape(error.message)}</p>`;
        }
    }
    function renderInventory() {
        if (!inventory) return;
        const summary = inventory.summary;
        byId('stock-metrics').innerHTML = [['Stok tersedia', 'stock-units', `${summary.reserved} unit diperuntukkan`], ['Aset tersedia pada kos', 'stock-asset', `Aset diperuntukkan ${money(summary.reserved_asset)}`], ['Potensi nilai jualan', 'stock-potential', 'Berdasarkan harga semasa'], ['Potensi untung kasar', 'stock-profit', 'Belum direalisasi']].map(([label, id, caption]) => `<div class="stock-metric"><label>${label}</label><strong id="${id}">—</strong><small>${['stock-units', 'stock-asset'].includes(id) ? `<button type="button" class="textbtn" data-reservations aria-haspopup="dialog">${caption} →</button>` : caption}</small></div>`).join('');
        byId('stock-metrics').querySelectorAll('[data-reservations]').forEach(button => button.onclick = () => showReservations(1));
        count('stock-units', summary.units, false); count('stock-asset', summary.asset); count('stock-potential', summary.potential_sales); count('stock-profit', summary.potential_profit);
        byId('stock-quality').disabled = false;
        byId('stock-quality').hidden = summary.unknown === 0 && summary.negative === 0;
        byId('stock-quality').textContent = `${summary.pricing_missing} rekod kos / harga belum lengkap. ${summary.allocation_missing} rekod belum mempunyai pecahan stok casing / bilangan huruf; jumlah nilai hanya meliputi nilai yang diketahui. ${summary.negative} rekod baki negatif memerlukan semakan; baki negatif tidak digunakan untuk penilaian aset. Klik untuk lihat rekod →`;
        byId('stock-rows').innerHTML = inventory.rows.length ? inventory.rows.map((row, i) => `<tr><td><div class="stock-product-cell"><div><button type="button" data-stock="${i}">${escape(row.name)} ↗</button><small>${escape(row.code)} · ${escape(row.variant || row.type)}</small></div>${row.edit_url ? `<a class="stock-edit" href="${escape(row.edit_url)}" target="_blank" rel="noopener noreferrer" title="Edit produk (tab baharu)" aria-label="Edit produk ${escape(row.name)} (tab baharu)">${byId('stock-edit-icon').innerHTML}</a>` : ''}</div></td><td>${row.available}</td><td>${row.reserved}</td><td>${money(row.cost)}</td><td>${money(row.asset)}</td><td>${money(row.price)}</td><td>${money(row.potential_profit)}</td><td><span class="badge ${row.is_discontinued ? 'warn' : row.available <= 0 ? 'danger' : row.available < 5 ? 'warn' : ''}">${row.is_discontinued ? 'Dihentikan' : row.available <= 0 ? 'Habis' : row.available < 5 ? 'Rendah' : 'Mencukupi'}</span></td></tr>`).join('') : '<tr><td colspan="8" class="empty">Tiada produk sepadan.</td></tr>';
        byId('stock-rows').querySelectorAll('button').forEach(button => button.onclick = () => {
            const row = inventory.rows[Number(button.dataset.stock)];
            openModal(row.name, `<p>${escape(row.code)} · ${escape(row.variant)} · Stok pusat semasa</p><div class="cost-row"><span>Tersedia / diperuntukkan</span><b>${row.available} / ${row.reserved} unit</b></div><div class="cost-row"><span>Aset tersedia</span><b>${money(row.asset)}</b></div><div class="cost-row"><span>Aset diperuntukkan</span><b>${money(row.cost === null ? null : row.reserved * row.cost)}</b></div><div class="cost-row"><span>Potensi untung kasar tersedia</span><b>${money(row.potential_profit)}</b></div><p>Potensi untung belum direalisasi; belum menolak diskaun, komisen atau kos operasi.</p><a class="textbtn" href="${escape(row.url)}">Lihat produk →</a>`);
        });
        byId('stock-pagination').innerHTML = pagination(inventory.page, summary.records, inventory.per_page);
        byId('stock-pagination').querySelectorAll('button').forEach(button => button.onclick = () => { stockPage = Number(button.dataset.page); loadInventory(); });
        byId('stock-alerts').innerHTML = `<h3>Perlu perhatian</h3><button type="button" class="notice quality-notice" data-stock-status="low" aria-controls="stock-rows">${summary.low} rekod stok rendah (1–4 unit). Lihat stok →</button><button type="button" class="notice danger quality-notice" data-stock-status="out" aria-controls="stock-rows">${summary.empty} rekod stok habis / baki negatif. Lihat stok →</button><p class="stock-note">Aset tersedia & diperuntukkan yang diketahui: <b>${money(summary.asset + summary.reserved_asset)}</b>.</p>`;
        byId('stock-alerts').querySelectorAll('[data-stock-status]').forEach(button => button.onclick = () => {
            const form = byId('stock-filters');
            form.elements.stock_type.value = inventoryFilters.stock_type || 'all';
            form.elements.stock_search.value = inventoryFilters.stock_search || '';
            form.elements.stock_status.value = button.dataset.stockStatus;
            stockPage = 1;
            loadInventory();
            form.elements.stock_status.focus({preventScroll: true});
            form.scrollIntoView({behavior: reduced() ? 'instant' : 'smooth', block: 'center'});
        });
        byId('stock-leaders').innerHTML = '<h3>Modal terbesar dalam stok</h3>' + inventory.leaders.map(row => `<div class="cost-row"><span>${escape(row.name)}</span><b>${money(row.asset)}</b></div>${meter(percent(row.asset, summary.asset), '#688db5')}`).join('');
    }
    async function loadInventory() {
        inventoryController?.abort(); inventoryController = new AbortController();
        const controller = inventoryController;
        byId('stock-filters').setAttribute('aria-busy', 'true');
        try {
            const filters = Object.fromEntries(new FormData(byId('stock-filters')));
            const data = await request(root.dataset.inventoryUrl, {...filters, stock_page: stockPage}, controller.signal);
            if (controller !== inventoryController) return;
            inventoryFilters = filters;
            inventory = data;
            renderInventory();
        } catch (error) {
            if (error.name !== 'AbortError') { byId('stock-quality').disabled = true; byId('stock-quality').hidden = false; byId('stock-quality').textContent = `${error.message} Stok sebelumnya masih dipaparkan.`; }
        } finally { if (controller === inventoryController) byId('stock-filters').removeAttribute('aria-busy'); }
    }
    async function showReservations(page) {
        dialogController?.abort();
        dialogController = new AbortController();
        const controller = dialogController;
        openModal('Stok & aset diperuntukkan', '<p role="status">Memuatkan pesanan…</p>');
        try {
            const data = await request(root.dataset.inventoryUrl, {...inventoryFilters, stock_reservations: 1, stock_page: page}, controller.signal);
            if (controller !== dialogController || !byId('dashboard-modal').open) return;
            const statuses = {pending: 'Menunggu proses', processing: 'Sedang diproses', ready: 'Sedia untuk serahan', pickup_ready: 'Sedia untuk diambil'};
            byId('modal-body').innerHTML = `<p>${data.units} unit diperuntukkan · Nilai kos diketahui ${money(data.asset)}. Mengikut penapis stok semasa; stok ini sudah dikhaskan untuk pesanan.</p>${data.restricted ? '<p class="notice">Hanya pesanan yang anda dibenarkan melihat dipaparkan. Jumlah mungkin berbeza daripada kad stok.</p>' : ''}${data.unknown_units ? `<p class="notice">${data.unknown_units} unit belum dapat dinilai; semak kos atau pecahan stok.</p>` : ''}` + (data.rows.length ? data.rows.map(row => `<article class="cost-record"><div class="cost-record-head"><a class="textbtn" href="${escape(row.url)}">${escape(row.reference)} ↗</a><span class="badge">${escape(statuses[row.status] || row.status)}</span></div><h3>${escape(row.name)}</h3><p>${escape(row.code)} · ${escape(row.variant || (row.character_count ? row.character_count + ' huruf' : 'Produk biasa'))}</p><div class="cost-row"><span>Kuantiti diperuntukkan</span><b>${row.quantity} unit</b></div><div class="cost-row"><span>Kos seunit</span><b>${money(row.cost)}</b></div><div class="cost-row"><span>Jumlah kos diperuntukkan</span><b>${money(row.total_cost)}</b></div></article>`).join('') + `<div class="pagination">${pagination(data.page, data.total, data.per_page)}</div>` : empty('Tiada stok diperuntukkan dalam pilihan ini.'));
            byId('modal-body').querySelectorAll('[data-page]').forEach(button => button.onclick = () => showReservations(Number(button.dataset.page)));
        } catch (error) {
            if (error.name !== 'AbortError' && controller === dialogController) byId('modal-body').innerHTML = `<p role="alert">${escape(error.message)}</p>`;
        }
    }
    async function showStockQuality(category, page) {
        dialogController?.abort();
        dialogController = new AbortController();
        const controller = dialogController;
        openModal('Semakan kelengkapan stok & aset', '<p role="status">Memuatkan rekod stok…</p>');
        try {
            const data = await request(root.dataset.inventoryUrl, {...inventoryFilters, stock_quality: category, stock_page: page}, controller.signal);
            if (controller !== dialogController || !byId('dashboard-modal').open) return;
            byId('modal-body').innerHTML = `<p>Stok semasa mengikut penapis stok yang dipaparkan. Paparan ini untuk semakan sahaja. Rekod yang mempunyai kedua-dua isu disenaraikan sekali.</p><label class="quality-filter">Paparkan<select id="stock-quality-filter"><option value="all">Semua rekod terlibat</option><option value="missing">Kos / harga belum lengkap</option><option value="allocation">Pecahan stok belum tersedia</option><option value="negative">Baki negatif</option></select></label><p class="subtle">${data.summary.records} rekod terlibat. Klik produk untuk membuka rekod asal.</p>` + (data.rows.length ? data.rows.map(row => {
                const issues = [];
                if (row.allocation_missing) {
                    issues.push('Pecahan stok mengikut casing / bilangan huruf belum tersedia. Nilai aset belum dapat dikira dengan tepat.');
                    if (row.pricing_missing) issues.push('Tetapan kos / harga bilangan huruf juga belum lengkap.');
                }
                else {
                    if (row.cost === null) issues.push('Kos seunit belum diketahui.');
                    if (row.price === null) issues.push('Harga jualan belum diketahui.');
                }
                if (row.available < 0) issues.push('Baki negatif perlu disemak; kuantiti untuk penilaian aset dianggap sifar.');
                return `<article class="cost-record"><div class="cost-record-head"><div><a class="textbtn" href="${escape(row.url)}">${escape(row.name)} ↗</a><small>${escape(row.code)}${row.variant ? ' · ' + escape(row.variant) : ''}</small></div><span class="badge warn">Perlu semakan</span></div><p>${issues.join(' ')}</p><div class="cost-row"><span>Baki tersedia</span><b class="${row.available < 0 ? 'negative' : ''}">${row.available} unit</b></div><div class="cost-row"><span>Diperuntukkan</span><b>${row.reserved} unit</b></div><div class="cost-row"><span>Kos seunit</span><b>${row.allocation_missing ? (row.pricing_missing ? 'Semak Character Pricing' : 'Sudah ditetapkan mengikut bilangan huruf') : money(row.cost)}</b></div><div class="cost-row"><span>Harga jualan seunit</span><b>${row.allocation_missing ? (row.pricing_missing ? 'Semak Character Pricing' : 'Sudah ditetapkan mengikut bilangan huruf') : money(row.price)}</b></div></article>`;
            }).join('') + `<div class="pagination">${pagination(data.page, data.summary.records, data.per_page)}</div>` : empty('Tiada rekod dalam kategori ini.'));
            byId('stock-quality-filter').value = category;
            byId('stock-quality-filter').onchange = event => showStockQuality(event.target.value, 1);
            byId('modal-body').querySelectorAll('[data-page]').forEach(button => button.onclick = () => showStockQuality(category, Number(button.dataset.page)));
        } catch (error) {
            if (error.name !== 'AbortError' && controller === dialogController) byId('modal-body').innerHTML = `<p role="alert">${escape(error.message)}</p>`;
        }
    }
    if (byId('stock-filters')) {
        byId('stock-quality').onclick = () => showStockQuality('all', 1);
        byId('stock-filters').onsubmit = event => { event.preventDefault(); stockPage = 1; loadInventory(); };
        byId('stock-filters').addEventListener('change', event => { if (event.target.tagName === 'SELECT' || event.target.name === 'stock_include_discontinued') { stockPage = 1; loadInventory(); } });
    }
    byId('replay').onclick = () => { render(); renderInventory(); };
    render(); renderInventory();
}
