<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CpfValido implements Rule
{
    public function passes($attribute, $value)
    {
        $cpf = preg_replace('/\D/', '', (string) $value);
        if (strlen($cpf) !== 11) return false;
        if (preg_match('/^([0-9])\1{10}$/', $cpf)) return false;

        $soma = 0; for ($i=0,$j=10;$i<9;$i++,$j--) $soma += (int)$cpf[$i] * $j;
        $resto = $soma % 11; $d1 = $resto < 2 ? 0 : 11 - $resto;
        if ((int)$cpf[9] !== $d1) return false;

        $soma = 0; for ($i=0,$j=11;$i<10;$i++,$j--) $soma += (int)$cpf[$i] * $j;
        $resto = $soma % 11; $d2 = $resto < 2 ? 0 : 11 - $resto;
        if ((int)$cpf[10] !== $d2) return false;

        return true;
    }

    public function message()
    {
        return 'O CPF informado é inválido.';
    }
}
