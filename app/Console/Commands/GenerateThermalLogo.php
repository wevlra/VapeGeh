<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:generate-thermal-logo')]
#[Description('Generate monochrome thermal logo from existing logo')]
class GenerateThermalLogo extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $source = public_path('assets/images/logo-stacked-light-tr.png');
        $target = public_path('assets/images/logo-thermal.png');

        if (! file_exists($source)) {
            $this->error('Source logo not found at: '.$source);

            return Command::FAILURE;
        }

        $srcImg = @imagecreatefrompng($source);
        if (! $srcImg) {
            $this->error('Cannot read source logo (try PNG format: logo-wordmark-dark-tr.png).');

            return Command::FAILURE;
        }

        $srcW = imagesx($srcImg);
        $srcH = imagesy($srcImg);

        // target width 384px, maintain aspect ratio
        $dstW = 384;
        $dstH = (int) round($srcH * $dstW / $srcW);

        $dstImg = imagecreatetruecolor($dstW, $dstH);
        imagecolortransparent($dstImg, imagecolorallocatealpha($dstImg, 0, 0, 0, 127));
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);

        // Resize
        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        // Convert to grayscale with threshold (pure black/white)
        for ($x = 0; $x < $dstW; $x++) {
            for ($y = 0; $y < $dstH; $y++) {
                $rgb = imagecolorat($dstImg, $x, $y);
                $a = ($rgb >> 24) & 0x7F; // alpha
                if ($a >= 100) {
                    // Transparent pixel — skip
                    continue;
                }
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = (int) round(0.299 * $r + 0.587 * $g + 0.114 * $b);
                // Threshold: dark → black (0), light → white (255)
                $bw = $gray < 128 ? 0 : 255;
                imagesetpixel($dstImg, $x, $y, imagecolorallocate($dstImg, $bw, $bw, $bw));
            }
        }

        // Clear any fully transparent background → white
        $white = imagecolorallocate($dstImg, 255, 255, 255);
        imagecolortransparent($dstImg, -1);
        for ($x = 0; $x < $dstW; $x++) {
            for ($y = 0; $y < $dstH; $y++) {
                $rgba = imagecolorat($dstImg, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                if ($alpha >= 100) {
                    imagesetpixel($dstImg, $x, $y, $white);
                }
            }
        }

        imagepng($dstImg, $target, 9);
        imagedestroy($srcImg);
        imagedestroy($dstImg);

        $this->info("Thermal logo generated: {$target} ({$dstW}x{$dstH})");

        return Command::SUCCESS;
    }
}
