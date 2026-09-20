<?php

declare(strict_types=1);

namespace App\Actions\Bank;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * The bank behind a routing number.
 *
 * The number is on the same line of the same check as the account number, and
 * typing the bank's name by hand is how it ends up disagreeing with the number
 * that actually routes the money. The directory answers with the name on file.
 *
 * A failed lookup is never fatal: the field stays editable and the operator types
 * the name themselves. What must not happen is a wrong name arriving quietly.
 */
class LookUpRoutingNumberAction
{
    private const URL = 'https://www.routingnumbers.info/api/data.json';

    /**
     * @return array{state: 'short'|'found'|'failed', message: string, bank_name: string}
     */
    public function handle(?string $routingNumber): array
    {
        $digits = preg_replace('/\D/', '', (string) $routingNumber);

        /*
         * The directory only answers to a complete number, so a half-typed one is
         * held back rather than sent and reported as not found.
         */
        if (strlen($digits) !== 9) {
            return [
                'state' => 'short',
                'message' => strlen($digits).' of 9 digits',
                'bank_name' => '',
            ];
        }

        try {
            $response = Http::timeout(8)->get(self::URL, ['rn' => $digits])->json();
        } catch (Throwable $e) {
            return [
                'state' => 'failed',
                'message' => 'The routing directory could not be reached — type the bank name in yourself.',
                'bank_name' => '',
            ];
        }

        if (($response['code'] ?? null) != 200) {
            return [
                'state' => 'failed',
                'message' => $response['message'] ?? 'That routing number is not in the directory.',
                'bank_name' => '',
            ];
        }

        return [
            'state' => 'found',
            'message' => trim(($response['city'] ?? '').' '.($response['state'] ?? '')),
            'bank_name' => (string) ($response['customer_name'] ?? ''),
        ];
    }
}
