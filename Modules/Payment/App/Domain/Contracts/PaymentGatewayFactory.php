<?php
namespace Modules\Payment\App\Domain\Contracts;

use Modules\Payment\App\Application\Services\AbstractGatewayService;
use Modules\Payment\App\Application\Services\BikashService;
use Modules\Payment\App\Application\Services\NagadService;
use Modules\Payment\App\Application\Services\SslCommerzService;
use Modules\Payment\App\Application\Services\PayPalService;
use Modules\Payment\App\Application\Services\AamarPayService;
use Modules\Payment\App\Application\Services\ShurjoPayService;

class PaymentGatewayFactory
{
    /**
     * Resolve a gateway service instance.
     *
     * @param string $gatewayName The name defined in config/payment.php (e.g., 'bikash')
     * @return AbstractGatewayService
     * @throws \InvalidArgumentException
     */
    public function make(string $gatewayName): AbstractGatewayService
    {
        switch (strtolower($gatewayName)) {
            case 'bikash':
                return new BikashService();
            case 'nagad':
                return new NagadService();
            case 'sslcommerz':
                return new SslCommerzService();
            case 'paypal':
                return new PayPalService();
            case 'aamarpay':
                return new AamarPayService();
            case 'shurjopay':
                return new ShurjoPayService();
            default:
                throw new \InvalidArgumentException("Unsupported payment gateway [{$gatewayName}]");
        }
    }
}
?>
