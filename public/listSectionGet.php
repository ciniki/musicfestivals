<?php
//
// Description
// ===========
// This method will return all the information about an list section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the list section is attached to.
// listsection_id:          The ID of the list section to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_listSectionGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'listsection_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'List Section'),
        'list_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'List Section'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.listSectionGet');
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
    // Return default for new List Section
    //
    if( $args['listsection_id'] == 0 ) {
        $listsection = array('id'=>0,
            'list_id'=>'',
            'name'=>'',
            'sequence'=>'1',
        );
        if( isset($args['list_id']) && $args['list_id'] > 0 ) {
            //
            // Get the next sequence number
            //
            $strsql = "SELECT MAX(sequence) AS num "
                . "FROM ciniki_musicfestival_list_sections "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND list_id = '" . ciniki_core_dbQuote($ciniki, $args['list_id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals','item');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $listsection['sequence'] = (isset($rc['item']['num']) ? $rc['item']['num'] + 1 : 1);
        }
    }

    //
    // Get the details for an existing List Section
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_list_sections.id, "
            . "ciniki_musicfestival_list_sections.list_id, "
            . "ciniki_musicfestival_list_sections.name, "
            . "ciniki_musicfestival_list_sections.sequence "
            . "FROM ciniki_musicfestival_list_sections "
            . "WHERE ciniki_musicfestival_list_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_list_sections.id = '" . ciniki_core_dbQuote($ciniki, $args['listsection_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'listsections', 'fname'=>'id', 
                'fields'=>array('list_id', 'name', 'sequence'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.271', 'msg'=>'List Section not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['listsections'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.272', 'msg'=>'Unable to find List Section'));
        }
        $listsection = $rc['listsections'][0];
    }

    return array('stat'=>'ok', 'listsection'=>$listsection);
}
?>
