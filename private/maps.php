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
    $maps['festival'] = array(
        'status'=>array(
            '10'=>'Active',
            '30'=>'Current',
            '50'=>'Past',
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
            '5'=>'Draft',
            '10'=>'Registered',
            '31'=>'Other',
            '32'=>'Other',
            '33'=>'Other',
            '34'=>'Other',
            '35'=>'Other',
            '36'=>'Other',
            '37'=>'Other',
            '38'=>'Other',
            '50'=>'Approved',
            '51'=>'Approved',
            '52'=>'Approved',
            '53'=>'Approved',
            '54'=>'Approved',
            '55'=>'Approved',
            '70'=>'Disqualified',
            '75'=>'Withdrawn',
            '77'=>'No Show',
            '80'=>'Cancelled',
        ),
        'participation'=>array(
            '0' => 'Live',
            '1' => 'Virtual',
            '2' => 'Plus',
            '3' => 'Virtual Plus',
        ),
        'participationinitials'=>array(
            '0' => 'L',
            '1' => 'V',
            '2' => 'P',
            '3' => 'VP',
        ),
        'provincials_status'=>array(
            '0' => '',
            '30' => 'Recommended',
            '35' => 'Invited',
            '50' => 'Accepted',
            '55' => 'Instructions Sent',
            '60' => 'Registered',
            '70' => 'Ineligible',
            '90' => 'Declined',
        ),
        'provincials_position'=>array(
            '0' => '',
            '1' => '1st Recommendation',
            '2' => '2nd Recommendation',
            '3' => '3rd Recommendation',
            '4' => '4th Recommendation',
            '101' => '1st Alternate',
            '102' => '2nd Alternate',
            '103' => '3rd Alternate',
        ),
        'provincials_position_short'=>array(
            '0' => '',
            '1' => '1st',
            '2' => '2nd',
            '3' => '3rd',
            '4' => '4th',
            '101' => 'Alt 1',
            '102' => 'Alt 2',
            '103' => 'Alt 3',
        ),
    );
    $maps['message'] = array(
        'status'=>array(
            '5'=>'Template',
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
    $maps['adjudicator'] = array(
        'flags'=>array(
            0x01 => 'Live',
            0x02 => 'Virtual',
            0x04 => '',
            0x08 => 'Hidden',
        ),
    );
    $maps['recommendation'] = array(
        'status'=>array(
            10 => 'Draft',
            30 => 'Submitted',
            50 => 'Reviewed',
            ),
        );
    $maps['recommendationentry'] = array(
        'position'=>array(
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            4 => '4th',
            101 => '1st Alternate',
            102 => '2nd Alternate',
            103 => '3rd Alternate',
            600 => 'N/A',
        ),
        'position_shortname'=>array(
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            4 => '4th',
            101 => '1st Alt',
            102 => '2nd Alt',
            103 => '3rd Alt',
            600 => 'N/A',
        ),
        'status'=>array(
            10 => 'Recommended',
            20 => 'Issues',
            30 => 'Approved',
            35 => 'Invited',
            40 => 'Accepted',
            45 => 'Instructions Sent',
            50 => 'Registered',
            70 => 'Turned Down',
            80 => 'Already Recommended',
            85 => 'Ineligible',
            90 => 'Expired',
        ),
    );
    $maps['cr'] = array(
        'status'=>array(
            10 => 'Draft',
            20 => 'Submitted',
            30 => 'Reviewing',
            40 => 'Pending Payment',
            50 => 'Pending',
            70 => 'Completed',
            90 => 'Cancelled',
        ),
    );

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
