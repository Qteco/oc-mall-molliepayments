<?php
return [
    'plugin' => [
        'name' => 'Mollie betalingen voor Mall',
        'description' => 'Mollie betaalprovider voor de OFFLINE.Mall plugin'
    ],
    'settings' => [
        'mollie_mode' => 'Modus',
        'mollie_mode_label' => 'Let op: Live modus zal echte betalingen verwerken',
        'test_api_key' => 'Mollie test API sleutel',
        'test_api_key_label' => 'Ziet er uit als "test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"',
        'live_api_key' => 'Mollie live API sleutel',
        'live_api_key_label' => 'Ziet er uit als "live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"',
        'orders_page' => 'Bestellingen pagina',
        'orders_page_label' => 'Klanten worden na het afrekenen doorgestuurd naar deze pagina, bijvoorbeeld: http://yourwebsite.com/account/orders',
    ],
    'messages' => [
        'order_number' => 'Bestelling #',
        'payment_paid' => 'De betaling is succesvol afgerond.',
        'payment_failed' => 'De betaling is niet succesvol afgerond.',
        'payment_canceled' => 'De betaling is geannuleerd.',
        'payment_expired' => 'De betalingspagina is achtergelaten en de betaling is verlopen.',
    ],
];
