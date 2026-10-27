<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class MobileApiMutation
{
    public function execute(Request $request, array $data, callable $operation)
    {
        $key = $request->header('Idempotency-Key');
        if (!is_string($key) || !preg_match('/^[A-Za-z0-9_-]{8,128}$/', $key)) {
            throw ValidationException::withMessages(['idempotency_key' => 'Supply an Idempotency-Key header of 8-128 letters, digits, underscores or hyphens.']);
        }
        $keyHash = hash('sha256', $key);
        $requestHash = hash('sha256', $request->path().'|'.json_encode($data, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($request, $data, $operation, $keyHash, $requestHash) {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $existing = DB::table('mobile_api_mutations')->where('user_id', $request->user()->id)->where('key_hash', $keyHash)->first();
            if ($existing) {
                abort_unless(hash_equals($existing->request_hash, $requestHash), 409, 'This idempotency key was already used for a different request.');
                return response()->json(json_decode($existing->response, true, 512, JSON_THROW_ON_ERROR), 201)->header('Idempotency-Replayed', 'true');
            }
            try {
                $response = ['data' => $operation($data)];
            } catch (InvalidArgumentException $exception) {
                if ($exception->getMessage() === 'Posting date falls in a closed fiscal period.') {
                    throw ValidationException::withMessages(['date' => 'The transaction date falls in a closed fiscal period.']);
                }
                throw $exception;
            }
            DB::table('mobile_api_mutations')->insert([
                'user_id' => $request->user()->id, 'key_hash' => $keyHash, 'request_hash' => $requestHash,
                'response' => json_encode($response, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now(),
            ]);
            return response()->json($response, 201)->header('Idempotency-Replayed', 'false');
        }, 3);
    }
}