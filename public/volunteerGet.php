<?php
//
// Description
// ===========
// This method will return all the information about an volunteer.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the volunteer is attached to.
// volunteer_id:          The ID of the volunteer to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_volunteerGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'volunteer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Volunteer'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerGet');
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
    // Load the festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Return default for new Volunteer
    //
    if( $args['volunteer_id'] == 0 ) {
        $volunteer = array('id'=>0,
            'customer_id' => (isset($args['customer_id']) ? $args['customer_id'] : 0),
            'festival_id' => (isset($args['festival_id']) ? $args['festival_id'] : 0),
            'status' => '10',
            'shortname' => '',
            'local_festival_id' => '0',
            'notes' => '',
            'internal_notes' => '',
        );
    }

    //
    // Get the details for an existing Volunteer
    //
    else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerLoad');
        $rc = ciniki_musicfestivals_volunteerLoad($ciniki, $args['tnid'], [
            'festival_id' => $args['festival_id'],
            'volunteer_id' => $args['volunteer_id'],
            'shifts' => 'yes',
            ]);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $volunteer = $rc['volunteer'];
        if( isset($volunteer['shifts']) ) {
            $volunteer['shifts'] = array_values($volunteer['shifts']);
        }
    }

    //
    // If the customer is specified, load the details
    //
    if( isset($volunteer['customer_id']) && $volunteer['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
        $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], 
            array('customer_id'=>$volunteer['customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $volunteer['customer'] = $rc['customer'];
        $volunteer['customer_details'] = $rc['details'];
    } else {
        $volunteer['customer'] = array();
        $volunteer['customer_details'] = array();
    }

    $rsp = array('stat'=>'ok', 'volunteer'=>$volunteer);

    //
    // Get the complete list of tags
    //
    $strsql = "SELECT DISTINCT tags.tag_type, tags.tag_name AS names "
        . "FROM ciniki_musicfestival_volunteer_tags AS tags "
        . "INNER JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
            . "tags.volunteer_id = volunteers.id "
            . "AND volunteers.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY tags.tag_type, tags.tag_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'tags', 'fname'=>'tag_type', 'fields'=>array('type'=>'tag_type', 'names'), 
            'dlists'=>array('names'=>'::')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['available_days'] = [];
    if( isset($festival['volunteers-availability-days']) && $festival['volunteers-availability-days'] != '' ) {
        $rsp['available_days'] = preg_split('/\s*\n\s*/', trim($festival['volunteers-availability-days']));
    }
    $rsp['available_times'] = [];
    if( isset($festival['volunteers-availability-times']) && $festival['volunteers-availability-times'] != '' ) {
        $rsp['available_times'] = preg_split('/\s*\n\s*/', trim($festival['volunteers-availability-times']));
    }
    $rsp['roles'] = [];
    if( isset($festival['volunteers-roles']) && $festival['volunteers-roles'] != '' ) {
        $rsp['roles'] = preg_split('/\s*,\s*/', trim($festival['volunteers-roles']));
    }
    $rsp['skills'] = [];
    if( isset($festival['volunteers-skills']) && $festival['volunteers-skills'] != '' ) {
        $rsp['skills'] = preg_split('/\s*,\s*/', trim($festival['volunteers-skills']));
    }
    if( isset($rc['tags']) ) {
        foreach($rc['tags'] as $type) {
            $names = explode('::', $type['names']);
            foreach($names as $name) {
                if( $type['type'] == 10 && !in_array($name, $rsp['available_days']) ) {
                    $rsp['available_days'][] = $name;
                } else if( $type['type'] == 20 && !in_array($name, $rsp['available_times']) ) {
                    $rsp['available_times'][] = $name;
                } else if( $type['type'] == 30 && !in_array($name, $rsp['skills']) ) {
                    $rsp['skills'][] = $name;
                } else if( $type['type'] == 50 && !in_array($name, $rsp['roles']) ) {
                    $rsp['roles'][] = $name;
                }
            }
        }
    }
    sort($rsp['skills']);
    sort($rsp['roles']);

    //
    // Get the list of member festivals
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $strsql = "SELECT members.id, "
            . "members.name "
            . "FROM ciniki_musicfestivals_members AS members "
            . "WHERE members.status = 10 "
            . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY members.id "
            . "ORDER BY members.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'members', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1314', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
        }
        $rsp['members'] = isset($rc['members']) ? $rc['members'] : array();
        array_unshift($rsp['members'], ['id' => 0, 'name' => 'None']);
    }

    return $rsp;
}
?>
