<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'fk_user_id',
        'started_weighing',
        'confirmation_weighing',
        'finished_weighing',
        'finished_interaction',
        'redeemed_credit',
        'credit',
        'trash_weight',
        'over_700'
    ];
    protected $table = 'participations';
    protected $date = ['deleted_at'];

    public function initialParticipationRules()
    {
        return [
            'started_weighing' => 'required|boolean:1',
        ];
    }

    public function initialParticipationFeedback()
    {
        return [
            'started_weighing.required' => 'O campo começou a pesagem é obrigatório.',
            'started_weighing.boolean' => 'Válido apenas 1 para o campo começou a pesagem.',
        ];
    }

    public function finishedParticipationRules()
    {
        return [
            'finished_weighing' => 'required|boolean:1',
        ];
    }

    public function finishedParticipationFeedback()
    {
        return [
            'finished_weighing.required' => 'O campo finalizou a pesagem é obrigatório.',
            'finished_weighing.boolean' => 'Válido apenas 1 para o campo começou a pesagem.',
        ];
    }

    public function finishedInterationRules()
    {
        return [
            'finished_interaction' => 'required|boolean:1',
        ];
    }

    public function finishedInterationFeedback()
    {
        return [
            'finished_interaction.required' => 'O campo finalizar a interação é obrigatório.',
            'finished_interaction.boolean' => 'Válido apenas 1 para o campo começou a pesagem.',
        ];
    }

    public function resultParticipationRules()
    {
        return [
            'credits' => 'required|integer',
            'weight' => 'required|integer',
            'over_700' => 'boolean:0,1',
        ];
    }
    public function resultParticipationFeedback()
    {
        return [
            'credits.required' => 'O campo crédito é obrigatório.',
            'credits.integer' => 'Válido apenas valores númericos para o campo crédito.',

            'weight.required' => 'O campo peso do lixo é obrigatório.',
            'weight.integer' => 'Válido apenas valores númericos para o campo crédito.',

            'over_700.boolean' => 'Válido apenas 0 ou 1 para o campo maior que 700g.',
        ];
    }

    public function redeemCreditsRules()
    {
        return [
            'id' => 'required',
        ];
    }

    public function redeemCreditsFeedback()
    {
        return [
            'id.required' => 'O campo id é obrigatório.',
        ];
    }

    public function confirmationWeighingRules()
    {
        return [
            'confirmation' => 'required|boolean:1',
        ];
    }

    public function confirmationWeighingFeedback()
    {
        return [
            'confirmation.required' => 'O campo confirmação é obrigatório.',
            'confirmation.boolean' => 'Válido apenas 1 para o campo começou a pesagem.',
        ];
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'fk_user_id');
    }
}