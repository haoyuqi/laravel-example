<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ClearFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear files';

    protected $paths;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->paths = [
            ['path' => storage_path('logs'), 'expires' => 604800],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach ($this->paths as $item) {
            $files = collect(File::files($item['path']))
                ->filter(function ($file, $key) use ($item) {
                    return (
                        basename($file) != '.gitignore' &&
                        time() - File::lastModified($file) > $item['expires']
                    );
                });

            if ($files->isNotEmpty()) {
                info('Clear files.');
                info($files->map(function ($item) {
                    return $item->getFilename();
                })->toArray());

                File::delete($files->map(function ($item) {
                    return $item->getPathname();
                })->toArray());
            }
        }
    }
}
