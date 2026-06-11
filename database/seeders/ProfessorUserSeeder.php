<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProfessorUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@id.com.br'], // chave única
            [
                'password'        => Hash::make('Admin@2026'),
                'nome_completo'   => 'Administrador',
                'telefone'        => '(11) 90000-0000',
                'cpf'             => '00000000000',     // ajuste se validar CPF
                'data_nascimento' => '1980-01-01',      // YYYY-mm-dd
                'foto_perfil'     => null,
                'tipo_usuario'    => 'professor',
                'status'          => 'ativo',
            ]
        );
    }
}
