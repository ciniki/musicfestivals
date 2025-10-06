<?php
//
// Description
// ===========
// This method will return all the information about an approved title list.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the approved title list is attached to.
// list_id:          The ID of the approved title list to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_titleListGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'list_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Approved Title List'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.titleListGet');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Approved Title List
    //
    if( $args['list_id'] == 0 ) {
        $titlelist = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'flags'=>'0',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Approved Title List
    //
    else {
        $strsql = "SELECT ciniki_musicfestivals_titlelists.id, "
            . "ciniki_musicfestivals_titlelists.name, "
            . "ciniki_musicfestivals_titlelists.permalink, "
            . "ciniki_musicfestivals_titlelists.search_label, "
            . "ciniki_musicfestivals_titlelists.flags, "
            . "ciniki_musicfestivals_titlelists.description, "
            . "ciniki_musicfestivals_titlelists.col1_field, "
            . "ciniki_musicfestivals_titlelists.col1_label, "
            . "ciniki_musicfestivals_titlelists.col2_field, "
            . "ciniki_musicfestivals_titlelists.col2_label, "
            . "ciniki_musicfestivals_titlelists.col3_field, "
            . "ciniki_musicfestivals_titlelists.col3_label, "
            . "ciniki_musicfestivals_titlelists.col4_field, "
            . "ciniki_musicfestivals_titlelists.col4_label "
            . "FROM ciniki_musicfestivals_titlelists "
            . "WHERE ciniki_musicfestivals_titlelists.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestivals_titlelists.id = '" . ciniki_core_dbQuote($ciniki, $args['list_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'titlelists', 'fname'=>'id', 
                'fields'=>array('name', 'permalink', 'search_label', 'flags', 'description',
                    'col1_field', 'col1_label',
                    'col2_field', 'col2_label',
                    'col3_field', 'col3_label',
                    'col4_field', 'col4_label',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1149', 'msg'=>'Approved Title List not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['titlelists'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1150', 'msg'=>'Unable to find Approved Title List'));
        }
        $titlelist = $rc['titlelists'][0];
    }

    return array('stat'=>'ok', 'titlelist'=>$titlelist);
}
?>
