<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CapacitorConfig extends Command
{
    protected $signature = 'capacitor:config {--url= : Override APP_URL}';

    protected $description = 'Generate capacitor.config.json from APP_URL env';

    public function handle(): int
    {
        $url = $this->option('url') ?? config('app.url');

        if (empty($url) || $url === 'http://localhost') {
            $this->warn('APP_URL is not set or still default (http://localhost).');
            $this->warn('Set APP_URL in your .env file or pass --url=https://your-domain.com');

            if (! $this->option('url')) {
                return self::FAILURE;
            }
        }

        $config = [
            'appId' => 'com.wevlra.vapegeh',
            'appName' => 'VapeGeh',
            'webDir' => 'www',
            'server' => [
                'url' => $url,
                'cleartext' => true,
            ],
        ];

        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

        // Write to mobile/
        $mobilePath = base_path('mobile/capacitor.config.json');
        file_put_contents($mobilePath, $json);
        $this->info("✓ Updated {$mobilePath}");

        // Write to Android assets
        $androidPath = base_path('mobile/android/app/src/main/assets/capacitor.config.json');
        file_put_contents($androidPath, $json);
        $this->info("✓ Updated {$androidPath}");

        $this->line('');
        $this->line("   Server URL: {$url}");

        return self::SUCCESS;
    }
}
