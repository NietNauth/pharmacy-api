<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'unique:products,slug'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'sku' => ['required', 'unique:products,sku'],
            'usage' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'requires_prescription' => ['required', 'boolean'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:base_price'],
            'unit' => ['required', 'string', 'max:50'],
            'dosage_form' => ['nullable', 'string'],
            'active_ingredient' => ['nullable', 'string'],
            'manufacturer' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
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
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'category_id.required' => 'Danh mục là bắt buộc.',
            'category_id.exists' => 'Danh mục không hợp lệ.',
            'sku.required' => 'SKU là bắt buộc.',
            'sku.unique' => 'SKU này đã tồn tại.',
            'requires_prescription.required' => 'Trạng thái thuốc kê đơn là bắt buộc.',
            'base_price.required' => 'Giá gốc là bắt buộc.',
            'sale_price.lte' => 'Giá bán không được lớn hơn giá gốc.',
            'unit.required' => 'Đơn vị tính là bắt buộc.',
            'status.required' => 'Trạng thái là bắt buộc.',
            'images.max' => 'Sản phẩm tối đa chỉ có 6 hình ảnh.',
        ];
    }
}
