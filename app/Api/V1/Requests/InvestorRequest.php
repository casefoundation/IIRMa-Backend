<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Input;

class InvestorRequest extends Request
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
        $args = [
            'filter_key'=>'sometimes',
            'filter_value'=>'sometimes',
        ];

        return $args;
    }
}
