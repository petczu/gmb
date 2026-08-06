<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Image Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default image processing driver that will be
    | used when manipulating or converting images. This driver is always
    | utilized unless another driver is explicitly specified instead.
    |
    | Supported: "gd", "imagick"
    |
    */

    // Imagick: GD cannot decode the HEIC/HEIF photos iPhones upload.
    'default' => env('IMAGE_DRIVER', 'imagick'),

];
