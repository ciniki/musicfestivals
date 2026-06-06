<?php
//
// Description
// ===========
// This method will return all the information about a volunteer resource.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the resource is attached to.
// resource_id:          The ID of the resource to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_volunteerResourceGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'resource_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Resource'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerResourceGet');
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
    // Return default for new Resource
    //
    if( $args['resource_id'] == 0 ) {
        $resource = array('id'=>0,
            'name'=>'',
            'resourcetype'=>'10',
            'category'=>'',
            'synopsis'=>'',
            'url'=>'',
            'org_filename'=>'',
            'extension'=>'',
        );
    }

    //
    // Get the details for an existing Resource
    //
    else {
        $strsql = "SELECT resources.id, "
            . "resources.name, "
            . "resources.resourcetype, "
            . "resources.category, "
            . "resources.synopsis, "
            . "resources.url, "
            . "resources.org_filename, "
            . "resources.extension "
            . "FROM ciniki_musicfestival_volunteer_resources AS resources "
            . "WHERE resources.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND resources.id = '" . ciniki_core_dbQuote($ciniki, $args['resource_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'resources', 'fname'=>'id', 
                'fields'=>array('name', 'resourcetype', 'category', 'synopsis', 'url', 'org_filename', 'extension'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1597', 'msg'=>'Resource not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['resources'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1598', 'msg'=>'Unable to find Resource'));
        }
        $resource = $rc['resources'][0];
    }

    return array('stat'=>'ok', 'resource'=>$resource);
}
?>
