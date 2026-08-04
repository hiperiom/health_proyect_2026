<?php

namespace App\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class WriteAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected array $payload) {}

    public function handle(): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $this->payload['user_id'],
            'action' => $this->payload['action'],
            'target_resource' => $this->payload['target_resource'],
            'target_id' => $this->payload['target_id'],
            'payload' => json_encode($this->payload['payload'], JSON_UNESCAPED_UNICODE),
            'ip_address' => $this->payload['ip_address'],
            'performed_at' => $this->payload['performed_at'],
            'created_at' => now()->utc(),
            'updated_at' => now()->utc(),
        ]);
    }
}
