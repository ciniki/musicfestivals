<?php
//
// Description
// ===========
// This method will return all the information about an section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the section is attached to.
// section_id:          The ID of the section to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_sectionClassesUpdate($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
        'earlybird_fee_update'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Earlybird Fee Update'),
        'fee_update'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Fee Update'),
        'virtual_fee_update'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Virtual Fee Update'),
        'earlybird_plus_fee_update'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Earlybird Plus Fee Update'),
        'plus_fee_update'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Plus Fee Update'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.sectionClasses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load the festival details
    //
    $strsql = "SELECT ciniki_musicfestivals.id, "
        . "ciniki_musicfestivals.name, "
        . "ciniki_musicfestivals.permalink, "
        . "ciniki_musicfestivals.start_date, "
        . "ciniki_musicfestivals.end_date, "
        . "ciniki_musicfestivals.status, "
        . "ciniki_musicfestivals.flags, "
        . "ciniki_musicfestivals.earlybird_date, "
        . "ciniki_musicfestivals.live_date, "
        . "ciniki_musicfestivals.virtual_date, "
        . "ciniki_musicfestivals.edit_end_dt, "
        . "ciniki_musicfestivals.upload_end_dt "
        . "FROM ciniki_musicfestivals "
        . "WHERE ciniki_musicfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.499', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.500', 'msg'=>'Unable to find requested festival'));
    }
    $festival = $rc['festival'];
   
    //
    // Get the list of classes in the section
    //
    $strsql = "SELECT classes.id, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee, "
        . "classes.earlybird_plus_fee, "
        . "classes.plus_fee "
        . "FROM ciniki_musicfestival_categories AS categories "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
        . "GROUP BY classes.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.501', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
    }
    $classes = isset($rc['classes']) ? $rc['classes'] : array();

    foreach($classes as $class) {
        $update_args = array();
        if( isset($args['fee_update']) && $args['fee_update'] != '' && $args['fee_update'] != 0 ) {
            $update_args['fee'] = $class['fee'] + $args['fee_update'];
        }

        //
        // Update virtual fees
        //
        if( ($festival['flags']&0x04) == 0x04 
            && isset($args['virtual_fee_update']) && $args['virtual_fee_update'] != '' && $args['virtual_fee_update'] != 0 
            ) {
            $update_args['virtual_fee'] = $class['virtual_fee'] + $args['virtual_fee_update'];
        }

        //
        // Update earlybird fees
        //
        if( ($festival['flags']&0x20) == 0x20 
            && isset($args['earlybird_fee_update']) && $args['earlybird_fee_update'] != '' && $args['earlybird_fee_update'] != 0 
            ) {
            $update_args['earlybird_fee'] = $class['earlybird_fee'] + $args['earlybird_fee_update'];
        }

        //
        // Update plus fees
        //
        if( ($festival['flags']&0x10) == 0x10 
            && isset($args['plus_fee_update']) && $args['plus_fee_update'] != '' 
            && $args['plus_fee_update'] != 0 
            ) {
            $update_args['plus_fee'] = $class['plus_fee'] + $args['plus_fee_update'];
        }
        if( ($festival['flags']&0x30) == 0x30 
            && isset($args['earlybird_plus_fee_update']) && $args['earlybird_plus_fee_update'] != '' 
            && $args['earlybird_plus_fee_update'] != 0 
            ) {
            $update_args['earlybird_plus_fee'] = $class['earlybird_plus_fee'] + $args['earlybird_plus_fee_update'];
        }

        if( count($update_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.class', $class['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.772', 'msg'=>'Unable to update the class', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
