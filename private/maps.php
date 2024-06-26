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
        'participation'=>array(
            '0' => 'Live',
            '1' => 'Virtual',
            '2' => 'Plus',
            '3' => 'Virtual Plus',
        ),
        'provincials_status'=>array(
            '0' => '',
            '30' => 'Recommended',
            '50' => 'Accepted',
            '70' => 'Ineligible',
            '90' => 'Declined',
        ),
        'provincials_position'=>array(
            '1' => '1st Recommendation',
            '2' => '2nd Recommendation',
            '3' => '3rd Recommendation',
            '101' => '1st Alternative',
            '102' => '2nd Alternative',
            '103' => '3rd Alternative',
        ),
    );
    $maps['message'] = array(
        'status'=>array(
            '10'=>'Draft',
            '30'=>'Scheduled',
            '50'=>'Sent',
        ),
    );
    $maps['schedulesection'] = array(
        'flags'=>array(
            0x01 => 'Schedule Released',
            0x02 => 'Comments Released',
            0x04 => 'Certificates Released',
            0x10 => 'Schedule Published',
            0x20 => 'Results Published',
        ),
    );
    $maps['scheduledivision'] = array(
        'flags'=>array(
            0x02 => 'Comments Released',
            0x04 => 'Certificates Released',
            0x20 => 'Results Published',
        ),
    );
    $maps['recommendationentry'] = array(
        'position'=>array(
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            101 => '1st Alternate',
            102 => '2nd Alternate',
            103 => '3rd Alternate',
        ),
    );

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
