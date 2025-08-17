<?php

namespace App\Http\Requests\Admin\MemberShipManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberShipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
     public function rules(): array
    {
        return [
            
        ] + ($this->isMethod('POST') ? $this->store() : $this->update());;
    }
    protected function store(): array
    {
        return [
             'name' => ['required', 'string', 'min:3', Rule::unique('member_ships', 'name')],
             'slug' => ['required', 'string', 'min:3', Rule::unique('member_ships', 'slug')],
             'tag' => ['nullable', 'string', 'min:3', Rule::unique('member_ships', 'tag')],
        ];
    }
    public function update(): array
    {
        return [
            'slug' => ['required', 'string','min:3', Rule::unique('member_ships', 'slug')->ignore(decrypt($this->route('membership')))],
            'name' => ['required', 'string','min:3', Rule::unique('member_ships', 'name')->ignore(decrypt($this->route('membership')))],
            'tag' => ['nullable', 'string','min:3', Rule::unique('member_ships', 'tag')->ignore(decrypt($this->route('membership')))],
        ];
    }
}
