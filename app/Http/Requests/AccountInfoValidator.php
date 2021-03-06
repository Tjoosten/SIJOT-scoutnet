<?php

namespace Sijot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccountInfoValidator extends FormRequest
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
            'theme' => 'required',
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ];
    }
}
