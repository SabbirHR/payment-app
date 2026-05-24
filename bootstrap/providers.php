<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    Modules\Payment\Providers\PaymentServiceProvider::class,
];
