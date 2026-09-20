<?php
namespace App\Http\Requests\Organization;
use Illuminate\Foundation\Http\FormRequest;
class StoreWarehouseRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'plant_id' => 'required|exists:plants,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code',
            'type' => 'required|in:raw,finished,scrap,quarantine,transit',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}