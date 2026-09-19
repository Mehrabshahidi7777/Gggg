<?php

if (! function_exists('format_price')) {
    function format_price($price): string
    {
        return $price === null || $price === '' ? 'توافقی' : number_format((float) $price).' تومان';
    }
}
