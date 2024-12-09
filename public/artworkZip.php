<?php
//
// Description
// -----------
// This method will return a zip file with the artwork added.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_artworkZip(&$ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'schedulesection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.artworkZip');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Get the artwork for the festival
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.uuid, "
        . "registrations.display_name, "
        . "registrations.title1, "
        . "registrations.title2, "
        . "registrations.title3, "
        . "registrations.title4, "
        . "registrations.title5, "
        . "registrations.title6, "
        . "registrations.title7, "
        . "registrations.title8, "
        . "registrations.artwork1, "
        . "registrations.artwork2, "
        . "registrations.artwork3, "
        . "registrations.artwork4, "
        . "registrations.artwork5, "
        . "registrations.artwork6, "
        . "registrations.artwork7, "
        . "registrations.artwork8, "
        . "sections.name AS section_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "timeslots.name AS timeslot_name, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time_text ";
    } else {
        $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text ";
    }
    $strsql .= "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "registrations.timeslot_id = timeslots.id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "timeslots.sdivision_id = divisions.id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
            . "divisions.ssection_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        $strsql .= "ORDER BY sections.sequence, sections.name, divisions.name, registrations.timeslot_time, registrations.timeslot_sequence, registrations.display_name ";
    } else {
        $strsql .= "ORDER BY sections.sequence, sections.name, divisions.name, timeslots.slot_time, registrations.timeslot_sequence, registrations.display_name ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'section_name', 'division_id', 'division_name', 'slot_time_text', 'display_name', 
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
                'artwork1', 'artwork2', 'artwork3', 'artwork4', 'artwork5', 'artwork6', 'artwork7', 'artwork8',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.762', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();


    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/zipstream-php/src/ZipStream.php');

    $zip = new Pablotron\ZipStream\ZipStream('artwork.zip');
    $prev_division_id = 0;
    foreach($registrations as $reg) {
       
        if( $prev_division_id != $reg['division_id'] ) {
            $reg_num = 1;
            $prev_division_id = $reg['division_id'];
        }
        $files_found = 'no';
        for($i = 1; $i <= 8; $i++) {
            if( isset($reg["title{$i}"]) && isset($reg["artwork{$i}"]) && $reg["artwork{$i}"] != '' ) {
                $extension = preg_replace('/^.*\.([a-zA-Z0-9]+)$/', '$1', $reg["artwork{$i}"]);
                $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/files/'
                    . $reg['uuid'][0] . '/' . $reg['uuid'] . '_artwork' . $i;
                try {
                    $zip->add_file_from_path($reg['section_name'] . '/' . $reg['division_name'] . '/' 
                        . $reg_num . ' - ' . $reg['slot_time_text'] . ' - ' . $reg['display_name'] . ' - ' . $reg["title{$i}"] . ' - ' . $reg["artwork{$i}"], 
                        $storage_filename);
                    $files_found = 'yes';
                } catch(Exception $e) {
                    error_log('Zip Add File: ' . $e->getMessage());
                }
            }
        }
        if( $files_found == 'yes' ) {
            $reg_num++;
        }
    }

    $zip->close();

    return array('stat'=>'exit');
}
?>
