<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\HandlesSingletonSettings;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\UpdatePaymentGatewaySettingsRequest;
use App\Services\PaymentGatewaySettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentGatewaySettingsController extends Controller
{
    use HandlesSingletonSettings;

    public function __construct(private readonly PaymentGatewaySettingsService $paymentGatewaySettingsService) {}

    public function show(Request $request): JsonResponse
    {
        return $this->showSettings(
            $request,
            $this->paymentGatewaySettingsService,
            'admin.settings.payments.view',
            'Payment gateway settings fetched successfully'
        );
    }

    public function update(UpdatePaymentGatewaySettingsRequest $request): JsonResponse
    {
        return $this->updateSettings(
            $request,
            $this->paymentGatewaySettingsService,
            'admin.settings.payments.edit',
            'admin.settings.payments.update',
            $request->validated(),
            'Payment gateway settings updated successfully'
        );
    }
}
