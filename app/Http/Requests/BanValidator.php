<?php

namespace Sijot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class BanValidator
 * 
 * //TODO: Complete teh class docblock.
 * 
 * @package Sijot\Http\Requests
 */
class BanValidator extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'eind_datum' => 'required',
            'reason'     => 'required'
        ];
    }
}
