<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\Certificados;
use App\Models\Cursos;
use App\Models\Matriculas;
use App\Models\User;
use App\Services\CourseCompletionService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use App\Models\QuizTentativa;


class StudentCertificatesController extends Controller
{

    private function assertOwnership(Request $request, Certificados $cert): void
    {
        $alunoId = auth('aluno')->id() ?? $request->session()->get('aluno_id');


        abort_if(!$alunoId || (int)$cert->matricula->aluno_id !== (int)$alunoId, 403);
    }


    public function index(Request $request)
    {
        $alunoId = auth('aluno')->id() ?? $request->session()->get('aluno_id');
        abort_if(!$alunoId, 403);

        $certs = Certificados::query()
            ->whereHas('matricula', fn($q)=>$q->where('aluno_id',$alunoId))
            ->with(['matricula.curso'])
            ->orderByDesc('data_emissao')
            ->get();

        return view('aluno.certificados', [
            'aluno' => $request->user('aluno'),
            'certificados' => $certs,
        ]);
    }

    public function verify(Request $request, string $codigo)
    {
        $cert = Certificados::where('codigo_verificacao', $codigo)
            ->with(['matricula.curso', 'matricula.aluno'])
            ->firstOrFail();

        return view('site.certificado-verificar', compact('cert'));
    }


    private function findCertTemplatePath(string $relative): ?string
    {
        $candidates = [
            public_path($relative),                     // ex.: public/certificados/template/template.jpg
            public_path('storage/'.$relative),         // ex.: public/storage/certificados/template/template.jpg (requer storage:link)
            storage_path('app/public/'.$relative),     // ex.: storage/app/public/certificados/template/template.jpg
            base_path('public/'.$relative),
        ];

        foreach ($candidates as $p) {
            if (is_file($p) && is_readable($p)) return $p;
        }
        return null;
    }

    private function formatCargaHorariaHoras(Cursos $curso): string
    {
        $horas = ((int) ($curso->carga_horaria_total ?? 0)) / 60;

        if ($horas <= 0) {
            return '';
        }

        return rtrim(rtrim(number_format($horas, 2, ',', ''), '0'), ',');
    }

    public function baixarFPDF(Cursos $curso, Request $request)
    {
        $alunoId   = auth('aluno')->id() ?? $request->session()->get('aluno_id');
        $matricula = Matriculas::where('aluno_id', $alunoId)
            ->where('curso_id', $curso->id)->firstOrFail();

        $cert = $matricula->certificado->firstOrFail();

        $bytes = $this->renderCertificadoFPDF($curso, $matricula, $cert);

        return response($bytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="certificado-preview.pdf"', // inline p/ visualizar
        ]);
    }

    /**
     * Certificado 2 páginas (frente com fundo; verso desenhado).
     * Ajustes-chave:
     * - Sem page break automático (evita 3ª/4ª páginas).
     * - Quadro de CARGA/NOTA/REGISTRO vai DENTRO da moldura da lista, no topo direito.
     * - Colunas independentes (direita começa abaixo do quadro).
     * - Assinaturas posicionadas para caber.
     */
    private function renderCertificadoFPDF(Cursos $curso, Matriculas $matricula, Certificados $cert): string
    {
        // --- Caminhos (ajuste conforme seus arquivos) ---
        $frontRel          = 'certificados/template/template2.jpeg'; // fundo da página 1
        $logoRel           = 'images/logo2Embraur.png';              // logo (pág. 1 e 2)
        $assinInstrutorRel = 'images/assinatura_helder.jpeg';
        // $assinAlunoRel   = (REMOVIDO: aluno assina manualmente no espaço)
        $assinTec1Rel      = 'images/assinatura_helder.jpeg';
        $assinTec2Rel      = 'images/assinatura_zunei.jpeg';

        // --- Helpers ---
        $toPdf = fn(?string $s) => iconv('UTF-8','Windows-1252//TRANSLIT', $s ?? '');
        $maskCpf = function (?string $cpf) {
            $d = preg_replace('/\D/', '', (string)$cpf);
            return strlen($d) === 11 ? substr($d,0,3).'.'.substr($d,3,3).'.'.substr($d,6,3).'-'.substr($d,9,2) : $cpf;
        };
        $placeImage = function(\FPDF $pdf, ?string $relPath, float $x, float $y, float $w, float $h, string $phLabel='') {
            // Desenha imagem proporcional; se não existir, desenha uma caixa placeholder.
            $abs = $relPath ? $this->findCertTemplatePath($relPath) : null;
            if ($abs && is_file($abs)) {
                [$iw,$ih] = @getimagesize($abs) ?: [0,0];
                if ($iw && $ih) {
                    $type = strtolower(pathinfo($abs, PATHINFO_EXTENSION)) === 'png' ? 'PNG' : 'JPG';
                    $ratio = min($w/$iw, $h/$ih); $dw=$iw*$ratio; $dh=$ih*$ratio;
                    $pdf->Image($abs, $x+($w-$dw)/2, $y+($h-$dh)/2, $dw, $dh, $type);
                    return true;
                }
            }
            $pdf->SetDrawColor(180,180,180); $pdf->Rect($x,$y,$w,$h);
            if ($phLabel) { $pdf->SetFont('Arial','',9); $pdf->SetTextColor(120,120,120); $pdf->SetXY($x,$y+($h/2)-3); $pdf->Cell($w,6,$phLabel,0,0,'C'); }
            return false;
        };
        $cargaHorariaHoras = $this->formatCargaHorariaHoras($curso);

        // ========= PÁGINA 1 =========
        $front = $this->findCertTemplatePath($frontRel);
        if (!$front) throw new \RuntimeException("Template da frente não encontrado em public/$frontRel (ou storage/app/public/...).");

        $pdf = new \FPDF('L','mm','A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false); // não cria páginas extras

        // Fundo
        $type = strtolower(pathinfo($front, PATHINFO_EXTENSION)) === 'png' ? 'PNG' : 'JPG';
        $pdf->Image($front, 0, 0, 297, 210, $type);

        // LOGO topo (maior). Caixa ~ 90 x 32 mm
        $placeImage($pdf, $logoRel, 103.5, 16, 95, 32, 'LOGO');

        // Caixa "CERTIFICADO" (acima do nome)
        $pdf->SetDrawColor(0,0,0);
        //$pdf->Rect(123, 52, 60, 10);
        $pdf->SetFont('Arial','B',25);
        $pdf->SetXY(123, 52);
        $pdf->Cell(60, 10, $toPdf('CERTIFICADO'), 0, 0, 'C');

        // Nome (sublinhado)
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','BU',28);
        $pdf->SetXY(20, 78);
        $pdf->Cell(257, 12, $toPdf(strtoupper($matricula->aluno->nome_completo) ?? ''), 0, 1, 'C');

        // CPF (mascarado)
        if (!empty($matricula->aluno->cpf)) {
            $pdf->SetFont('Arial','',12);
            $pdf->SetXY(20, 94);
            $pdf->Cell(257, 6, $toPdf('CPF '.$maskCpf($matricula->aluno->cpf)), 0, 1, 'C');
        }

        // Texto do curso (com NOME DO CURSO em negrito, centralizado em 2 linhas)
        $pdf->SetFont('Arial','',15);
        $pdf->SetXY(25, 105);
        $pdf->Cell(247, 6, $toPdf('Certificamos que o aluno concluiu com aproveitamento o curso de'), 0, 2, 'C');
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(247, 6, $toPdf($curso->titulo), 0, 2, 'C');
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(247, 6, $toPdf('com carga horária de '.$cargaHorariaHoras.' horas realizado no período:'), 0, 2, 'C');

        // Período (APENAS a linha "De XX a YY" dentro da caixa)
        $fmt = fn($d) => $d ? $d->format('d/m/Y') : '—';

        $inicio = $matricula->data_inicio ?? $cert->data_emissao ?? $matricula->created_at ?? now();
        $fim    = $matricula->data_fim    ?? $cert->data_emissao ?? $inicio;

        //Garante que o início seja pelo menos 4 dias antes do fim
        if ($fim instanceof \Carbon\Carbon && $inicio instanceof \Carbon\Carbon) {
            if ($fim->diffInDays($inicio, false) < 4) {
                $inicio = $fim->copy()->subDays(4);
            }
        }

        $pdf->SetDrawColor(120,120,120);
        $pdf->Rect(98, 123, 101, 10);
        $pdf->SetFont('Arial','',11);
        $pdf->SetXY(98, 123);
        $pdf->Cell(101, 10, $toPdf('De '.$fmt($inicio).' a '.$fmt($fim)), 0, 0, 'C');

        // Assinaturas: instrutor (com imagem) e aluno (SÓ espaço/linha)
        $assinYImg = 140;
        $assinHImg = 30;
        $linhaY = 170;
        $wAssin = 70;
        $xEsq = 40;
        $xDir = 300 - 30 - $wAssin;

        // Instrutor
        $placeImage($pdf, $assinInstrutorRel, $xEsq+15, $assinYImg, 50, $assinHImg, '');
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetXY($xEsq, $linhaY); $pdf->Cell($wAssin, 0, '', 'T');
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY($xEsq, $linhaY+2); $pdf->Cell($wAssin, 5, $toPdf('Instrutor'), 0, 0, 'C');


        // Aluno (sem imagem pré-definida)
        $pdf->SetXY($xDir, $linhaY); $pdf->Cell($wAssin, 0, '', 'T');
        $pdf->SetXY($xDir, $linhaY+2); $pdf->Cell($wAssin, 5, $toPdf('Aluno(a)'), 0, 0, 'C');


        // ========= PÁGINA 2 =========
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);

        // Logo topo (direita)
        $placeImage($pdf, $logoRel, 215, 12, 60, 22, 'LOGO');

        // Títulos
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','B',14);
        $pdf->SetXY(20, 18); $pdf->Cell(170, 8, $toPdf('Conteúdo Ministrado:'), 0, 1, 'L');
        $sub = $curso->sigla ?? $curso->titulo;
        if ($sub) { $pdf->SetFont('Arial','',12); $pdf->SetXY(20, 26); $pdf->Cell(170, 7, $toPdf($sub), 0, 1, 'L'); }

        // Moldura da lista
        $pdf->SetDrawColor(120,120,120); $pdf->Rect(14, 34, 269, 118);

        // === Quadro CARGA / NOTA / REGISTRO dentro da moldura (topo direito) ===
        // Nota = média (0–10) das melhores tentativas por quiz do curso para este aluno.
        $nota10 = null;

        try {
            // ✅ qualifica o campo e usa distinct para evitar IDs repetidos
            $quizIds = $curso->quizzes()
                ->select('quizzes.id')
                ->distinct()
                ->pluck('quizzes.id');

            if ($quizIds->isNotEmpty()) {
                $tents = QuizTentativa::where('aluno_id', $matricula->aluno_id)
                    ->whereIn('quiz_id', $quizIds)
                    ->get();

                $best = [];
                foreach ($tents as $t) {
                    if (($t->nota_maxima ?? 0) > 0) {
                        $ratio = (float)$t->nota_obtida / (float)$t->nota_maxima; // 0..1
                        $q = (int)$t->quiz_id;
                        if (!isset($best[$q]) || $ratio > $best[$q]) {
                            $best[$q] = $ratio;
                        }
                    }
                }

                if ($best) {
                    $nota10 = round((array_sum($best) / count($best)) * 10, 1);
                }
            }
        } catch (\Throwable $e) {
            \Log::error($e);
        }
        $notaDisplay = $nota10 !== null
            ? number_format($nota10, 1, ',', '')
            : ($cert->nota_aproveitamento !== null ? number_format((float)$cert->nota_aproveitamento, 1, ',', '') : '—');

        $statsX = 14 + 269 - 98; $statsY = 38;
        $pdf->SetXY($statsX, $statsY);
        $pdf->SetFont('Arial','',11);
        $pdf->Cell(60, 6, $toPdf('CARGA TOTAL:'), 0, 0, 'L');
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(30, 6, $toPdf($cargaHorariaHoras.'h'), 0, 1, 'L');

        $pdf->SetXY($statsX, $statsY+8);
        $pdf->SetFont('Arial','',11);
        $pdf->Cell(60, 6, $toPdf('NOTA DE APROVEITAMENTO:'), 0, 0, 'L');
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(30, 6, $toPdf($notaDisplay), 0, 1, 'L');

        $pdf->SetXY($statsX, $statsY+16);
        $pdf->SetFont('Arial','',11);
        $pdf->Cell(60, 6, $toPdf('REGISTRO'), 0, 0, 'L');
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(30, 6, $toPdf($cert->codigo_verificacao ?? ''), 0, 1, 'L');

        // Lista de módulos (2 colunas; direita começa mais baixo p/ não colidir com o quadro)
        $modulos = [];
        if (method_exists($curso,'modulos')) { try { $modulos = $curso->modulos()->orderBy('ordem')->pluck('titulo')->toArray(); } catch (\Throwable $e) {} }
        if (!$modulos && !empty($curso->conteudo_programatico)) {
            $modulos = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $curso->conteudo_programatico))));
        }

        $pdf->SetTextColor(20,20,20); $pdf->SetFont('Arial','',11);
        $xL=20; $xR=150; $yL=40; $yR=64; $colW=130; $lineH=6; $maxY=146; $i=1;
        foreach ($modulos as $m) {
            if ($yL <= $maxY) { $pdf->SetXY($xL,$yL); $pdf->MultiCell($colW,$lineH,$toPdf(($i++).'. '.$m),0,'L'); $yL=$pdf->GetY(); }
            else { if ($yR > $maxY) break; $pdf->SetXY($xR,$yR); $pdf->MultiCell($colW-2,$lineH,$toPdf(($i++).'. '.$m),0,'L'); $yR=$pdf->GetY(); }
        }

        // Corpo técnico + assinaturas (cabem antes do fim da página)
        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(20, 158); $pdf->Cell(120, 7, $toPdf('Corpo Técnico:'), 0, 2, 'L');


        $tec = [
            ['img'=>$assinTec1Rel, 'nome'=>config('certificados.corpo_tecnico.0.nome')  ?? 'Helder Votri Rosso',
                'cargo'=>config('certificados.corpo_tecnico.0.cargo') ?? 'Eng. Eletricista / Seg. do Trabalho – CREA-SC 130455-2', 'x'=>25],
            ['img'=>$assinTec2Rel, 'nome'=>config('certificados.corpo_tecnico.1.nome')  ?? 'Zunei Votri',
                'cargo'=>config('certificados.corpo_tecnico.1.cargo') ?? 'Enfermeiro – COREN-SC 201310', 'x'=>120],
        ];


        // === QR CODE (canto direito inferior do conteúdo, dentro da área em vermelho) ===
        // URL absoluta para a verificação (usa APP_URL)
        // URL do QR
        $qrUrl = route('certificados.verify', $cert->codigo_verificacao);

        // gera o PNG em memória
        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);
        $qrPath = $tmpDir.'/qr-'.$cert->codigo_verificacao.'.png';

// Gera o QR (v6)

        $builder = new Builder(
            writer: new PngWriter(),
            data: $qrUrl,
            size: 350,
            margin: 1
        );

        $result = $builder->build();
        $result->saveToFile($qrPath);

        // posicione onde está o retângulo vermelho (ajuste se precisar)
        $qrX = 242;  // mm
        $qrY = 155;  // mm
        $qrW = 45;   // mm
        $pdf->Image($qrPath, $qrX, $qrY, $qrW, 0, 'PNG');

        // Rótulo pequeno abaixo do QR (opcional)
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY($qrX, $qrY + $qrW + 2);
        $pdf->Cell($qrW, 4, $toPdf('Verificação: '.$cert->codigo_verificacao), 0, 0, 'C');



        foreach ($tec as $b) {
            $x0=(float)$b['x']; $w=80;
            $placeImage($pdf, $b['img'], $x0+15, 166, 50, 16, '');
            $pdf->SetDrawColor(0,0,0);
            $pdf->SetXY($x0, 184); $pdf->Cell($w, 0, '', 'T');
            $pdf->SetFont('Arial','B',10); $pdf->SetXY($x0, 186); $pdf->Cell($w, 5, $toPdf($b['nome']), 0, 2, 'C');
            $pdf->SetFont('Arial','',9);  $pdf->Cell($w, 5, $toPdf($b['cargo']), 0, 2, 'C');
        }

        return $pdf->Output('S');
    }





    public function issue(Request $request, Cursos $curso)
    {
        $alunoId = auth('aluno')->id() ?? $request->session()->get('aluno_id');
        $aluno   = User::where('tipo_usuario','aluno')->where('id',$alunoId)->firstOrFail();
        abort_if(!$aluno, 403);

        $matricula = Matriculas::where('aluno_id', $aluno->id)
            ->where('curso_id', $curso->id)
            ->firstOrFail();

        // cria/recupera registro
        $cert = Certificados::firstOrCreate(
            ['matricula_id' => $matricula->id],
            [
                'codigo_verificacao' => strtoupper(\Illuminate\Support\Str::random(10)),
                'data_emissao'       => now(),
                'valido'             => true,
            ]
        );

        // regra de elegibilidade
        $elig = app(CourseCompletionService::class)->checkEligibility($cert->matricula);
        if (!$elig['elegivel']) {
            abort(403, 'Certificado disponível apenas após concluir todas as provas dos módulos com média mínima de '
                . number_format($elig['exigido'], 2));
        }

        // ---------- Gera PDF (FPDF) usando o template criado ----------
        $pdfBytes = $this->renderCertificadoFPDF($curso, $matricula, $cert);

        // Salva arquivo em storage/public
        $filename = "certificado--{$aluno->id}-{$cert->codigo_verificacao}.pdf";
        $path     = "certificados/{$filename}";
        \Storage::disk('public')->put($path, $pdfBytes);

        // Atualiza URL pública
        $cert->url_certificado = \Storage::url($path);
        $cert->save();

        // Baixa (ou troque por inline, se quiser visualizar em tela)
        return response($pdfBytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function visualizar(Request $request, Certificados $cert)
    {
        $this->assertOwnership($request, $cert);
        $bytes = $this->renderCertificadoFPDF($cert->matricula->curso, $cert->matricula, $cert);

        $filename = "certificado-{$cert->matricula->aluno_id}-{$cert->codigo_verificacao}.pdf";
        return response($bytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

// DOWNLOAD
    public function download(Request $request, Certificados $cert)
    {
        $this->assertOwnership($request, $cert);
        $bytes = $this->renderCertificadoFPDF($cert->matricula->curso, $cert->matricula, $cert);

        $filename = "certificado-{$cert->matricula->aluno_id}-{$cert->codigo_verificacao}.pdf";
        return response($bytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }


}
