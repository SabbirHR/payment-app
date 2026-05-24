<?php
namespace Modules\Payment\App\Domain\Exceptions;

use Exception;

class PaymentFailedException extends Exception
{
    protected $message = 'Payment processing failed.';
}
?>
