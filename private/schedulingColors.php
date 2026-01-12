<?php
//
// Description
// -----------
// This function returns a list of colors to use when displaying scheduler
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_schedulingColors(&$ciniki, $tnid, $args) {

    $colors = [
        'dfffff',
        'ffdfff',
        'ffffdf',
        'ffdfdf',
        'dfdfff',
        'dfffdf',
        'dfdfdf',

        'bfffff',
        'ffbfff',
//        'ffffbf', // Highlight color
        'ffbfbf',
        'bfbfff',
        'bfffbf',
        'bfbfbf',

        '9fffff',
        'ff9fff',
//        'ffff9f', // Highlight color
        'ff9f9f',
        '9f9fff',
        '9fff9f',
        '9f9f9f',

        '7fffff',
        'ff7fff',
        'ffff7f',
        'ff7f7f',
        '7f7fff',
        '7fff7f',
        '7f7f7f',

        'dfff7f',
        'df7fff',
        'df7f7f',
        '7fdfff',
        'ffdf7f',
        '7fdf7f', 
        '7fffdf',
        'ff7fdf',
        '7f7fdf',
        '7fdfdf',
        'dfdf7f',
        'df7fdf',

        'dfff5f',
        'df5fff',
        'df5f5f',
        '5fdfff',
        'ffdf5f',
        '5fdf5f',
        '5fffdf',
        'ff5fdf',
        '5f5fdf',
        '5fdfdf',
        'dfdf5f',
        'df5fdf',

        'bfff7f',
        'bf7fff',
        'bf7f7f',
        '7fbfff',
        'ffbf7f',
        '7fbf7f',
        '7fffbf',
        'ff7fbf',
        '7f7f7f',
        '7fbfbf',
        'bfbf7f',
        'bf7fbf',

        ];

    return array('stat'=>'ok', 'colors'=>$colors);
}
?>
