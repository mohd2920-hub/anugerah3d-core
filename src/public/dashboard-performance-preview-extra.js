// Standalone design demonstration. No application or database connections.
const channelNames = ['POS', 'Pesanan Ejen', 'Pembelian Pelanggan'];
const siteNames = ['Shah Alam', 'Bangi', 'Setia Alam'];
const productNames = ['Clicker Custom', 'Keychain Nama', 'Miniatur Premium', 'Corporate Gift', 'Phone Stand', 'Tag Beg'];
const unitCosts = [5, 3, 18, 15, 6, 2];
const prices = [14, 9, 48, 40, 18, 7];
const palette = ['#3b92f7', '#9b88da', '#28b997', '#ddb36b'];
let year = 2026;
const ledger = [];
for (let yr = 2024; yr <= 2026; yr++) {
    for (let m = 0; m < 12; m++) {
        for (let day = 1; day <= new Date(yr, m + 1, 0).getDate(); day++) {
            for (let channel = 0; channel < 3; channel++) {
                for (let site = 0; site < 3; site++) {
                    const product = (day + channel * 2 + site + m) % 6;
                    const qty = 1 + ((day * 7 + site * 3 + channel + m + yr) % 4) + Math.floor(m / 4) + (yr - 2024);
                    const sales = prices[product] * qty;
                    const capital = unitCosts[product] * qty;
                    const commission = channel === 0 ? 0 : Math.round(sales * .1);
                    const salary = channel === 0 ? Math.round(sales * .12) : 0;
                    const operating = Math.round(sales * .07);
                    ledger.push({year: yr, month: m, day, channel, site, product, qty, sales, capital, commission, salary, operating, cost: capital + commission + salary + operating,
                        id: `DEMO-${yr}${String(m + 1).padStart(2, '0')}${String(day).padStart(2, '0')}-${channel}${site}`});
                }
            }
        }
    }
}

document.querySelector('.metrics').insertAdjacentHTML('beforebegin', `
<nav class="section-nav" aria-label="Bahagian preview"><a href="#performance">Prestasi</a><a href="#analysis">Analisis</a><a href="#inventory">Stok & aset</a></nav>
<div class="filters" id="performance">
 <label>Tahun<select id="yearFilter"><option>2026</option><option>2025</option></select></label>
 <label>Saluran<select id="channelFilter"><option value="all">Semua saluran</option>${channelNames.map((n,i)=>`<option value="${i}">${n}</option>`).join('')}</select></label>
 <label>Business site<select id="siteFilter"><option value="all">Semua lokasi</option>${siteNames.map((n,i)=>`<option value="${i}">${n}</option>`).join('')}</select></label>
 <label>Banding dengan<select id="comparison"><option value="previous">Tempoh sebelumnya</option><option value="year">Tahun sebelumnya</option></select></label>
 <button class="action" id="export">↓ Eksport CSV</button><span id="exportStatus" role="status"></span>
</div>`);
document.querySelector('.metric:nth-child(2)').insertAdjacentHTML('beforeend', '<button class="cost-trigger" id="costOpen">Lihat pecahan kos →</button>');
document.querySelector('footer').insertAdjacentHTML('beforebegin', `
<div id="analysis">
 <div class="twocol">
  <section class="card summary-card" id="summary"></section>
  <section class="card"><div class="cardhead"><div><h3>Sasaran jualan</h3><small id="targetPeriod"></small></div><span class="badge" id="targetBadge"></span></div><div class="target-layout"><div class="ring"><svg viewBox="0 0 120 120" aria-hidden="true"><circle class="track" cx="60" cy="60" r="50"/><circle class="fill" id="targetRing" cx="60" cy="60" r="50"/></svg><strong id="targetPercent"></strong></div><div><label for="targetValue" class="subtle">Sasaran bulanan (RM)</label><br><input id="targetValue" type="number" min="1" max="10000000" step="1000" value="25000"><p id="targetRemaining"></p><small>Sasaran contoh boleh diubah.</small></div></div></section>
 </div>
 <div class="section-heading"><div><div class="tag">REVENUE MIX</div><h2>Dari mana jualan datang?</h2><p>Klik saluran untuk menapis keseluruhan prestasi.</p></div></div>
 <div class="channel-grid" id="channels"></div>
 <div class="twocol"><section class="card" id="costBreakdown"></section><section class="card" id="siteRanking"></section></div>
 <div class="twocol"><section class="card" id="productRanking"></section><section class="card" id="transactionPreview"></section></div>
</div>
<section class="stock-shell" id="inventory" style="margin-top:38px">
 <div class="section-heading" style="margin-top:0"><div><div class="tag">INVENTORY INTELLIGENCE</div><h2>Stok hari ini. Potensi esok.</h2><p>Nilai aset pada kos dan potensi untung stok yang tersedia.</p></div><span class="pill">Stok semasa · Data contoh</span></div>
 <div class="stockfilters"><label>Kategori<select id="stockCategory"><option value="all">Semua kategori</option><option>Clicker</option><option>Aksesori</option><option>Hadiah</option></select></label><label>Lokasi stok<select id="stockSite"><option value="all">Semua lokasi</option>${siteNames.map((n,i)=>`<option value="${i}">${n}</option>`).join('')}</select></label><label>Cari produk<input id="stockSearch" type="search" placeholder="Nama produk…"></label><label>Status<select id="stockStatus"><option value="all">Semua status</option><option value="low">Stok rendah</option><option value="out">Stok habis</option></select></label></div>
 <div class="stock-metrics" id="stockMetrics"></div>
 <div class="tablewrap"><table><thead><tr><th>Produk / Lokasi</th><th>Tersedia</th><th>Diperuntukkan</th><th>Kos / unit</th><th>Aset tersedia</th><th>Harga jualan</th><th>Potensi untung kasar</th><th>Status</th></tr></thead><tbody id="stockRows"></tbody></table></div>
 <div class="stock-note">Nilai aset tersedia = unit tersedia × kos seunit. Potensi untung belum direalisasi dan belum menolak diskaun, komisen atau kos operasi. Stok diperuntukkan masih aset tetapi diasingkan daripada stok tersedia. Penapis stok berasingan daripada tempoh dan saluran jualan di atas.</div>
 <div class="stock-bottom"><div id="stockAlerts"></div><div id="stockLeaders"></div></div>
</section>
<dialog id="modal" aria-labelledby="modalTitle"><div class="dialog-head"><div><div class="tag" style="font-size:9px;margin-bottom:6px">BUTIRAN · DATA CONTOH</div><h2 id="modalTitle"></h2></div><button id="closeModal" aria-label="Tutup butiran">×</button></div><div class="dialog-body" id="modalBody"></div></dialog>`);

function scopedRows(yr = year, month = selected, ignoreChannel = false, ignoreSite = false) {
    return ledger.filter(r => r.year === yr && (month === null || r.month === month)
        && (ignoreChannel || el('channelFilter').value === 'all' || r.channel === Number(el('channelFilter').value))
        && (ignoreSite || el('siteFilter').value === 'all' || r.site === Number(el('siteFilter').value)));
}
function total(rows, key = 'sales') { return rows.reduce((sum, r) => sum + r[key], 0); }
function aggregate(rows, label) { return {label, sales: total(rows), cost: total(rows, 'cost')}; }
daily = function(m) { const rows = scopedRows(year, m); return Array.from({length: new Date(year, m + 1, 0).getDate()}, (_,i) => aggregate(rows.filter(r => r.day === i + 1), String(i + 1))); };
function periodText() { return selected === null ? `Tahun ${year}` : `${full[selected]} ${year}`; }
function openModal(title, html) { el('modalTitle').textContent = title; el('modalBody').innerHTML = html; if (!el('modal').open) el('modal').showModal(); }
el('closeModal').onclick = () => el('modal').close();
el('modal').addEventListener('click', e => {if (e.target === el('modal')) {const r=el('modal').getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)el('modal').close();}});
function transactionsTable(rows) {
    return `<div class="tablewrap"><table><thead><tr><th>Resit / Tarikh</th><th>Saluran / Lokasi</th><th>Produk</th><th>Jualan</th><th>Kos</th><th>Untung</th></tr></thead><tbody>${rows.map(r=>`<tr><td>${r.id}<small>${r.day} ${months[r.month]} ${r.year}</small></td><td>${channelNames[r.channel]}<small>${siteNames[r.site]}</small></td><td>${productNames[r.product]}<small>${r.qty} unit</small></td><td>${money(r.sales)}</td><td>${money(r.cost)}</td><td>${money(r.sales-r.cost)}</td></tr>`).join('')}</tbody></table></div>`;
}
function showTransactions(rows, label) {
    openModal(label, `<p>${rows.length} transaksi · Jualan <b>${money(total(rows))}</b> · Kos <b>${money(total(rows,'cost'))}</b> · Untung <b>${money(total(rows)-total(rows,'cost'))}</b></p>${transactionsTable(rows)}`);
}
const costKeys = [['Modal produk','capital'],['Komisen ejen','commission'],['Gaji POS','salary'],['Kos operasi','operating']];
function costMarkup(rows) { const cost = total(rows,'cost'); return costKeys.map(([label,key],i)=>`<div class="cost-row"><span>${label}</span><strong>${money(total(rows,key))}</strong></div><div class="meter"><span style="--width:${total(rows,key)/cost*100}%;--color:${palette[i]}"></span></div>`).join(''); }
function costModal() {const rows=scopedRows();openModal(`Pecahan kos · ${periodText()}`, `<h3>Jumlah kos ${money(total(rows,'cost'))}</h3>${costMarkup(rows)}<p style="margin-top:20px;font-size:12px">Komisen hanya pada pesanan ejen / pelanggan; gaji contoh diperuntukkan pada POS. Semua komponen contoh dikira sekali.</p>`);}
el('costOpen').onclick = costModal;

function updateTarget() {
    const input=el('targetValue');const valid=input.valueAsNumber;
    if (!Number.isFinite(valid) || valid<1 || valid>10000000) {input.setCustomValidity('Masukkan sasaran antara RM 1 dan RM 10,000,000.');input.reportValidity();return;}
    input.setCustomValidity('');const target=valid*(selected===null?12:1),sales=total(scopedRows()),pct=sales/target*100;
    el('targetPeriod').textContent=periodText()+' · Mengikut penapis jualan';
    el('targetPercent').textContent=pct.toFixed(1)+'%';
    el('targetRing').style.strokeDashoffset=314*(1-Math.min(1,pct/100));
    el('targetBadge').textContent=pct>=100?'Sasaran dicapai':'Dalam perjalanan';
    el('targetRemaining').textContent=`${money(sales)} / ${money(target)} · ${sales>=target?'Melebihi sasaran '+money(sales-target):'Baki '+money(target-sales)}`;
}
el('targetValue').addEventListener('change', updateTarget);
function updatePanels() {
    const rows=scopedRows(),sales=total(rows),cost=total(rows,'cost');
    el('periodLabel').textContent=periodText()+' · Data contoh';
    el('eyebrow').textContent=(selected===null?'THE BIG PICTURE / ':'PRESTASI HARIAN / ')+year;
    el('chart').setAttribute('aria-label',`Jualan kos dan untung ${periodText()}. Pilih ${selected===null?'bulan':'hari'} untuk butiran.`);
    if(selected===null)el('best').textContent=el('best').textContent.replace('2026',String(year));
    let compareYear=year,compareMonth=selected;
    if(selected===null||el('comparison').value==='year')compareYear--;
    else if(selected===0){compareYear--;compareMonth=11;}else compareMonth--;
    const previous=scopedRows(compareYear,compareMonth),prevSales=total(previous),prevCost=total(previous,'cost');
    const growth=(sales/prevSales-1)*100,costGrowth=(cost/prevCost-1)*100,margin=(sales-cost)/sales*100,previousMargin=(prevSales-prevCost)/prevSales*100;
    const comparisonLabel=compareMonth===null?`tahun ${compareYear}`:`${full[compareMonth]} ${compareYear}`;
    el('summary').innerHTML=`<div class="eyebrow">SOROTAN AUTOMATIK</div><h3>Jualan ${growth>=0?'meningkat':'menurun'} ${Math.abs(growth).toFixed(1)}%.</h3><p>${periodText()} berbanding ${comparisonLabel}. Kos ${costGrowth>=0?'meningkat':'menurun'} ${Math.abs(costGrowth).toFixed(1)}%. Margin ${margin>=previousMargin?'bertambah':'berkurang'} ${Math.abs(margin-previousMargin).toFixed(1)} mata peratusan.</p><div class="compare-row"><div><small>Jualan tempoh lalu</small><b>${money(prevSales)}</b></div><div><small>Margin semasa</small><b>${margin.toFixed(1)}%</b></div><div><small>Margin tempoh lalu</small><b>${previousMargin.toFixed(1)}%</b></div></div><small style="display:block;margin-top:12px">Perbandingan tempoh penuh menggunakan data rekaan.</small>`;
    updateTarget();
    const mixRows=scopedRows(year,selected,true),mixTotal=total(mixRows);
    el('channels').innerHTML=channelNames.map((name,i)=>{const value=total(mixRows.filter(r=>r.channel===i));return `<button class="channel-card" data-channel="${i}"><span class="name"><i class="dot" style="--c:${palette[i]}"></i>${name}</span><strong>${money(value)}</strong><div class="meter"><span style="--width:${value/mixTotal*100}%;--color:${palette[i]}"></span></div><small>${(value/mixTotal*100).toFixed(1)}% jualan lokasi dipilih · Klik untuk tapis</small></button>`}).join('');
    el('channels').querySelectorAll('button').forEach(b=>b.onclick=()=>{el('channelFilter').value=b.dataset.channel;render();});
    el('costBreakdown').innerHTML=`<div class="cardhead"><div><h3>Di sebalik setiap ringgit.</h3><small>Pecahan kos · ${periodText()}</small></div><span class="badge">${money(cost)}</span></div>${costMarkup(rows)}<button class="textbtn" id="costDetails">Lihat butiran kos →</button>`;
    el('costDetails').onclick=costModal;
    const sites=siteNames.map((name,i)=>{const r=scopedRows(year,selected,false,true).filter(r=>r.site===i);return {name,i,sales:total(r),cost:total(r,'cost')};}).sort((a,b)=>b.sales-a.sales);
    el('siteRanking').innerHTML=`<div class="cardhead"><div><h3>Prestasi business site</h3><small>Semua lokasi · Saluran dipilih</small></div></div>${sites.map((s,i)=>`<div class="rank"><span class="rank-index">0${i+1}</span><div class="rank-body"><div class="rank-title"><button class="textbtn" data-site="${s.i}">${s.name} →</button><b>${money(s.sales)}</b></div><div class="meter"><span style="--width:${s.sales/sites[0].sales*100}%;--color:${palette[i]}"></span></div><small>Kos ${money(s.cost)} · Untung ${money(s.sales-s.cost)}</small></div></div>`).join('')}`;
    el('siteRanking').querySelectorAll('button').forEach(b=>b.onclick=()=>{el('siteFilter').value=b.dataset.site;render();});
    const products=productNames.map((name,i)=>{const r=rows.filter(r=>r.product===i);return {name,i,sales:total(r),cost:total(r,'cost'),qty:total(r,'qty')};}).sort((a,b)=>(b.sales-b.cost)-(a.sales-a.cost));
    el('productRanking').innerHTML=`<div class="cardhead"><div><h3>Produk penyumbang untung</h3><small>Mengikut untung selepas kos contoh</small></div></div>${products.slice(0,4).map((p,i)=>`<div class="rank"><span class="rank-index">0${i+1}</span><div class="rank-body"><div class="rank-title"><button class="textbtn" data-product="${p.i}">${p.name} →</button><b>${money(p.sales-p.cost)}</b></div><div class="meter"><span style="--width:${(p.sales-p.cost)/(products[0].sales-products[0].cost)*100}%;--color:#2bb596"></span></div><small>${p.qty} unit · Jualan ${money(p.sales)}</small></div></div>`).join('')}`;
    el('productRanking').querySelectorAll('button').forEach(b=>b.onclick=()=>showTransactions(rows.filter(r=>r.product===Number(b.dataset.product)),productNames[Number(b.dataset.product)]));
    const latest=rows.slice(-4).reverse();
    el('transactionPreview').innerHTML=`<div class="cardhead"><div><h3>Jejak setiap transaksi.</h3><small>${rows.length} rekod dalam ${periodText()}</small></div></div>${latest.map((r,i)=>`<div class="rank"><span class="rank-index">↗</span><div class="rank-body"><div class="rank-title"><button class="textbtn" data-receipt="${i}">${productNames[r.product]}</button><b>${money(r.sales)}</b></div><small>${r.day} ${months[r.month]} · ${channelNames[r.channel]} · ${siteNames[r.site]}</small></div></div>`).join('')}<button class="textbtn" id="allTransactions">Lihat semua transaksi contoh →</button>`;
    el('allTransactions').onclick=()=>showTransactions(rows,`Transaksi · ${periodText()}`);
    el('transactionPreview').querySelectorAll('[data-receipt]').forEach(b=>b.onclick=()=>showTransactions([latest[Number(b.dataset.receipt)]],'Butiran transaksi'));
    document.querySelectorAll('#analysis .card,#analysis .channel-card').forEach(n=>{n.classList.remove('reveal');void n.offsetWidth;n.classList.add('reveal');});
}

const originalRender=render;
el('replay').removeEventListener('click',originalRender);
render=function(){year=Number(el('yearFilter').value);monthly=months.map((m,i)=>aggregate(scopedRows(year,i),m));originalRender();updatePanels();};
const originalTip=showTip;
showTip=function(d,i,event){originalTip(d,i,event);el('tooltip').querySelector('strong').textContent=selected===null?`${full[i]} ${year}`:`${d.label} ${full[selected]} ${year}`;};
['yearFilter','channelFilter','siteFilter','comparison'].forEach(id=>el(id).addEventListener('change',render));
let chartSelectedBefore=null;
el('chart').addEventListener('click',()=>{chartSelectedBefore=selected;},true);
el('chart').addEventListener('keydown',()=>{chartSelectedBefore=selected;},true);
function drillDay(event){if(chartSelectedBefore===null)return;if(event.type==='keydown'&&!['Enter',' '].includes(event.key))return;const node=event.target.closest('[data-index]');if(!node)return;const day=Number(node.dataset.index)+1;el('details').classList.remove('show');showTransactions(scopedRows().filter(r=>r.day===day),`${day} ${full[selected]} ${year}`);}
el('chart').addEventListener('click',drillDay);el('chart').addEventListener('keydown',drillDay);

const stock=productNames.flatMap((name,product)=>siteNames.map((site,i)=>({name,product,site:i,category:['Clicker','Aksesori','Hadiah','Hadiah','Aksesori','Aksesori'][product],available:product===5?0:product===1?4+i*2:35+product*17+i*13,reserved:product===5?0:3+i*2,cost:unitCosts[product],price:prices[product]})));
const stockAnimations=new Map();
function animateNumber(id,value,currency=true){if(stockAnimations.has(id))cancelAnimationFrame(stockAnimations.get(id));let start;function frame(t){start??=t;const k=reduced?1:Math.min(1,(t-start)/850);el(id).textContent=currency?money(value*(1-(1-k)**3)):Math.round(value*(1-(1-k)**3)).toLocaleString('en-MY');if(k<1)stockAnimations.set(id,requestAnimationFrame(frame));}stockAnimations.set(id,requestAnimationFrame(frame));}
function updateStock(){
    const rows=stock.filter(r=>(el('stockCategory').value==='all'||r.category===el('stockCategory').value)&&(el('stockSite').value==='all'||r.site===Number(el('stockSite').value))&&r.name.toLowerCase().includes(el('stockSearch').value.toLowerCase())&&(el('stockStatus').value==='all'||(el('stockStatus').value==='out'?r.available===0:r.available>0&&r.available<=10)));
    const available=total(rows,'available'),reserved=total(rows,'reserved'),asset=rows.reduce((s,r)=>s+r.available*r.cost,0),reservedAsset=rows.reduce((s,r)=>s+r.reserved*r.cost,0),potential=rows.reduce((s,r)=>s+r.available*r.price,0);
    el('stockMetrics').innerHTML=[['Stok tersedia','stockUnits',`${reserved} unit diperuntukkan`],['Aset tersedia pada kos','stockAsset',`Aset diperuntukkan: ${money(reservedAsset)}`],['Potensi nilai jualan','stockPotential','Pada harga jualan contoh'],['Potensi untung kasar','stockProfit','Belum menjadi keuntungan sebenar']].map(([label,id,caption])=>`<div class="stock-metric"><label>${label}</label><strong id="${id}">0</strong><small>${caption}</small></div>`).join('');
    animateNumber('stockUnits',available,false);animateNumber('stockAsset',asset);animateNumber('stockPotential',potential);animateNumber('stockProfit',potential-asset);
    el('stockRows').innerHTML=rows.length?rows.map((r,i)=>`<tr><td><button data-stock="${i}">${r.name} ↗</button><small>${r.category} · ${siteNames[r.site]}</small></td><td>${r.available}</td><td>${r.reserved}</td><td>${money(r.cost)}</td><td>${money(r.available*r.cost)}</td><td>${money(r.price)}</td><td style="color:#128566">${money(r.available*(r.price-r.cost))}</td><td><span class="badge ${r.available===0?'danger':r.available<=10?'warn':''}">${r.available===0?'Habis':r.available<=10?'Rendah':'Mencukupi'}</span></td></tr>`).join(''):'<tr><td colspan="8" class="empty">Tiada produk sepadan dengan penapis.</td></tr>';
    el('stockRows').querySelectorAll('button').forEach(b=>b.onclick=()=>{const r=rows[Number(b.dataset.stock)];openModal(r.name,`<p>${siteNames[r.site]} · ${r.category} · Stok semasa contoh</p><div class="compare-row"><div><small>Tersedia</small><b>${r.available} unit</b></div><div><small>Diperuntukkan</small><b>${r.reserved} unit</b></div></div><div class="cost-row"><span>Aset tersedia (${r.available} × ${money(r.cost)})</span><b>${money(r.available*r.cost)}</b></div><div class="cost-row"><span>Aset diperuntukkan</span><b>${money(r.reserved*r.cost)}</b></div><div class="cost-row"><span>Jumlah aset fizikal pada kos</span><b>${money((r.available+r.reserved)*r.cost)}</b></div><div class="cost-row"><span>Potensi jualan stok tersedia</span><b>${money(r.available*r.price)}</b></div><div class="cost-row"><span>Potensi untung kasar stok tersedia</span><b>${money(r.available*(r.price-r.cost))}</b></div><p style="font-size:12px;margin-top:20px">Potensi untung belum direalisasi; tidak termasuk diskaun, komisen atau kos operasi.</p>`);});
    const low=rows.filter(r=>r.available>0&&r.available<=10),out=rows.filter(r=>r.available===0);
    el('stockAlerts').innerHTML=`<h3>Perlu perhatian</h3><div class="notice">${low.length} rekod produk / lokasi mempunyai stok rendah (1–10 unit).</div><div class="notice danger">${out.length} rekod produk / lokasi kehabisan stok.</div><p class="stock-note">Jumlah aset fizikal dalam pilihan: <b>${money(asset+reservedAsset)}</b>, termasuk stok diperuntukkan.</p>`;
    const leaders=rows.slice().sort((a,b)=>b.available*b.cost-a.available*a.cost).slice(0,3);
    el('stockLeaders').innerHTML='<h3>Modal terbesar dalam stok</h3>'+leaders.map(r=>`<div class="cost-row"><span>${r.name} <small class="subtle">· ${siteNames[r.site]}</small></span><b>${money(r.available*r.cost)}</b></div><div class="meter"><span style="--width:${asset?r.available*r.cost/asset*100:0}%;--color:#688db5"></span></div>`).join('');
}
['stockCategory','stockSite','stockStatus'].forEach(id=>el(id).onchange=updateStock);el('stockSearch').addEventListener('input',updateStock);
el('replay').addEventListener('click',()=>{render();updateStock();el('targetRing').style.transition='none';el('targetRing').style.strokeDashoffset=314;requestAnimationFrame(()=>requestAnimationFrame(()=>{el('targetRing').style.transition='';updateTarget();}));});
el('export').onclick=()=>{const rows=scopedRows();const csv=[['Data contoh sahaja',periodText(),el('channelFilter').selectedOptions[0].text,el('siteFilter').selectedOptions[0].text],['Resit','Tarikh','Saluran','Lokasi','Produk','Unit','Jualan RM','Kos RM','Untung RM'],...rows.map(r=>[r.id,`${r.year}-${String(r.month+1).padStart(2,'0')}-${String(r.day).padStart(2,'0')}`,channelNames[r.channel],siteNames[r.site],productNames[r.product],r.qty,r.sales,r.cost,r.sales-r.cost])].map(row=>row.map(v=>'"'+String(v).replaceAll('"','""')+'"').join(',')).join('\r\n');const url=URL.createObjectURL(new Blob(['\ufeff'+csv],{type:'text/csv;charset=utf-8'}));const a=document.createElement('a');a.href=url;a.download=`anugerah3d-demo-${year}-${selected===null?'tahunan':selected+1}.csv`;a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);el('exportStatus').textContent=`${rows.length} transaksi dieksport`;};
render();updateStock();
