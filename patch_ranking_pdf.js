// ============================================================
// RANKING — Quem mais fez demandas no período
// Cole este bloco ANTES da linha: doc.save('Relatorio_ASSEGO_TI_'...
// Arquivo: assets/js/app.js — função exportReportPDF()
// ============================================================

{
  // Pegar dados de devs ordenados por concluídas DESC
  const rankData = (rd.byDev || [])
    .filter(d => (d.total || 0) > 0)
    .sort((a, b) => (b.concluidas || 0) - (a.concluidas || 0))
    .slice(0, 10); // top 10

  if (rankData.length > 0) {
    y += 18;
    if (y + 60 > ph - 16) { newPg(); }

    const tblW = pw - mg * 2;

    // ── Título da seção ──────────────────────────────────────
    doc.setTextColor(245, 190, 30); // dourado
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('🏆 RANKING DE PRODUTIVIDADE — TOP DESENVOLVEDORES', mg, y);
    y += 3;

    // Linha dourada decorativa
    doc.setDrawColor(245, 190, 30);
    doc.setLineWidth(0.6);
    doc.line(mg, y, pw - mg, y);
    y += 6;

    // ── Cabeçalho da tabela ──────────────────────────────────
    const cols  = ['#', 'Desenvolvedor', 'Concluídas', 'Total', 'Em Aberto', 'Tempo Médio', 'Taxa'];
    const widths = [10, 68, 28, 22, 24, 30, 16];

    doc.setFillColor(20, 40, 90);
    doc.roundedRect(mg, y, tblW, 7, 1, 1, 'F');
    doc.setTextColor(220, 225, 240);
    doc.setFontSize(7.5);
    doc.setFont('helvetica', 'bold');

    cols.forEach((h, i) => {
      let cx = mg;
      for (let j = 0; j < i; j++) cx += widths[j];
      doc.text(h, cx + 2.5, y + 4.8);
    });
    y += 8;

    // ── Linhas do ranking ────────────────────────────────────
    const medals = ['🥇', '🥈', '🥉'];
    const podiumBg = [
      [255, 215, 0, 25],  // ouro
      [192, 192, 192, 25],// prata
      [205, 127, 50, 25], // bronze
    ];

    doc.setFont('helvetica', 'normal');

    rankData.forEach((d, ri) => {
      safeY(7);

      // Fundo diferenciado para pódio
      if (ri < 3) {
        const [r, g, b] = podiumBg[ri];
        doc.setFillColor(r, g, b);
        doc.roundedRect(mg, y, tblW, 6.5, 0.8, 0.8, 'F');
      } else if (ri % 2 === 0) {
        doc.setFillColor(240, 242, 248);
        doc.rect(mg, y, tblW, 6, 'F');
      }

      const pos      = ri + 1;
      const posLabel = pos <= 3 ? medals[ri] : String(pos) + 'º';
      const conc     = d.concluidas  || 0;
      const total    = d.total       || 0;
      const aberto   = total - conc - (d.canceladas || 0);
      const taxa     = total > 0 ? Math.round((conc / total) * 100) + '%' : '—';

      // Tempo médio formatado
      let avgTxt = '—';
      if (d.avg_days) {
        const h2 = parseFloat(d.avg_days) * 24;
        avgTxt = h2 < 1
          ? Math.round(h2 * 60) + 'min'
          : h2 < 24
            ? h2.toFixed(1) + 'h'
            : parseFloat(d.avg_days).toFixed(1) + 'd';
      }

      const vals = [posLabel, d.name || '—', String(conc), String(total), String(Math.max(0, aberto)), avgTxt, taxa];

      // Cor especial para pódio
      doc.setTextColor(ri < 3 ? 30 : 40, ri < 3 ? 30 : 50, ri < 3 ? 30 : 70);
      if (ri < 3) doc.setFont('helvetica', 'bold');
      else        doc.setFont('helvetica', 'normal');

      doc.setFontSize(7.5);
      vals.forEach((v, i) => {
        let cx = mg;
        for (let j = 0; j < i; j++) cx += widths[j];
        // Coluna "Concluídas" em destaque verde
        if (i === 2) doc.setTextColor(16, 185, 129);
        else         doc.setTextColor(ri < 3 ? 30 : 40, ri < 3 ? 30 : 50, ri < 3 ? 30 : 70);
        doc.text(String(v), cx + 2.5, y + 4.3, { maxWidth: widths[i] - 3 });
      });

      doc.setDrawColor(210, 215, 225);
      doc.setLineWidth(0.15);
      doc.line(mg, y + 6.5, pw - mg, y + 6.5);
      y += 6.5;
    });

    // ── Nota de rodapé da tabela ─────────────────────────────
    y += 4;
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(6.5);
    doc.setTextColor(140, 150, 170);
    doc.text(
      '* Ranking baseado em demandas concluídas no período selecionado. Pódio destaca os 3 melhores.',
      mg, y
    );
    y += 8;
  }
}
// ============================================================
// FIM DO BLOCO — após este fecha a chave e vem o doc.save()
// ============================================================
