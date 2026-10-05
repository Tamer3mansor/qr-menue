<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Plan Price
    |--------------------------------------------------------------------------
    |
    | This application has no billing integration, so nothing in the database
    | records what a customer actually pays. The super admin dashboard shows an
    | estimate of active customers multiplied by this price.
    |
    | It defaults to 0 on purpose: until you set SUBSCRIPTION_PLAN_PRICE the
    | estimate reads zero instead of inventing revenue.
    |
    */

    'plan_price' => (float) env('SUBSCRIPTION_PLAN_PRICE', 0),

];
