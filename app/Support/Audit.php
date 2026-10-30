<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class Audit
{
    public static function record(string $action, string $resource, ?int $id = null): void
    {
        DB::table('activity_logs')->insert(['user_id' => auth()->id(), 'action' => $action, 'resource' => $resource, 'resource_id' => $id, 'created_at' => now()]);
    }
}
