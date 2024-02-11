<?php
//
// Description
// -----------
// This function will update the festival settings from the supplier array.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// festival_id:     The ID of the festival
// args:            The array to search for the settings in.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_settingsUpdate(&$ciniki, $tnid, $festival_id, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Get the current settings
    //
    $strsql = "SELECT id, uuid, detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'settings', 'fname'=>'detail_key', 'fields'=>array('id', 'uuid', 'detail_key', 'detail_value')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = array();
    if( isset($rc['settings']) ) {
        $settings = $rc['settings'];
    }

    // FIXME: Some of the settings in db ciniki_musicfestivals need to move into settings
    //
    // Check for any settings and add/update
    //
    $valid_settings = array(
        'schedule-division-header-format',
        'schedule-division-header-labels',
        'schedule-names',
        'schedule-titles',
        'schedule-header',
        'schedule-footer',
        'schedule-footerdate',
        'schedule-section-page-break',
        'runsheets-separate-classes',
        'runsheets-class-format',
        'age-restriction-msg',
        'waiver-title',
        'waiver-msg',
        'president-name',
        'inperson-choice-msg',
        'virtual-choice-msg',
        'customer-type-intro-msg',
        'customer-type-parent-button-label',
        'customer-type-teacher-button-label',
        'customer-type-adult-button-label',
        'registration-parent-msg',
        'registration-teacher-msg',
        'registration-adult-msg',
        'registration-participation-label',
        'registration-title-label',
        'registration-composer-label',
        'registration-movements-label',
        'registration-length-label',
        'registration-length-format',
        'competitor-parent-msg',
        'competitor-teacher-msg',
        'competitor-adult-msg',
        'competitor-individual-study-level',
        'competitor-individual-instrument',
        'competitor-individual-age',
        'competitor-individual-age-label',
        'competitor-group-parent-msg',
        'competitor-group-teacher-msg',
        'competitor-group-adult-msg',
        'competitor-group-study-level',
        'competitor-group-instrument',
        'competitor-group-age',
        'competitor-group-age-label',
        );
    foreach($valid_settings as $field) {
        if( isset($args[$field]) ) {
            if( isset($settings[$field]['detail_value']) && $settings[$field]['detail_value'] != $args[$field] ) {
                //
                // Update the setting
                //
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.setting', $settings[$field]['id'],
                    array('detail_value'=>$args[$field]), 
                    0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            } else if( !isset($settings[$field]) ) {
                //
                // Add the setting
                //
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.setting', 
                    array('festival_id'=>$festival_id, 'detail_key'=>$field, 'detail_value'=>$args[$field]), 
                    0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
