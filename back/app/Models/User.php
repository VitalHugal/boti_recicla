<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'info_user',
        'cpf_hash',
    ];

    protected $table = 'users';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function rules()
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'cpf' => 'required|digits:11',
            'notify' => 'boolean:1',
        ];
    }

    public function feedback()
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.max' => 'O campo nome deve conter até 255 caracteres.',

            'email.email' => 'E-mail informado não é válido. Por favor, verifique.',
            'email.required' => 'O campo E-mail é obrigatório.',
            'email.max' => 'O campo E-mail deve conter até 255 caracteres.',

            'cpf.required' => 'O campo CPF é obrigatório.',
            'cpf.digits' => 'O campo CPF recisa ter 11 digitos.',
            'cpf.integer' => 'Válido apenas números no campo CPF.',
            
            'notify.boolean' => 'O campo aceito receber notificações aceita apenas 1.',
        ];
    }
}