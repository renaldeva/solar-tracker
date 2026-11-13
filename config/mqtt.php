<?php

return [
    'host' => env('MQTT_HOST', '10.132.15.43'),
    'port' => (int) env('MQTT_PORT', 1884),
    'username' => env('MQTT_USERNAME', env('MQTT_AUTH_USERNAME', 'device_ST-DEMO-001')),
    'password' => env('MQTT_PASSWORD', env('MQTT_AUTH_PASSWORD', 'LHm8PSGX0wff9CkZ')),
    'device_uid' => env('MQTT_DEVICE_UID', 'ST-DEMO-001'),
    'client_id' => env('MQTT_CLIENT_ID', 'sunchase-backend-'.gethostname()),
];

