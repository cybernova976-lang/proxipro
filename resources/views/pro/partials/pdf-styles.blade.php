{{--
    Feuille de style commune aux PDF de devis et de factures (moteur DomPDF).
    DomPDF ne connaît ni flexbox ni grid : tout est en tableaux et en blocs.
    Police fournie avec DomPDF (DejaVu Sans), tailles en points.
    Palette : ardoise (0f172a, 334155, 64748b) et un seul accent, l'indigo 4338ca.
    Les commentaires sont des commentaires Blade : ils ne partent pas dans le PDF.
--}}
@@page { margin: 13mm 15mm 23mm 15mm; }
{{-- Pas de remise à zéro universelle : DomPDF l'appliquerait aussi à la page et effacerait ses marges. --}}
body, div, table, tr, td, th, span, img { margin: 0; padding: 0; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.35; color: #334155; }
table { width: 100%; border-collapse: collapse; border-spacing: 0; }
td, th { vertical-align: top; text-align: left; font-weight: normal; padding: 0; }
strong { color: #0f172a; }
.pk-num { text-align: right; white-space: nowrap; }
.pk-label { font-size: 7pt; font-weight: bold; letter-spacing: 1pt; text-transform: uppercase; color: #64748b; }

{{-- En-tête : identité de l'émetteur à gauche, titre et numéro à droite. --}}
.pk-head-seller { width: 56%; padding-right: 8mm; }
.pk-head-doc { width: 44%; text-align: right; }
.pk-logo { max-height: 48px; max-width: 200px; margin-bottom: 5pt; }
.pk-seller-name { font-size: 14pt; font-weight: bold; color: #0f172a; line-height: 1.2; margin-bottom: 4pt; }
.pk-seller-name-with-logo { font-size: 11.5pt; }
.pk-seller-info { font-size: 8pt; color: #64748b; line-height: 1.45; }
.pk-doc-title { font-size: 23pt; font-weight: bold; color: #4338ca; letter-spacing: 1.5pt; line-height: 1.1; white-space: nowrap; }
.pk-doc-title-long { font-size: 15pt; letter-spacing: 1pt; line-height: 1.3; }
.pk-doc-number { font-size: 10pt; font-weight: bold; color: #0f172a; margin-top: 4pt; white-space: nowrap; }
.pk-badge { display: inline-block; margin-top: 7pt; padding: 2pt 8pt; border: 0.75pt solid #cbd5e1; border-radius: 8pt; font-size: 7pt; font-weight: bold; letter-spacing: .6pt; text-transform: uppercase; color: #475569; }
.pk-badge-strong { border-color: #4338ca; background: #eef2ff; color: #4338ca; }
.pk-rule { border-top: 1.5pt solid #4338ca; margin: 4.5mm 0 4.5mm; height: 0; }

{{-- Bandeau : dates et références à gauche, client à droite. --}}
.pk-band-meta { width: 50%; padding-right: 8mm; }
.pk-band-client { width: 50%; }
.pk-kv td { padding: 1.3pt 0; font-size: 8.5pt; }
.pk-kv td.k { color: #64748b; white-space: nowrap; padding-right: 12pt; }
.pk-kv td.v { color: #0f172a; font-weight: bold; }
.pk-client { background: #f8fafc; border-left: 2.5pt solid #4338ca; padding: 9pt 12pt 10pt; }
.pk-client-name { font-size: 11pt; font-weight: bold; color: #0f172a; line-height: 1.25; margin-top: 3pt; }
.pk-client-info { font-size: 8.5pt; color: #475569; line-height: 1.45; margin-top: 2pt; }

{{-- Objet du document. --}}
.pk-subject { margin: 4mm 0 3mm; }
.pk-subject-text { font-size: 11pt; font-weight: bold; color: #0f172a; line-height: 1.3; margin-top: 2pt; }

{{-- Lignes : en-tête repris sur chaque page, nombres alignés à droite. --}}
.pk-lines th { background: #0f172a; color: #ffffff; font-size: 7pt; font-weight: bold; letter-spacing: .7pt; text-transform: uppercase; padding: 6pt 8pt; }
.pk-lines th.pk-num { text-align: right; }
.pk-lines td { padding: 5.5pt 8pt; border-bottom: 0.5pt solid #e2e8f0; font-size: 9pt; color: #334155; }
.pk-lines td.pk-line-total { color: #0f172a; font-weight: bold; }
.pk-lines tr { page-break-inside: avoid; }

{{-- Totaux : le montant à retenir est dans la bande indigo. --}}
.pk-summary { margin-top: 4mm; page-break-inside: avoid; }
.pk-summary-left { width: 50%; padding-right: 8mm; }
.pk-summary-right { width: 50%; }
.pk-totals td { padding: 4pt 10pt; font-size: 9pt; }
.pk-totals td.l { color: #64748b; }
.pk-totals td.a { width: 34%; text-align: right; white-space: nowrap; color: #0f172a; font-weight: bold; }
.pk-total-detail { font-size: 8pt; }
.pk-totals tr.pk-total-strong td { border-top: 0.75pt solid #cbd5e1; padding-top: 6pt; padding-bottom: 6pt; font-size: 10pt; font-weight: bold; color: #0f172a; }
.pk-totals tr.pk-total-grand td { background: #4338ca; color: #ffffff; font-size: 11.5pt; font-weight: bold; padding: 8pt 10pt; }
.pk-paid { border: 0.75pt solid #c7d2fe; background: #eef2ff; padding: 8pt 11pt; }
.pk-paid-text { font-size: 9pt; color: #312e81; font-weight: bold; margin-top: 2pt; }
.pk-paid-detail { font-size: 8.5pt; color: #475569; }
.pk-paid-below { margin-top: 5mm; margin-right: 50%; }

{{-- Conditions, mentions et notes. --}}
.pk-section { margin-top: 5mm; }
.pk-section-first { margin-top: 0; }
.pk-keep { page-break-inside: avoid; }
.pk-section-title { font-size: 7pt; font-weight: bold; letter-spacing: 1pt; text-transform: uppercase; color: #4338ca; border-bottom: 0.5pt solid #e2e8f0; padding-bottom: 3pt; margin-bottom: 5pt; page-break-after: avoid; }
.pk-section-text { font-size: 8.5pt; color: #475569; line-height: 1.5; }
.pk-pre { white-space: pre-line; }

{{-- Bon pour accord : cadre de signature, ou réponse donnée en ligne. --}}
.pk-accord { margin-top: 4mm; }
.pk-half-right { margin-left: 50%; }
.pk-sign-box { border: 0.75pt solid #cbd5e1; padding: 8pt 11pt; height: 21mm; }
.pk-sign-hint { font-size: 8pt; color: #64748b; line-height: 1.45; margin-top: 3pt; }
.pk-accord-text { font-size: 8.5pt; color: #334155; line-height: 1.45; margin-top: 3pt; }

{{-- Pied de page, repris sur chaque page, dans la marge basse. --}}
.pk-footer { position: fixed; left: 0; right: 0; bottom: -17mm; border-top: 0.5pt solid #e2e8f0; padding-top: 5pt; font-size: 7pt; line-height: 1.55; color: #94a3b8; }
.pk-footer-page { width: 16%; text-align: right; white-space: nowrap; }
.pk-page:after { content: counter(page); }
.pk-footer-free { color: #6366f1; }

{{-- Filigranes : un seul à la fois (brouillon, ou version gratuite). --}}
.pk-watermark { position: fixed; top: 124mm; left: -15mm; right: -15mm; text-align: center; white-space: nowrap; font-weight: bold; transform: rotate(-30deg); }
.pk-watermark-free { font-size: 26pt; letter-spacing: 2pt; color: #4338ca; opacity: .12; }
.pk-watermark-draft { font-size: 27pt; letter-spacing: 1pt; color: #dc2626; opacity: .16; }
