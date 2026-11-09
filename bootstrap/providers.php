<?php

use App\Providers\AppServiceProvider;
use PhpMqtt\Client\MqttClientServiceProvider;

return [
    AppServiceProvider::class,
    MqttClientServiceProvider::class,
];
