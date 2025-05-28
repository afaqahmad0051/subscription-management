<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionPlan;
use Illuminate\Validation\Rule;
use App\Traits\FailedValidationResponse;
use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    use FailedValidationResponse;

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
            'plan_name' => [
                'required',
                'string',
                Rule::enum(SubscriptionPlan::class),
            ],
            'auto_renew' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'plan_name.required' => 'Please select a subscription plan.',
            'plan_name.enum' => 'Invalid subscription plan selected.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'auto_renew' => $this->boolean('auto_renew', true),
        ]);
    }
}
