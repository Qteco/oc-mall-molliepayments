<?php
return [
    'plugin' => [
        'name' => 'Mollie paiements pour Mall',
        'description' => 'Mollie moyen de paiement pour le plugin OFFLINE.Mall'
    ],
    'settings' => [
        'mollie_mode' => 'Mode',
        'mollie_mode_label' => 'Attention: Le mode en direct traitera les paiements réels',
        'test_api_key' => 'Clé API de test de Mollie',
        'test_api_key_label' => 'Ressemble à "test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"',
        'live_api_key' => 'Clé API de live de Mollie',
        'live_api_key_label' => 'Ressemble à "live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"',
        'orders_page' => 'Page des commandes',
        'orders_page_label' => 'Les clients seront redirigés vers cette page après le paiement, exemple: http://yourwebsite.com/account/orders',
    ],
    'messages' => [
        'order_number' => 'Commande #',
        'payment_paid' => 'Le paiement a été effectué avec succès.',
        'payment_failed' => 'Le paiement n\'a pas été effectué avec succès.',
        'payment_canceled' => 'Le paiement a été annulé.',
        'payment_expired' => 'La page de paiement a été abandonnée et le paiement a expiré.',
    ],
];
