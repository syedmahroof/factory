<?php

namespace Integrations\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Integrations\Crm\Actions\AddMerchantNotesAction;
use Integrations\Crm\Actions\GetMerchantDetailsAction;
use Integrations\Crm\Actions\GetMerchantNotesAction;
use Integrations\Crm\Actions\GetMerchantPaymentsAction;
use Integrations\Crm\Actions\GetProcessingAchAction;
use Integrations\Crm\Actions\PushMerchantAction;
use Integrations\Crm\Actions\UpdateCrmIdAction;
use Integrations\Crm\Http\Requests\AddMerchantNotesRequest;
use Integrations\Crm\Http\Requests\GetMerchantDetailsRequest;
use Integrations\Crm\Http\Requests\GetMerchantNotesRequest;
use Integrations\Crm\Http\Requests\GetMerchantPaymentsRequest;
use Integrations\Crm\Http\Requests\GetProcessingAchRequest;
use Integrations\Crm\Http\Requests\PushMerchantRequest;
use Integrations\Crm\Http\Requests\UpdateCrmIdRequest;

/**
 * Endpoints the third-party CRM calls on us.
 */
class CrmController extends Controller
{
    /**
     * Push lead from crm to IP
     *
     * push the lead from crm to IP when clicking push to ip
     */
    public function pushMerchant(PushMerchantRequest $request, PushMerchantAction $action)
    {
        try {
            $validated = collect($request->validated());
            $result = $action->execute($validated);

            if (! $result['success']) {
                throw new Exception($result['message'], 200);
            }

            return response()->json([
                'status' => 200,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
                'data' => '',
            ], $e->getCode());
        }

    }

    /**
     * Get a specific merchant details
     *
     * get details about a specific merchant
     */
    public function getMerchant(GetMerchantDetailsRequest $request, GetMerchantDetailsAction $action)
    {

        try {

            $validated = collect($request->validated());
            $result = $action->execute($validated);

            if (! $result['success']) {
                throw new Exception($result['message'], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 200);

        } catch (Exception $e) {

            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
                'data' => '',
            ], $e->getCode());

        }

    }

    /**
     * Update CRM Id
     *
     * update crm id of a specific merchant
     */
    public function updateCrmId(UpdateCrmIdRequest $request, UpdateCrmIdAction $action)
    {
        $result = $action->execute(collect($request->validated()));

        if (! $result['success']) {
            return response()->json([
                'status' => 'error',
                'msg' => $result['message'],
                'result' => [],
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'msg' => $result['message'],
            'result' => $result['data'],
        ], 200);
    }

    /**
     * Get merchant payment details
     *
     * payments recorded against one advance, filtered by CRM id and date range
     */
    public function getMerchantPayments(GetMerchantPaymentsRequest $request, GetMerchantPaymentsAction $action)
    {
        $result = $action->execute(collect($request->validated()));

        if (! $result['success']) {
            return response()->json([
                'status' => 0,
                'error' => $result['message'].' | ',
                'data' => '',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'total_count' => $result['total_count'],
            'result' => $result['data'],
        ], 200);
    }

    /**
     * Get processing ACH transactions
     *
     * ACH debits sent to the gateway that have not reached a final state
     */
    public function getProcessingAch(GetProcessingAchRequest $request, GetProcessingAchAction $action)
    {
        $result = $action->execute(collect($request->validated()));

        if (! $result['success']) {
            return response()->json([
                'status' => 0,
                'result' => $result['message'],
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'result' => $result['data'],
        ], 200);
    }

    /**
     * Add merchant notes
     *
     * add notes to a specific merchant
     */
    public function addMerchantNotes(AddMerchantNotesRequest $request, AddMerchantNotesAction $action)
    {
        try {
            $validated = collect($request->validated());
            $result = $action->execute($validated);

            if (! $result['success']) {
                throw new Exception($result['message'], 200);
            }

            return response()->json([
                'status' => 200,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
                'data' => '',
            ], $e->getCode() ?: 200);
        }
    }

    /**
     * Get merchant notes
     *
     * notes recorded against one merchant
     */
    public function getMerchantNotes(GetMerchantNotesRequest $request, GetMerchantNotesAction $action)
    {
        $result = $action->execute(collect($request->validated()));

        if (! $result['success']) {
            return response()->json([
                'status' => 0,
                'message' => $result['message'],
                'data' => '',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'total_count' => $result['total_count'],
            'result' => $result['data'],
        ], 200);
    }
}
