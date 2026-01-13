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
    


    $base_colors = [
        '#ff89bB', 
        '#f052f6', 
        '#7cf48b', 
//        '#ffe119',  //yellow
        '#6383f8', 
        '#f58231', 
        '#b13ed4', 
        '#42d4f4', 
        '#bfef45', 
        '#fabed4', 
        '#66b9b0', 
        '#dcbeff', 
        '#cA9354', 
        '#fffac8', 
        '#c05050', 
        '#aaffc3', 
        '#a0a030', 
        '#ffd8b1', 
        ];
    $gradient_colors = [
        '#ffddcc', 
        '#ffaaff', 
        '#aaffaa', 
        '#ccccff', 
        '#bbddff', 
        '#fabed4', 
        '#88ccff', 
        '#fAc394', 
        '#b0b040', 
        ];

    $colors = [];
    foreach($base_colors as $color) {
        $colors[] = $color;
    } 
    foreach($base_colors as $color) {
        $colors[] = "linear-gradient(90deg, {$color}, #ffffff);";
    } 
    foreach($gradient_colors as $color) {
        $colors[] = "repeating-linear-gradient(90deg, {$color}, {$color} 15px, #ffffff 15px, #ffffff 30px);";
    }
    foreach($base_colors as $color) {
        $colors[] = "radial-gradient(#ffffff 20%, {$color});";
    } 
    foreach($gradient_colors as $color) {
        $colors[] = "repeating-linear-gradient(90deg, {$color}, {$color} 20%, #ffffff 20%, #ffffff 40%);";
    }
    foreach($gradient_colors as $color) {
        $colors[] = "repeating-linear-gradient(135deg, {$color}, {$color} 10px, #ffffff 1px, #ffffff 12px);";
    }
    foreach($base_colors as $color) {
        $colors[] = "linear-gradient(90deg, #ffffff, {$color});";
    } 
/*        'repeating-linear-gradient(90deg, #66ffff, #66ffff 15px, #bbffff 15px, #bbffff 30px);',
        'repeating-linear-gradient(90deg, #ff66ff, #ff66ff 15px, #ffbbff 15px, #ffbbff 30px);',
        'repeating-linear-gradient(90deg, #ff6666, #ff6666 15px, #ffbbbb 15px, #ffbbbb 30px);',
        'repeating-linear-gradient(90deg, #6666ff, #6666ff 15px, #bbbbff 15px, #bbbbff 30px);',
        'repeating-linear-gradient(90deg, #66ff66, #66ff66 15px, #bbffbb 15px, #bbffbb 30px);',

        'repeating-linear-gradient(45deg, #66ffff, #66ffff 15px, #bbffff 15px, #bbffff 30px);',
        'repeating-linear-gradient(45deg, #ff66ff, #ff66ff 15px, #ffbbff 15px, #ffbbff 30px);',
        'repeating-linear-gradient(45deg, #ff6666, #ff6666 15px, #ffbbbb 15px, #ffbbbb 30px);',
        'repeating-linear-gradient(45deg, #6666ff, #6666ff 15px, #bbbbff 15px, #bbbbff 30px);',
        'repeating-linear-gradient(45deg, #66ff66, #66ff66 15px, #bbffbb 15px, #bbffbb 30px);',

        'repeating-linear-gradient(90deg, #bbffff, #bbffff 15px, #ffffff 15px, #ffffff 30px);',
        'repeating-linear-gradient(90deg, #ffbbff, #ffbbff 15px, #ffffff 15px, #ffffff 30px);',
        'repeating-linear-gradient(90deg, #ffbbbb, #ffbbbb 15px, #ffffff 15px, #ffffff 30px);',
        'repeating-linear-gradient(90deg, #bbbbff, #bbbbff 15px, #ffffff 15px, #ffffff 30px);',
        'repeating-linear-gradient(90deg, #bbffbb, #bbffbb 15px, #ffffff 15px, #ffffff 30px);',

        'repeating-linear-gradient(45deg, #bbffff, #bbffff 15px, #ffffff 15px, #ffffff 30px);',
        'repeating-linear-gradient(45deg, #ffbbff, #ffbbff 15px, #ffffff 15px, #ffffff 30px);',
        'repeating-linear-gradient(45deg, #ffbbbb, #ffbbbb 15px, #ffffff 15px, #ffffff 30px);',
        'repeating-linear-gradient(45deg, #bbbbff, #bbbbff 15px, #ffffff 15px, #ffffff 30px);',
        'repeating-linear-gradient(45deg, #bbffbb, #bbffbb 15px, #ffffff 15px, #ffffff 30px);',

        

        '#bbffff',
        '#ffbbff',
//        '#ffffbb', // Yellow
        '#ffbbbb',
        '#bbbbff',
        '#bbffbb',

        '#66ffff',
        '#ff66ff',
//        '#ffff66', // Yellow
        '#ff6666',
        '#6666ff',
        '#66ff66',

//        'repeating-linear-gradient(90deg, #ddffff, #ddffff 20px, #ffffff 20px, #ffffff 40px);',
//        'repeating-linear-gradient(45deg, #bbffff, #bbffff 10px, #ffffff 10px, #ffffff 20px);',
//        'repeating-linear-gradient(135deg, #bbffff, #bbffff 10px, #ffffff 10px, #ffffff 20px);',
//        'repeating-linear-gradient(180deg, #bbffff, #bbffff 20px, #ffffff 20px, #ffffff 40px);',
        '#ffbbff',
//        '#ffffbb', // Yellow
        '#ffbbbb',
        '#bbbbff',
        '#bbffbb',

        '#66ffff',
        '#ff66ff',
//        '#ffff66', // Yellow
        '#ff6666',
        '#6666ff',
        '#66ff66',

        ]; */

    return array('stat'=>'ok', 'colors'=>$colors);
}
?>
