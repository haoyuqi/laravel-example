<?php

namespace App\Console\Commands;

use App\Models\VisitorStatistics;
use App\Support\RedisFailureLogger;
use App\Support\VisitorStatisticsRetention;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
            $uvCount = (int) Redis::scard('uv_set_'.$dateStr);
            $pvCount = Redis::get('pv_count_'.$dateStr);

            // Redis removes empty sets; a zero UV count means a missing snapshot.
            if ($uvCount === 0 || filter_var($pvCount, FILTER_VALIDATE_INT) === false || (int) $pvCount < 0) {
                $this->warn('Visits count is incomplete; database statistics were left unchanged.');

                return Command::FAILURE;
            }

            $pvCount = (int) $pvCount;
        } catch (\Throwable $e) {
            RedisFailureLogger::report('failed to retrieve visits count from Redis', [
                'date' => $dateStr,
                'error' => $e->getMessage(),
            ]);

            $this->warn('Failed to retrieve visits count from Redis: '.$e->getMessage());

            return Command::FAILURE;
        }

        DB::transaction(function () use ($dateStr, $uvCount, $pvCount): void {
            $this->saveStatistic('uv', $dateStr, $uvCount);
            $this->saveStatistic('pv', $dateStr, $pvCount);
        });

        // Keep the snapshot available for retries, without extending its lifetime.
        try {
            Redis::expireat('uv_set_'.$dateStr, VisitorStatisticsRetention::expiresAt($yesterday));
            Redis::expireat('pv_count_'.$dateStr, VisitorStatisticsRetention::expiresAt($yesterday));
        } catch (\Throwable $e) {
            RedisFailureLogger::report('failed to expire visits count in Redis', [
                'date' => $dateStr,
                'error' => $e->getMessage(),
            ]);
            $this->warn('Statistics were saved, but Redis expiration failed. Retry the command.');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * The unique type/date key makes concurrent retries safe, including trashed rows.
     */
    protected function saveStatistic(string $type, string $date, int $count): void
    {
        VisitorStatistics::upsert([
            ['type' => $type, 'date' => $date, 'count' => $count, 'deleted_at' => null],
        ], ['type', 'date'], ['count', 'deleted_at', 'updated_at']);
    }
}
