<?php

return [
    // Products with stock strictly BELOW this value are "low stock".
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 10),
];
