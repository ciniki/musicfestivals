<?php
//
// Description
// -----------
// This method will return the list of Schedule Sections for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Schedule Section for.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleSectionList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleSectionList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of schedulesections
    //
    $strsql = "SELECT ssections.id, "
        . "ssections.festival_id, "
        . "ssections.name, "
        . "ssections.sequence, "
        . "ssections.flags "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY ssections.sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'schedulesections', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'name', 'sequence', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['schedulesections']) ) {
        $schedulesections = $rc['schedulesections'];
        $schedulesection_ids = array();
        foreach($schedulesections as $iid => $schedulesection) {
            $schedulesection_ids[] = $schedulesection['id'];
            $schedulesections[$iid]['lv_flags_text'] = 'Live/Virtual';
            if( ($schedulesection['flags']&0x0300) == 0x0100 ) {
                $schedulesections[$iid]['lv_flags_text'] = 'Live';
            } elseif( ($schedulesection['flags']&0x0300) == 0x0200 ) {
                $schedulesections[$iid]['lv_flags_text'] = 'Virtual';
            }
            $text = '';
            if( ($schedulesection['flags']&0x01) == 0x01 ) {
                $text .= ($text != '' ? ', ' : '') . 'Released';
            }
            if( ($schedulesection['flags']&0x10) == 0x10 ) {
                $text .= ($text != '' ? ', ' : '') . 'Published';
            }
            $schedulesections[$iid]['schedule_flags_text'] = $text;
            $text = '';
            if( ($schedulesection['flags']&0x02) == 0x02 ) {
                $text .= ($text != '' ? ', ' : '') . 'Comments Released';
            }
            if( ($schedulesection['flags']&0x04) == 0x04 ) {
                $text .= ($text != '' ? ', ' : '') . 'Certificates Released';
            }
            if( ($schedulesection['flags']&0x20) == 0x20 ) {
                $text .= ($text != '' ? ', ' : '') . 'Published';
            }
            $schedulesections[$iid]['results_flags_text'] = $text;
            $schedulesections[$iid]['photos_flags_text'] = '';
            if( ($schedulesection['flags']&0x40) == 0x40 ) {
                $schedulesections[$iid]['photos_flags_text'] = 'Yes';
            }
        }
    } else {
        $schedulesections = array();
        $schedulesection_ids = array();
    }

    return array('stat'=>'ok', 'sections'=>$schedulesections, 'nplist'=>$schedulesection_ids);
}
?>
