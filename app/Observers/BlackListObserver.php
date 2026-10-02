<?php

namespace App\Observers;

use App\Models\BlackList;
use App\Service\BlackListService;

class BlackListObserver
{
    public function __construct(private BlackListService $blackListService) {}

    public function saved(BlackList $blackList): void
    {
        if ($blackList->wasRecentlyCreated) {
            $this->blackListService->forgetIp($blackList->ip);

            return;
        }

        if ($blackList->wasChanged('ip')) {
            $original = $blackList->getOriginal('ip');
            if ($original !== null && $original !== $blackList->ip) {
                $this->blackListService->forgetIp($original);
            }
            $this->blackListService->forgetIp($blackList->ip);

            return;
        }

        if ($blackList->wasChanged('deleted_at')) {
            $this->blackListService->forgetIp($blackList->ip);
        }

        // else: timestamp-only touch (e.g., from BlackListLog::$touches) — no-op
    }

    public function deleted(BlackList $blackList): void
    {
        $this->blackListService->forgetIp($blackList->ip);
    }

    public function restored(BlackList $blackList): void
    {
        $this->blackListService->forgetIp($blackList->ip);
    }
}
