<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class GoogleContactsRuntime
{
    public const HEARTBEAT_KEY = 'ife:gcontact:worker:heartbeat';
    public const HEARTBEAT_MAX_AGE = 180;

    private $nextSweep = 0;
    private $mode;

    public function setMode(string $mode): void
    {
        $this->mode = $mode === 'cron' ? 'cron' : 'worker';
    }

    private function mode(): string
    {
        return $this->mode ?: (config('google-contacts.process_mode') === 'cron' ? 'cron' : 'worker');
    }

    public function heartbeat(bool $running = true): void
    {
        Cache::put(self::HEARTBEAT_KEY, ['seen_at' => now()->timestamp, 'running' => $running,
            'mode' => $this->mode(), 'completed' => false], 86400);
    }

    public function finish(bool $completed): void
    {
        Cache::put(self::HEARTBEAT_KEY, ['seen_at' => now()->timestamp, 'running' => false,
            'mode' => $this->mode(), 'completed' => $completed], 86400);
    }

    public function status(): array
    {
        $heartbeat = Cache::get(self::HEARTBEAT_KEY, []);
        $seen = (int) ($heartbeat['seen_at'] ?? 0);
        $mode = ($heartbeat['mode'] ?? $this->mode()) === 'cron' ? 'cron' : 'worker';
        $age = now()->timestamp - $seen;
        $online = (bool) ($heartbeat['running'] ?? false) && $seen > 0
            && $age >= 0 && $age <= self::HEARTBEAT_MAX_AGE;
        $cronMaxAge = max(1, min(60, (int) config('google-contacts.cron_interval_minutes', 5))) * 60
            + self::HEARTBEAT_MAX_AGE;
        return [
            'online' => $online,
            'mode' => $mode,
            'healthy' => $mode === 'cron'
                ? $seen > 0 && $age >= 0 && $age <= $cronMaxAge && ($online || ($heartbeat['completed'] ?? false))
                : $online,
            'last_seen_at' => $seen ?: null,
        ];
    }

    public function tick(GoogleContactsService $service): bool
    {
        // The heartbeat means the process is alive, independently of OAuth or Internet access.
        $this->heartbeat();
        $account = $service->account();
        if (!$account || !$account->isConnected() || $account->isPaused()) {
            return false;
        }
        if (now()->timestamp >= $this->nextSweep) {
            app(GoogleContactsBulkService::class)->advance($service);
            $service->enqueuePending();
            $this->nextSweep = now()->timestamp + 300;
        }
        return true;
    }
}
