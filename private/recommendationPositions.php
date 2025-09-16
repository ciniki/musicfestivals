<?php
//
// Description
// -----------
// This will return the array with the positions for recommendations
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_recommendationPositions(&$ciniki, $tnid) {

    $positions = [
        '1' => [
            'label'=>'1st Recommendation', 
            'shortlabel' => '1st',
            ],
        '2' => [
            'label'=>'2nd Recommendation',
            'shortlabel' => '2nd',
            ],
        '3' => [
            'label'=>'3rd Recommendation',
            'shortlabel' => '3rd',
            ],
        '101' => [
            'label'=>'1st Alternate',
            'shortlabel' => '1st Alt',
            ],
        '102' => [
            'label'=>'2nd Alternate',
            'shortlabel' => '2nd Alt',
            ],
        '103' => [
            'label'=>'3rd Alternate',
            'shortlabel' => '3rd Alt',
            ],
        '104' => [
            'label'=>'4th Alternate',
            'shortlabel' => '4th Alt',
            ],
        ];

    return array('stat'=>'ok', 'positions'=>$positions);
}
?>
