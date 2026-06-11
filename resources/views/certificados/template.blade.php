{{-- resources/views/certificados/modelo-bg-embraur.blade.php --}}
@php
    // ===================== Dados dinâmicos (fallbacks) =====================
    $alunoNome      = $alunoNome      ?? ($aluno->name ?? $aluno->nome ?? 'Nome do Aluno');
    $cursoTitulo    = $cursoTitulo    ?? ($curso->titulo ?? 'Título do Curso');
    $cargaHoraria   = $cargaHoraria   ?? ($curso->carga_horaria ?? null);
    $dataEmissao    = $dataEmissao    ?? (isset($certificado->data_emissao) ? \Carbon\Carbon::parse($certificado->data_emissao) : now());
    $codigo         = $codigo         ?? ($certificado->codigo_verificacao ?? 'ABC-123-XYZ');

    // Imagens como data-URI (evita isRemoteEnabled no Dompdf)
    // Passe $bgDataUri já pronto pelo controller (de um JPG/PNG A4 landscape do seu template)
    // Ex.: $bgDataUri = 'data:image/jpeg;base64,'.base64_encode(file_get_contents($path));
    $bgDataUri          = $bgDataUri          ?? null;
    $assinaturaImgData  = $assinaturaImgData  ?? null; // opcional: assinatura do responsável (PNG transparente)

    // Paleta brand (oliva) – use se quiser harmonizar textos
    $brand = [
      50=>'#f5f7f2',100=>'#e9eee3',200=>'#d5dcc9',300=>'#c1cab0',400=>'#aab798',
      500=>'#889875',600=>'#778663',700=>'#606d50',800=>'#4b5440',900=>'#3b4333',
    ];

    // Auto-ajuste simples do tamanho do nome em função do comprimento
    $nameLen  = mb_strlen($alunoNome);
    $nameSize = $nameLen > 40 ? 18 : ($nameLen > 32 ? 22 : ($nameLen > 24 ? 26 : 30)); // pt

    // ===================== POSIÇÕES (mm) – ajuste fino se precisar =====================
    // A4 landscape: 297 (largura) x 210 (altura)
    // Nome (sobre a linha pontilhada ao centro)
    $nomeTop = 83;     // ↑ suba/abaixe 1–3 mm se necessário
    // Texto descritivo (conclusão do curso)
    $textoTop = 108;   // posição central abaixo do nome
    // Data (alinha com a área "Data" no canto inferior esquerdo do seu template)
    $dataTop = 168;    // suba/abaixe conforme o seu fundo
    $dataLeft = 38;    // deslocamento horizontal a partir da margem esquerda
    // Assinatura (lado direito)
    $signTop = 152;    // base da assinatura (imagem) / linha
    $signBoxWidth = 70;  // largura da caixa de assinatura
    $signRight = 38;     // distância da margem direita
    // Nome/cargo do signatário (abaixo da linha)
    $signLabelTop = 170;

    // Código de verificação (centralizado embaixo, perto do louro)
    $codigoTop = 186;
@endphp

    <!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado</title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        html, body { margin: 0; padding: 0; }
        * { box-sizing: border-box; }

        .page { position: relative; width: 297mm; height: 210mm; overflow: hidden; }
        .bg   { position: absolute; inset: 0; width: 297mm; height: 210mm; z-index: 0; }
        .layer{ position: relative; width: 297mm; height: 210mm; z-index: 1;
            font-family: "Montserrat", Arial, sans-serif; color: #111827; }

        .abs  { position: absolute; }

        .nome-aluno {
            left: 20mm; right: 20mm; text-align: center;
            color: {{ $brand[600] }}; /* oliva */
            font-weight: 700; letter-spacing: .02em;
            line-height: 1.1;
        }

        .texto {
            left: 30mm; right: 30mm; text-align: center;
            font-size: 11pt; color: #4b5563; line-height: 1.6;
        }
        .texto .curso { color: #111827; font-weight: 700; }

        .data {
            left: {{ $dataLeft }}mm; text-align: left;
            font-size: 10pt; color: #374151;
        }

        .assinatura {
            right: {{ $signRight }}mm; width: {{ $signBoxWidth }}mm; text-align: center;
        }
        .assinatura .img { width: {{ $signBoxWidth }}mm; height: 16mm; object-fit: contain; }
        .assinatura .linha { border-top: .4mm solid #cbd5e1; margin-top: 4mm; }
        .assinatura .nome { font-weight: 700; font-size: 10pt; color: #111827; margin-top: 2mm; }
        .assinatura .cargo { font-size: 9pt; color: #64748b; }

        .codigo {
            left: 0; right: 0; text-align: center;
            font-size: 9pt; color: #334155;
        }
        .codigo .val { font-family: "Courier New", monospace; font-weight: 700; color: {{ $brand[900] }}; }
    </style>
</head>
<body>
<div class="page">
    {{-- Fundo do certificado --}}
    @if($bgDataUri)
        <img class="bg" src="{{ $bgDataUri }}" alt="Fundo">
    @endif

    <div class="layer">
        {{-- Nome do aluno (em cima da linha pontilhada) --}}
        <div class="abs nome-aluno" style="top: {{ $nomeTop }}mm; font-size: {{ $nameSize }}pt;">
            {{ $alunoNome }}
        </div>

        {{-- Texto de conclusão + curso + horas --}}
        <div class="abs texto" style="top: {{ $textoTop }}mm;">
            Este certificado é apresentado a {{ $alunoNome }}, por ter concluído com aproveitamento o curso
            <span class="curso">“{{ $cursoTitulo }}”</span>
            @if($cargaHoraria) , com carga horária de <strong>{{ $cargaHoraria }}</strong> horas @endif.
        </div>

        {{-- Data (área inferior esquerda) --}}
        <div class="abs data" style="top: {{ $dataTop }}mm;">
            {{ $dataEmissao instanceof \Carbon\Carbon ? $dataEmissao->format('d/m/Y') : $dataEmissao }}
        </div>

        {{-- Assinatura (inferior direita) --}}
        <div class="abs assinatura" style="top: {{ $signTop }}mm;">
            @if($assinaturaImgData)
                <img class="img" src="{{ $assinaturaImgData }}" alt="Assinatura">
            @endif
            <div class="linha"></div>
            <div class="nome">{{ $assinatura1['nome'] ?? 'Responsável' }}</div>
            <div class="cargo">{{ $assinatura1['cargo'] ?? 'Cargo / Conselho' }}</div>
        </div>

        {{-- Código de verificação (central inferior, perto do louro) --}}
        <div class="abs codigo" style="top: {{ $codigoTop }}mm;">
            Código: <span class="val">{{ $codigo }}</span> • Verifique no site do <span style="color:#6b7280">Instituto da Indústria</span>
        </div>
    </div>
</div>
</body>
</html>
