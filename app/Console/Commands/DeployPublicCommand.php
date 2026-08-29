<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('deploy:public {destination : The destination directory (e.g. /home/user/public_html)}')]
#[Description('Copy public folder to a destination directory and adjust index.php paths for cPanel')]
class DeployPublicCommand extends Command
{
    public function handle()
    {
        $destination = rtrim($this->argument('destination'), '/');
        $publicPath = public_path();
        $projectName = basename(base_path());

        if (! File::exists($destination)) {
            if (! $this->confirm("Destination directory '{$destination}' not found. Create it?")) {
                $this->info('Aborted.');

                return;
            }
            File::makeDirectory($destination, 0755, true);
        }

        $this->info("Copying files from {$publicPath} to {$destination}...");

        File::copyDirectory($publicPath, $destination);

        $indexPath = $destination.'/index.php';

        if (File::exists($indexPath)) {
            $content = File::get($indexPath);

            // Change __DIR__.'/../ to __DIR__.'/../project-name/
            $content = str_replace("__DIR__.'/../", "__DIR__.'/../{$projectName}/", $content);

            File::put($indexPath, $content);
            $this->info("The index.php file has been adjusted (added /../{$projectName}/ folder).");
        } else {
            $this->warn('The index.php file was not found in the destination directory!');
        }

        $this->info('Done! The public folder has been deployed successfully.');
    }
}
