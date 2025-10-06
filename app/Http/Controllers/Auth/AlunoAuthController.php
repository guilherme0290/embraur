<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cursos;
use App\Models\Matriculas;
use App\Models\User;
use App\Rules\CpfValido;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AlunoAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.aluno-login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        $aluno = User::where('email', $data['email'])
            ->where('tipo_usuario', 'aluno')
            ->where('status', 'ativo')
            ->first();

        if ($aluno && Hash::check($data['password'], $aluno->password)) {
            $request->session()->regenerate();
            // define sessão simples usada pelas views/rotas do aluno
            $request->session()->put('aluno_id', $aluno->id);
            $request->session()->put('aluno_nome', $aluno->nome_completo);
            return redirect()->route('aluno.dashboard');
        }

        return back()->withErrors(['email' => 'Credenciais inválidas ou usuário inativo.']);
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['aluno_id', 'aluno_nome']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('site.home');
    }

    public function showRegisterForm(Request $request)
    {
        return view('auth.aluno-register', [
            'intended' => $request->query('intended'),
            'curso' => $request->query('curso'),
        ]);
    }

    public function register(Request $request)
    {
        $intended = $request->input('intended');   // URL de volta (ex.: detalhe do curso)
        $cursoId  = $request->input('curso');      // id do curso (se veio do botão "Comprar agora")

        if ($request->filled('data_nascimento')) {
            try {
                $dt = Carbon::createFromFormat('d/m/Y', $request->input('data_nascimento'));
                // opcional: valida se é uma data real (Carbon já lança exceção se for inválida)
                $request->merge(['data_nascimento' => $dt->format('Y-m-d')]);
            } catch (\Exception $e) {
                return back()
                    ->withErrors(['data_nascimento' => 'Informe a data no formato DD/MM/AAAA.'])
                    ->withInput();
            }
        }

        $cpf            = preg_replace('/\D+/', '', $request->cpf ?? '');
        $telefone       = preg_replace('/\D+/', '', $request->telefone ?? '');

        $request->merge([
            'cpf'      => $cpf,
            'telefone' => $telefone,
        ]);

        // 3) Regras, mensagens e rótulos amigáveis
        $rules = [
            'nome'            => ['bail','required','string','max:120'],
            'email'           => ['bail','required','email','unique:users,email'],
            'password'        => ['bail','required','min:6'],
            'cpf'             => ['bail','required','string','size:11', new CpfValido(), 'unique:users,cpf'],
            'telefone'        => ['bail','required','string','unique:users,telefone'],
            'data_nascimento' => ['bail','required','date','before:today','after:1900-01-01'],
        ];

        $messages = [
            'required'                  => 'O campo :attribute é obrigatório.',
            'email'                     => 'Informe um e-mail válido.',
            'min'                       => 'O campo :attribute deve ter ao menos :min caracteres.',
            'unique'                    => 'Este :attribute já está cadastrado.',
            'data_nascimento.date'      => 'Data de nascimento inválida. Use o formato DD/MM/AAAA (ex.: 25/02/1996).',
            'data_nascimento.before'    => 'A data de nascimento deve ser anterior a hoje.',
            'data_nascimento.after'     => 'A data de nascimento deve ser posterior a 01/01/1900.',
        ];

        $attributes = [
            'nome'            => 'nome',
            'email'           => 'e-mail',
            'password'        => 'senha',
            'cpf'             => 'CPF',
            'telefone'        => 'celular',
            'data_nascimento' => 'data de nascimento',
        ];

        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        if ($validator->fails()) {
            // Resposta HTML (volta para o form) com mensagem geral amigável
            if (!$request->expectsJson()) {
                return back()
                    ->with('error', 'Não foi possível concluir o cadastro. Revise os campos destacados.')
                    ->withErrors($validator)
                    ->withInput();
            }
            // Resposta JSON (caso SPA/AJAX)
            return response()->json([
                'message' => 'Falha na validação. Revise os campos informados.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();


        // Cria e autentica o aluno
        $aluno = User::createOrFirst([
            'nome_completo'   => $data['nome'],
            'cpf'             => $request->cpf,       // já sem máscara
            'email'           => $data['email'],
            'password'        => $data['password'],   // mutator faz o hash
            'tipo_usuario'    => 'aluno',
            'status'          => 'ativo',
            'telefone'        => $request->telefone,  // já sem máscara
            'data_nascimento' => $data['data_nascimento'],
        ]);

        auth('aluno')->login($aluno);
        $request->session()->regenerate();
        $request->session()->put('aluno_id', $aluno->id);
        $request->session()->put('aluno_nome', $aluno->nome_completo);

        // Se veio de um curso (botão "Comprar agora")
        if ($cursoId) {
            $curso = Cursos::find($cursoId);

            if (!$curso) {
                return redirect()->route('aluno.dashboard')
                    ->with('error', 'Curso não encontrado.');
            }

            // >>> Importante: NÃO cria matrícula aqui <<<

            // Se o curso for grátis, você pode matricular direto e pular checkout
            if ((float)($curso->preco ?? 0) <= 0) {
                $jaTem = Matriculas::where('aluno_id', $aluno->id)
                    ->where('curso_id', $curso->id)
                    ->exists();

                if (!$jaTem) {
                    $agora = Carbon::now();
                    Matriculas::create([
                        'aluno_id'              => $aluno->id,
                        'curso_id'              => $curso->id,
                        'data_matricula'        => $agora,
                        'data_inicio'           => $agora,
                        'data_conclusao'        => null,
                        'data_vencimento'       => $curso->validade_dias ? $agora->copy()->addDays((int)$curso->validade_dias) : null,
                        'progresso_porcentagem' => 0,
                        'nota_final'            => null,
                    ]);
                }

                return redirect()->route('aluno.dashboard')
                    ->with('success', 'Cadastro realizado! Acesso ao curso liberado.');
            }

            // Pago: manda iniciar o checkout (controller que cria Pedido + ItensPedido)
            return redirect()->route('checkout.start', $curso->id);
        }

        // Sem curso: volta para intended (se houver) ou dashboard
        return $intended
            ? redirect()->to($intended)->with('success', 'Cadastro realizado com sucesso!')
            : redirect()->route('aluno.dashboard')->with('success', 'Cadastro realizado com sucesso!');
    }
}
