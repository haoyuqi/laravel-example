<?php

namespace App\Console\Commands;

use App\Models\VisitorStatistics;
use App\Support\RedisFailureLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class SaveVisitsCountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save:visits-count';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store PV and UV data into database.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $yesterday = now()->subDay();
        $dateStr = $yesterday->toDateString();

        try {
            $uvCount = (int) (Redis::scard('uv_set_'.$dateStr) ?: 0);
            $pvCount = (int) (Redis::get('pv_count_'.$dateStr) ?: 0);
        } catch (\Throwable $e) {
            RedisFailureLogger::report('failed to retrieve visits count from Redis', [
                'date' => $dateStr,
                'error' => $e->getMessage(),
            ]);

            $this->warn('Failed to retrieve visits count from Redis: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->saveStatistic('uv', $dateStr, $uvCount);
        $this->saveStatistic('pv', $dateStr, $pvCount);

        return Command::SUCCESS;
    }

    /**
     * Save visitor statistic idempotently, restoring soft-deleted records if present.
     */
    protected function saveStatistic(string $type, string $date, int $count): void
    {
        $statistic = VisitorStatistics::withTrashed()->firstOrNew([
            'type' => $type,
            'date' => $date,
        ]);

        if ($statistic->trashed()) {
            $statistic->restore();
        }

        $statistic->count = $count;
        $statistic->save();
    }
}
