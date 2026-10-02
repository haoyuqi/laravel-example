<?php

namespace App\Console\Commands;

use App\Models\VisitorStatistics;
use App\Support\RedisFailureLogger;
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
            if ($uvCount === 0 || $pvCount === null || $pvCount === false) {
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

        // Keep the existing append-only behavior; deduplication belongs to #141.
        DB::transaction(function () use ($dateStr, $uvCount, $pvCount): void {
            $this->saveStatistic('uv', $dateStr, $uvCount);
            $this->saveStatistic('pv', $dateStr, $pvCount);
        });

        return Command::SUCCESS;
    }

    /**
     * Save a snapshot without modifying historical or soft-deleted records.
     */
    protected function saveStatistic(string $type, string $date, int $count): void
    {
        $statistic = new VisitorStatistics;
        $statistic->type = $type;
        $statistic->date = $date;
        $statistic->count = $count;
        $statistic->save();
    }
}
