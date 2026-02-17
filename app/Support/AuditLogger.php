<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditLogger
{
    public static function log(string $action, string $entityType, ?int $entityId = null, array $meta = []): void
    {
        try {
            $request = request();

            DB::table('audit_logs')->insert([
                'user_id' => Auth::id(),
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'meta' => empty($meta) ? null : json_encode($meta),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Avoid blocking business actions when logging fails.
        }
    }
}
