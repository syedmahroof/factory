<?php

namespace Integrations\Crm\Actions;

use App\Models\Merchant;
use Illuminate\Support\Collection;
use Integrations\Crm\Exceptions\CrmException;
use Throwable;

final class UpdateCrmIdAction
{
    public function execute(Collection $data): array
    {
        try {
            $merchantId = (int) $data->get('merchant_id');
            $crmId = $data->get('crm_id', null);

            $merchant = Merchant::find($merchantId);

            if (! $merchant) {
                throw new CrmException('No merchant was found for the given merchant_id.');
            }

            $merchant->update(['crm_id' => $crmId]);

            $return = [
                'success' => true,
                'message' => 'Merchant CRM ID updated.',
                'data' => [
                    ['id' => (string) $merchant->crm_id],
                ],
            ];
        } catch (CrmException $e) {
            $return = [
                'success' => false,
                'status' => 0,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        } catch (Throwable $th) {
            report($th);

            $return = [
                'success' => false,
                'status' => 0,
                'message' => 'Something went wrong',
                'data' => [],
            ];
        }

        return $return;
    }
}
