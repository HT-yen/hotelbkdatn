<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class HotelUpdateRequest extends FormRequest
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
            'actual_address' => 'required',
            'street' => 'required',
            'place_id' => 'required',
            'star' => 'required',
            'name' => 'min:1|unique:hotels,name,' . $this->id . ',id',
            'images.*' => 'image|max:' . config('image.max_upload_size')
        ];
    }
}
