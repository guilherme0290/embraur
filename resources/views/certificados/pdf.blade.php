{{-- resources/views/certificados/modelo-premium.blade.php --}}
@php
    // =======================
    // Dados dinâmicos (fallbacks)
    // =======================
    $alunoNome      = $alunoNome      ?? ($aluno->name ?? $aluno->nome ?? 'Nome do Aluno');
    $cursoTitulo    = $cursoTitulo    ?? ($curso->titulo ?? 'Título do Curso');
    $cargaHoraria   = $cargaHoraria   ?? ($curso->carga_horaria ?? null);
    $dataEmissao    = $dataEmissao    ?? (isset($certificado->data_emissao) ? \Carbon\Carbon::parse($certificado->data_emissao) : now());
    $codigo         = $codigo         ?? ($certificado->codigo_verificacao ?? 'ABC-123-XYZ');
    $assinatura1    = $assinatura1    ?? ['nome'=>'Juliana Silva','cargo'=>'Coordenadora'];
    $assinatura2    = $assinatura2    ?? ['nome'=>'Murad Nasser','cargo'=>'Instrutor'];
    $qrCodePath     = $qrCodePath     ?? null; // ex.: storage_path('app/public/certificados/qr-ABC-123-XYZ.png')

    // Títulos do topo (ajuste se quiser manter "CERTIFICATE/MOCKUP")
    $tituloTopo1    = $tituloTopo1    ?? 'CERTIFICADO';
    $tituloTopo2    = $tituloTopo2    ?? 'EMISSÃO';

    // =======================
    // Paleta BRAND (oliva)
    // =======================
    $brand = [
      50=>'#f5f7f2',100=>'#e9eee3',200=>'#d5dcc9',300=>'#c1cab0',400=>'#aab798',
      500=>'#889875',600=>'#778663',700=>'#606d50',800=>'#4b5440',900=>'#3b4333',
    ];

    // Mapeamento do modelo (antigo navy/gold/sand -> brand)
    $navy = $brand[900];   // moldura e títulos
    $gold = $brand[500];   // destaques (nome do aluno / detalhes)
    $sand = $brand[200];   // pincel/curvas claras
@endphp
    <!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado</title>
    <style>
        @page { size: A4 landscape; margin: 18mm; }
        * { box-sizing: border-box; }
        html, body { font-family: "Montserrat", "Helvetica Neue", Arial, sans-serif; color: #1f2937; }
        .wrap { position: relative; width: 100%; height: 100%; }
        .frame {
            position: relative;
            width: 100%;
            height: 100%;
            border: 14px solid {{ $navy }};
            border-radius: 10px;
            padding: 28mm 24mm;
            background: #fff;
            overflow: hidden;
        }

        /* faixas curvas em tons da paleta (compatível com dompdf) */
        .curve-top, .curve-bottom {
            position: absolute; left: -30mm; right: -30mm; height: 34mm; z-index: 0;
            background: linear-gradient(90deg, transparent 0%, {{ $sand }} 18%, {{ $gold }} 55%, {{ $sand }} 85%, transparent 100%);
            opacity: .5;
        }
        .curve-top    { top: -8mm; transform: rotate(-4deg); border-top-left-radius: 80mm; border-top-right-radius: 80mm; }
        .curve-bottom { bottom: -8mm; transform: rotate(4deg);  border-bottom-left-radius: 80mm; border-bottom-right-radius: 80mm; }

        .header { position: relative; z-index: 1; text-align: center; margin-bottom: 10mm; }
        .badge {
            display: inline-block; margin-bottom: 4mm;
            width: 16mm; height: 16mm; border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, {{ $brand[100] }}, {{ $brand[500] }} 60%, {{ $brand[700] }} 100%);
            box-shadow: 0 0 0 3px rgba(0,0,0,.04) inset;
        }
        .title {
            letter-spacing: .08em; color: {{ $navy }};
            font-family: "Playfair Display", "Georgia", serif;
            font-size: 28pt; font-weight: 800;
            margin: 2mm 0 0;
        }
        .subtitle {
            color: #64748b; letter-spacing: .28em; font-size: 9pt; margin-top: .5mm;
        }

        .awarded {
            text-align: center; margin: 10mm 0 6mm; z-index: 1; position: relative;
        }
        .awarded .label { color: #111827; font-size: 11pt; letter-spacing: .12em; }
        .aluno {
            margin-top: 3mm;
            font-size: 28pt; font-weight: 700; color: {{ $gold }};
            font-family: "Great Vibes", "Brush Script MT", cursive;
            line-height: 1.1;
            border-bottom: 2px solid {{ $brand[400] }};
            display: inline-block; padding: 0 2mm 1mm;
        }

        .body {
            position: relative; z-index: 1; text-align: center; margin-top: 6mm; padding: 0 8mm;
            font-size: 11pt; color: #4b5563; line-height: 1.6;
        }
        .curso { color: #111827; font-weight: 700; }
        .muted { color: #6b7280; }

        .footer {
            position: absolute; left: 24mm; right: 24mm; bottom: 24mm; z-index: 1;
            display: grid; grid-template-columns: 1fr auto 1fr; align-items: end; gap: 10mm;
        }
        .sign { text-align: center; }
        .sign .line { border-top: 1.5px solid #cbd5e1; width: 100%; margin-bottom: 2mm; }
        .sign .name { font-weight: 700; font-size: 10pt; color: #111827; }
        .sign .role { font-size: 9pt; color: #64748b; }

        .verify { text-align: right; font-size: 9pt; color: #334155; }
        .verify .code { font-family: "Courier New", monospace; font-weight: 700; color: {{ $navy }}; }
        .qr { width: 28mm; height: 28mm; border: 1px solid #e5e7eb; padding: 2mm; border-radius: 4px; background: #fff; }
        .qr img { width: 100%; height: 100%; object-fit: contain; }

        /* fallbacks de fontes (dompdf) */
        @font-face { font-family: "Playfair Display"; src: local("Playfair Display"), local("PlayfairDisplay"); }
        @font-face { font-family: "Great Vibes"; src: local("Great Vibes"), local("GreatVibes-Regular"); }
    </style>
</head>
<body>
<div class="wrap">
    <div class="frame">
        <div class="curve-top"></div>
        <div class="curve-bottom"></div>

        <div class="header">
            <div class="badge"></div>
            <div class="title">{{ $tituloTopo1 }}</div>
            <div class="subtitle">{{ $tituloTopo2 }}</div>
        </div>

        <div class="awarded">
            <div class="label">ESTE CERTIFICADO É CONCEDIDO A</div>
            <div class="aluno">{{ $alunoNome }}</div>
        </div>

        <div class="body">
            <p>
                Concluiu com aproveitamento o curso <span class="curso">“{{ $cursoTitulo }}”</span>
                @if($cargaHoraria) com carga horária de <strong>{{ $cargaHoraria }}</strong> horas @endif.
            </p>
            <p class="muted">
                Emitido em {{ $dataEmissao instanceof \Carbon\Carbon ? $dataEmissao->format('d/m/Y') : $dataEmissao }}
            </p>
        </div>

        <div class="footer">
            <div class="sign">
                <div class="line"></div>
                <div class="name">{{ $assinatura1['nome'] ?? '' }}</div>
                <div class="role">{{ $assinatura1['cargo'] ?? '' }}</div>
            </div>

            <div class="verify">
                @if($qrCodePath && file_exists($qrCodePath))
                    <div class="qr"><img src="{{ $qrCodePath }}"></div>
                @endif
                <div style="margin-top:3mm">
                    Código: <span class="code">{{ $codigo }}</span><br>
                    Verifique no site do <span class="muted">Instituto da Indústria</span>
                </div>
            </div>

            <div class="sign">
                <div class="line"></div>
                <div class="name">{{ $assinatura2['nome'] ?? '' }}</div>
                <div class="role">{{ $assinatura2['cargo'] ?? '' }}</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
