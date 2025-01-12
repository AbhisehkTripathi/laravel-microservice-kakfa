<?php
return [
    'broker' => env('KAFKA_BROKER', 'kafka:9092'),
    'topic' => env('KAFKA_TOPIC', 'product_updates'),
    'group' => env('KAFKA_GROUP', 'product_group'),
];
