<?php
return [
    // No capture, DDL or purge on application boot. Explicit TEST-only opt-in.
    'enabled'=>env('HW24_PRICE_ARCHIVE_ENABLED',false)===true||env('HW24_PRICE_ARCHIVE_ENABLED')==='true',
];
