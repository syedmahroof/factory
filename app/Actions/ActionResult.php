<?php

namespace App\Actions;

use Illuminate\Http\JsonResponse;

/**
 * Uniform return type for every Action class.
 *
 * Every key exists on every path — callers can always read ->success,
 * ->message, ->data and ->status without isset() guessing, and static
 * analysis can see the shape.
 */
class ActionResult
{
    public function __construct(
        public bool $success,
        public string $message = '',
        public mixed $data = null,
        public int $status = 200,
    ) {}

    public static function success(mixed $data = null, string $message = ''): self
    {
        return new self(true, $message, $data);
    }

    public static function failure(string $message, int $status = 400): self
    {
        return new self(false, $message, null, $status);
    }

    /**
     * Convenience for API controllers.
     */
    public function toResponse(): JsonResponse
    {
        return response()->json([
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
        ], $this->status);
    }
}
