<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', Rule::unique('products', 'slug')->ignore($id)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'sku' => ['nullable', Rule::unique('products', 'sku')->ignore($id)],
            'usage' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'requires_prescription' => ['nullable', 'boolean'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:base_price'],
            'unit' => ['nullable', 'string', 'max:50'],
            'dosage_form' => ['nullable', 'string'],
            'active_ingredient' => ['nullable', 'string'],
            'manufacturer' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
            'images' => ['nullable', 'array', 'max:6'],
            'images.*.url' => ['required_with:images', 'url'],
            'images.*.is_primary' => ['boolean'],
            'attributes' => ['nullable', 'array'],
            'attributes.*.attr_key' => ['required_with:attributes', 'string'],
            'attributes.*.attr_value' => ['required_with:attributes', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Danh mục không hợp lệ.',
            'sku.unique' => 'SKU này đã tồn tại.',
            'sale_price.lte' => 'Giá bán không được lớn hơn giá gốc.',
            'images.max' => 'Sản phẩm tối đa chỉ có 6 hình ảnh.',
        ];
    }
}
