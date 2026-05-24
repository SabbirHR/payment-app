<?php
namespace Modules\Payment\App\Application\Services;

use Modules\Payment\App\Domain\Contracts\PaymentGatewayInterface;
use Modules\Payment\App\Domain\Exceptions\PaymentFailedException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

abstract class AbstractGatewayService implements PaymentGatewayInterface
{
    /** @var Client */
    protected $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client();
    }

    // Concrete gateways must implement the three methods defined in the interface.
    abstract public function pay(\Modules\Payment\App\Domain\DTO\PaymentRequestDto $request): array;
    abstract public function validateTransaction(string $validationId): array;
    abstract public function refund(string $transactionId, float $amount): array;

    /**
     * Helper to send HTTP requests and wrap errors.
     */
    protected function sendRequest(string $method, string $url, array $options = []): array
    {
        try {
            $response = $this->httpClient->request($method, $url, $options);
            $content = $response->getBody()->getContents();
            $decoded = json_decode($content, true);
            
            if ($decoded === null) {
                // If the response is not valid JSON, throw an exception
                throw new PaymentFailedException("Invalid JSON response from gateway: " . substr($content, 0, 100));
            }
            
            return $decoded;
        } catch (GuzzleException $e) {
            throw new PaymentFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
