<?php
//
// Description
// -----------
// The mappings of int fields to text.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_maps($ciniki) {
    $maps = array();
    $maps['festival'] = array('status'=>array(
        '10'=>'Active',
        '60'=>'Archived',
        ));
    $maps['registration'] = array(
        'rtype'=>array(
            '30'=>'Individual',
            '50'=>'Duet',
            '60'=>'Trio',
            '90'=>'Ensemble',
        ),
        'status'=>array(
            '10'=>'Applied',
            '50'=>'Paid',
        ),
        'payment_type'=>array(
            '10'=>'Paypal',
            '20'=>'Square',
            '50'=>'Visa',
            '55'=>'Mastercard',
            '100'=>'Cash',
            '102'=>'Cheque',
            '110'=>'Email',
            '120'=>'Other',
        ),
    );

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
