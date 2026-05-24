<?php
namespace Modules\Payment\App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Payment\App\Domain\Contracts\PaymentGatewayInterface;
use Modules\Payment\App\Domain\Contracts\PaymentGatewayFactory;
use Modules\Payment\App\Domain\Contracts\TransactionRepository;
use Modules\Payment\App\Infrastructure\Repositories\EloquentTransactionRepository;
use Modules\Payment\App\Application\Services\ShurjoPayService;

class PaymentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/payment.php', 'payment'
        );

        $this->app->bind(ShurjoPayService::class, function ($app) {
            return new ShurjoPayService();
        });

        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            $gateway = config('payment.default');
            return (new PaymentGatewayFactory())->make($gateway);
        });

        $this->app->bind(
            TransactionRepository::class,
            EloquentTransactionRepository::class
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/payment.php' => config_path('payment.php'),
            __DIR__.'/../../.env-payment'      => base_path('.env-payment'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        
        if (file_exists(__DIR__.'/../../routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        }

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'payment');
        
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
