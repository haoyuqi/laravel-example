<?php

namespace App\Console\Commands;

use Haoyuqi\DownloadBingWallpaper\BingWallpaper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DownloadBingWallpaperCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:bing-wallpaper';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download bing wallpaper';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(BingWallpaper $bingWallpaper)
    {
        $file_path = storage_path('bing-wallpaper');
        $file_name = today()->toDateString() . '.png';
        if (!File::exists($file_path)) {
            File::makeDirectory($file_path);
        }

        $res = $bingWallpaper->download($file_path . '/' . $file_name);

        $info = 'Download bing wallpaper ' . $file_name . ' ' . ($res ? 'success' : 'failed') . '.';
        info($info);
        $this->info($info);
    }
}
