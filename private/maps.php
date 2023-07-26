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
        '30'=>'Current',
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
            '5'=>'Unpaid',
            '6'=>'Cart',
            '7'=>'E-Transfer Required',
            '10'=>'Applied',
            '50'=>'Paid',
            '60'=>'Cancelled',
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
            '121'=>'Online',
        ),
    );
    $maps['message'] = array(
        'status'=>array(
            '10'=>'Draft',
            '30'=>'Scheduled',
            '50'=>'Sent',
        ),
    );

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
