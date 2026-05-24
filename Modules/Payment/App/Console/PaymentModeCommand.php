<?php

namespace Modules\Payment\App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PaymentModeCommand extends Command
{
    protected $signature = 'payment:mode {mode? : The mode to set (api or web)}';
    protected $description = 'Set the global payment module mode to API or Web';

    public function handle()
    {
        $mode = $this->argument('mode');
        
        if (!in_array($mode, ['api', 'web'])) {
            $mode = $this->choice('Which mode do you want to enable globally for the Payment module?', ['api', 'web'], 0);
        }

        $this->setEnvValue('PAYMENT_MODE', $mode);
        $this->info("Successfully set Payment module mode to: " . strtoupper($mode));
        $this->info("Remember to clear your config cache if it is cached: php artisan config:clear");
    }

    protected function setEnvValue($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $content = file_get_contents($path);

            if (strpos($content, $key . '=') !== false) {
                // Update existing key
                $content = preg_replace('/^' . $key . '=.*/m', $key . '=' . $value, $content);
            } else {
                // Add new key
                $content .= "\n" . $key . '=' . $value . "\n";
            }

            file_put_contents($path, $content);
        }
    }
}
