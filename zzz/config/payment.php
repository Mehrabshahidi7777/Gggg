<?php

return [

    'default' => 'sep',

    'drivers' => [

        'sep' => [
            'apiGetToken' => 'https://sep.shaparak.ir/onlinepg/onlinepg',
            'apiPaymentUrl' => 'https://sep.shaparak.ir/OnlinePG/OnlinePG',
            'apiVerificationUrl' => 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction',
            'terminalId' => env('SEP_TERMINAL_ID'),
            'callbackUrl' => env('APP_URL') . '/payments/sep/order',
            'description' => 'پرداخت درگاه سامان',
            'currency' => 'T',
        ],

    ],

    'map' => [
        'sep' => \Shetabit\Multipay\Drivers\SEP\SEP::class,
    ],

];
