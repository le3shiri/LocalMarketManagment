<?php

namespace App\Http\Requests\Admin;

use App\Enums\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Accept Spanish decimal commas ("2,40") in the price field.
        if (is_string($this->price)) {
            $this->merge(['price' => str_replace(',', '.', $this->price)]);
        }

        $this->merge(['sku' => strtoupper(trim((string) $this->sku))]);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('products')->ignore($this->route('product'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'gt:0', 'max:99999', 'decimal:0,2'],
            'unit_type' => ['required', Rule::enum(UnitType::class)],
            'is_perishable' => ['boolean'],
            'location' => ['nullable', 'string', 'max:60'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'categoría', 'name' => 'nombre', 'sku' => 'SKU', 'description' => 'descripción',
            'price' => 'precio', 'unit_type' => 'tipo de venta', 'location' => 'ubicación', 'image' => 'imagen',
        ];
    }

    /** Validated product attributes, without the uploaded file. */
    public function productData(): array
    {
        return $this->safe()->except('image') + ['is_perishable' => $this->boolean('is_perishable')];
    }
}
