<?php

namespace App\Modules\ERPIntegration\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('erp-integration.sync');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:products,orders,inventory,customers'],
            'endpoint' => ['required', 'string'],
            'params' => ['sometimes', 'array'],
            'priority' => ['sometimes', 'string', 'in:low,normal,high'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Sync type is required',
            'type.in' => 'Invalid sync type. Allowed: products, orders, inventory, customers',
            'endpoint.required' => 'Endpoint is required',
        ];
    }
}

