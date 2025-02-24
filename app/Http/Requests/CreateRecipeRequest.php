<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRecipeRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'source_url' => 'nullable|url',
            'image' => 'nullable|image|max:2048',
            'ingredients' => 'required|array|min:1',
            // 'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            // 'ingredients.*.quantity' => 'required|numeric',
            'ingredients.*.unit' => 'nullable|string',
            'ingredients.*.notes' => 'nullable|string',
            'steps' => 'required|array|min:1',
            // 'steps.*.instruction' => 'required|string',
            // 'steps.*.order' => 'required|integer|min:1',
        ];
    }
}
