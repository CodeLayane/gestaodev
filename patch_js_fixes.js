// ============================================================
// PATCH assets/js/app.js — 3 correções
// ============================================================


// ────────────────────────────────────────────────────────────
// FIX 1: NOTIFICAÇÕES
// Problema: readNotif chama 'notification_read' mas o alias
// pode não estar mapeado corretamente.
// Substitua a função readNotif existente por esta:
// ────────────────────────────────────────────────────────────
async function readNotif(id, link) {
    // Marca como lida (tenta os dois nomes por segurança)
    try {
        await api('notification_read', { method: 'POST', params: { id } });
    } catch(e) {
        await fetch(`api.php?action=notification_read&id=${id}`, { method: 'POST' });
    }
    loadNotifCount();
    document.getElementById('notif-panel').classList.remove('show');
    if (link) {
        const [type, eid] = link.split(':');
        if (type === 'multi_demand') openMultiDemandFromNotif(parseInt(eid));
        else if (type === 'demand')       openDetail(parseInt(eid));
        else if (type === 'meeting')      showPage('reunioes');
        else if (type === 'solicitation') showPage('solicitacoes');
        else if (type === 'notice')       openNoticeView(parseInt(eid));
        else if (type === 'solicitation_approved' || type === 'solicitation_rejected') showPage('solicitacoes');
    }
}

// Substitua também readAllNotifs:
async function readAllNotifs() {
    await fetch('api.php?action=notificacoes_read_all', { method: 'POST' });
    loadNotifCount();
    document.getElementById('notif-panel').classList.remove('show');
}

// Fix: loadNotifCount deve atualizar o badge corretamente
async function loadNotifCount() {
    try {
        const r = await api('notifications_unread');
        const c = r?.c || 0;
        const el = document.getElementById('notif-count');
        const bn = document.getElementById('b-notif');
        if (c > 0) {
            if (el) { el.textContent = c; el.style.display = ''; }
            if (bn) { bn.textContent = c; bn.style.display = ''; }
        } else {
            if (el) el.style.display = 'none';
            if (bn) bn.style.display = 'none';
        }
        await checkSolicitacoesPendentes(false);
    } catch(e) {}
}


// ────────────────────────────────────────────────────────────
// FIX 2: RELATÓRIO DIÁRIO (semanal → hoje)
// Substitua a função loadRelatorios ou onde tiver
// a inicialização dos inputs de data do relatório diário.
// Busque por: rep-from  e  rep-to  dentro de loadReports
// e mude o padrão de -7 days para hoje:
// ────────────────────────────────────────────────────────────

// Cole esta função substituindo a inicialização dos filtros
// dentro de loadReports(), na linha que define dateFrom/dateTo:
// DE:
//   const dateFrom = document.getElementById('rep-from')?.value || new Date(Date.now()-90*86400000).toISOString().split('T')[0];
//   const dateTo   = document.getElementById('rep-to')?.value   || new Date().toISOString().split('T')[0];
// PARA:

// Padrão: últimos 30 dias (em vez de 90)
// const dateFrom = document.getElementById('rep-from')?.value || new Date(Date.now()-30*86400000).toISOString().split('T')[0];
// const dateTo   = document.getElementById('rep-to')?.value   || new Date().toISOString().split('T')[0];

// Para relatório DIÁRIO (hoje por padrão):
// const today = new Date().toISOString().split('T')[0];
// const dateFrom = document.getElementById('rep-from')?.value || today;
// const dateTo   = document.getElementById('rep-to')?.value   || today;


// ────────────────────────────────────────────────────────────
// FIX 3: SEÇÃO DE GARGALOS — cole dentro de loadReports()
// ANTES da linha: document.getElementById('rep-content').innerHTML=html;
// ────────────────────────────────────────────────────────────

// Buscar dados de gargalo junto com os outros relatórios
// Adicione 'bottleneck' no Promise.all de loadReports:
// const [genStats, byDev, bySys, timeline, productivity, sysHealth, bottleneck] = await Promise.all([
//   api('reports', {params:{...p, type:'general_stats'}}),
//   ...
//   api('bottleneck_report', {params:{date_from:dateFrom, date_to:dateTo}})
// ]);

// Depois, adicione o HTML do gargalo antes do document.getElementById('rep-content').innerHTML=html:

function buildGargaloHTML(bn) {
    if (!bn) return '';
    let h = '';

    // ── CABEÇALHO ──────────────────────────────────────────
    h += `<div class="tbl-c" style="margin-top:20px;border-left:4px solid #ef4444">
      <div class="tbl-bar" style="background:linear-gradient(90deg,rgba(239,68,68,.12),transparent)">
        <h3 style="color:#ef4444">🔍 Análise de Gargalos</h3>
        <span style="font-size:11px;color:var(--t3)">Onde o fluxo está travando</span>
      </div>
      <div style="padding:0 16px 16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-top:12px">`;

    // KPIs rápidos
    const stuck = bn.stuck_demands?.length || 0;
    const overloaded = (bn.overloaded_devs || []).filter(d => d.em_andamento > 3).length;
    const noPrazo = bn.no_deadline?.sem_prazo || 0;
    const totalAtivas = bn.no_deadline?.total_ativas || 1;
    const semPrazoPct = Math.round((noPrazo / totalAtivas) * 100);

    h += kpiCard('🧊 Demandas Paradas', stuck, stuck > 5 ? '#ef4444' : stuck > 2 ? '#f59e0b' : '#10b981', '+3 dias sem atualização');
    h += kpiCard('🔥 Devs Sobrecarregados', overloaded, overloaded > 1 ? '#ef4444' : '#10b981', 'mais de 3 demandas ativas');
    h += kpiCard('📅 Sem Prazo Definido', noPrazo, semPrazoPct > 30 ? '#f59e0b' : '#10b981', `${semPrazoPct}% das demandas ativas`);

    h += `</div></div>`;

    // ── DEMANDAS PARADAS ───────────────────────────────────
    if (bn.stuck_demands?.length) {
        h += `<div class="tbl-c" style="margin-top:14px">
          <div class="tbl-bar"><h3>🧊 Demandas Paradas (sem atualização há 3+ dias)</h3></div>
          <div style="overflow-x:auto"><table>
            <thead><tr>
              <th>Demanda</th><th>Status</th><th>Sistema</th>
              <th>Prioridade</th><th>Devs</th><th style="color:#ef4444">Dias parada</th>
            </tr></thead><tbody>`;
        bn.stuck_demands.forEach(d => {
            const urgColor = d.priority === 'Urgente' ? '#ef4444' : d.priority === 'Alta' ? '#f59e0b' : 'var(--t2)';
            const daysColor = d.days_stuck > 10 ? '#ef4444' : d.days_stuck > 5 ? '#f59e0b' : 'var(--t2)';
            h += `<tr onclick="openDetail(${d.id})" style="cursor:pointer">
              <td style="font-weight:600">${esc(d.title)}</td>
              <td><span class="badge s-${d.status.toLowerCase().replace(/ /g,'-')}">${esc(d.status)}</span></td>
              <td><span class="tag">${esc(d.system_name||'—')}</span></td>
              <td style="color:${urgColor};font-weight:600">${esc(d.priority)}</td>
              <td style="font-size:11px;color:var(--t3)">${esc(d.devs||'—')}</td>
              <td style="font-weight:700;color:${daysColor};font-family:'JetBrains Mono',monospace">${d.days_stuck}d</td>
            </tr>`;
        });
        h += `</tbody></table></div></div>`;
    }

    // ── FLUXO POR STATUS ───────────────────────────────────
    if (bn.status_flow?.length) {
        h += `<div class="tbl-c" style="margin-top:14px">
          <div class="tbl-bar"><h3>⏱ Tempo Médio por Status (onde as demandas ficam presas)</h3></div>
          <div style="padding:16px;display:flex;flex-wrap:wrap;gap:8px">`;
        const maxDays = Math.max(...bn.status_flow.map(s => parseFloat(s.avg_days) || 0), 1);
        bn.status_flow.forEach(s => {
            const pct = Math.round(((parseFloat(s.avg_days) || 0) / maxDays) * 100);
            const color = s.avg_days > 14 ? '#ef4444' : s.avg_days > 7 ? '#f59e0b' : '#10b981';
            h += `<div style="background:var(--bg3);border-radius:8px;padding:12px 16px;min-width:160px;flex:1;border-top:3px solid ${color}">
              <div style="font-size:11px;color:var(--t3);margin-bottom:4px">${esc(s.status)}</div>
              <div style="font-size:22px;font-weight:800;color:${color}">${s.avg_days || '—'}d</div>
              <div style="margin-top:6px;height:4px;background:var(--bdr);border-radius:2px">
                <div style="width:${pct}%;height:100%;background:${color};border-radius:2px"></div>
              </div>
              <div style="font-size:10px;color:var(--t3);margin-top:4px">${s.total} demandas · máx ${s.max_days}d</div>
            </div>`;
        });
        h += `</div></div>`;
    }

    // ── DEVS SOBRECARREGADOS ───────────────────────────────
    if (bn.overloaded_devs?.length) {
        h += `<div class="tbl-c" style="margin-top:14px">
          <div class="tbl-bar"><h3>🔥 Carga por Desenvolvedor</h3></div>
          <div style="overflow-x:auto"><table>
            <thead><tr><th>Desenvolvedor</th><th>Em Andamento</th><th>Urgentes</th><th>Atrasadas</th><th>Carga</th></tr></thead>
            <tbody>`;
        bn.overloaded_devs.forEach(d => {
            const cargaColor = d.em_andamento > 4 ? '#ef4444' : d.em_andamento > 2 ? '#f59e0b' : '#10b981';
            const cargaLabel = d.em_andamento > 4 ? '🔴 Sobrecarregado' : d.em_andamento > 2 ? '🟡 Moderado' : '🟢 OK';
            h += `<tr>
              <td><div class="dev-tag">${av(d.name, d.avatar_color, 22)} ${esc(d.name)}</div></td>
              <td style="font-weight:700;color:${cargaColor}">${d.em_andamento}</td>
              <td style="color:#ef4444">${d.urgentes || 0}</td>
              <td style="color:#f59e0b">${d.atrasadas || 0}</td>
              <td><span style="font-size:11px;font-weight:600;color:${cargaColor}">${cargaLabel}</span></td>
            </tr>`;
        });
        h += `</tbody></table></div></div>`;
    }

    // ── SISTEMAS COM MAIS URGÊNCIAS ────────────────────────
    if (bn.sys_bottleneck?.length) {
        h += `<div class="tbl-c" style="margin-top:14px">
          <div class="tbl-bar"><h3>💥 Sistemas com Maior Pressão</h3></div>
          <div style="overflow-x:auto"><table>
            <thead><tr><th>Sistema</th><th>Abertas</th><th>Urgentes</th><th>Altas</th><th>Idade Média</th></tr></thead>
            <tbody>`;
        bn.sys_bottleneck.forEach(s => {
            const pressColor = s.urgentes > 3 ? '#ef4444' : s.urgentes > 1 ? '#f59e0b' : 'var(--t2)';
            h += `<tr>
              <td style="font-weight:600">${esc(s.name)}</td>
              <td style="font-weight:700">${s.total_open}</td>
              <td style="color:#ef4444;font-weight:700">${s.urgentes}</td>
              <td style="color:#f59e0b">${s.altas}</td>
              <td style="color:${pressColor};font-family:'JetBrains Mono',monospace">${s.avg_age_days}d</td>
            </tr>`;
        });
        h += `</tbody></table></div></div>`;
    }

    return h;
}

function kpiCard(label, value, color, sub) {
    return `<div style="background:var(--bg3);border-radius:10px;padding:14px;border-left:3px solid ${color}">
      <div style="font-size:11px;color:var(--t3);margin-bottom:4px">${label}</div>
      <div style="font-size:28px;font-weight:800;color:${color}">${value}</div>
      <div style="font-size:10px;color:var(--t3);margin-top:2px">${sub}</div>
    </div>`;
}

// ────────────────────────────────────────────────────────────
// INSTRUÇÕES DE APLICAÇÃO:
//
// 1. No loadReports(), adicione ao Promise.all:
//    api('bottleneck_report', {params:{date_from:dateFrom, date_to:dateTo}})
//    e capture como variável `bottleneck`
//
// 2. Antes da linha: document.getElementById('rep-content').innerHTML=html;
//    adicione: html += buildGargaloHTML(bottleneck);
//
// 3. Para mudar o relatório de semanal para diário, no cabeçalho
//    da seção de relatórios diários, busque "semanal" e troque por "diário"
//    (pode estar em index.php ou app.js)
// ────────────────────────────────────────────────────────────
