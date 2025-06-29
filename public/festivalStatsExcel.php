<?php
//
// Description
// ===========
// This method will return all the information about an festival.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the festival is attached to.
// festival_id:          The ID of the festival to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_festivalStatsExcel($ciniki) {

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
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.festivalStatsExcel');
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Find the list of placements
    //
    if( isset($festival['comments-placement-options']) && $festival['comments-placement-options'] != '' ) {
        $placements = explode(',', $festival['comments-placement-options']);
        foreach($placements as $pid => $placement) {
            $placements[$pid] = trim($placement);
        }
    }

    //
    // Get the list of members
    //
    $strsql = "SELECT registrations.member_id, "
        . "members.name, "
        . "registrations.id AS reg_id, "
        . "registrations.participation, "
        . "registrations.timeslot_id, "
        . "registrations.placement, "
        . "registrations.finals_timeslot_id, "
        . "registrations.finals_placement "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
            . "registrations.member_id = members.id "
            . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY members.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'members', 'fname'=>'member_id', 
            'fields'=>array('id'=>'member_id', 'name'),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'participation', 'timeslot_id', 'placement', 'finals_timeslot_id', 'finals_placement'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.995', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $members = isset($rc['members']) ? $rc['members'] : array();

    foreach($members as $mid => $member) {
        $members[$mid]['num_registrations'] = 0;
        $members[$mid]['num_live'] = 0;
        $members[$mid]['num_virtual'] = 0;
        foreach($placements as $p) {
            $members[$mid][$p] = 0;
            $members[$mid]["virtual_{$p}"] = 0;
            $members[$mid]["live_{$p}"] = 0;
        }
       
        foreach($member['registrations'] as $reg) {
            $members[$mid]['num_registrations']++;
            if( $reg['participation'] == 1 ) {
                $members[$mid]['num_virtual']++;
            } else {
                $members[$mid]['num_live']++;
            }
            if( $reg['finals_placement'] != '' ) {
                if( !isset($members[$mid][$reg['finals_placement']]) ) {
                    $members[$mid][$reg['finals_placement']] = 1;
                    $members[$mid]["virtual_{$reg['finals_placement']}"] = 0;
                    $members[$mid]["live_{$reg['finals_placement']}"] = 0;
                } else {
                    $members[$mid][$reg['finals_placement']] += 1;
                }
                if( $reg['participation'] == 1 ) {
                    $members[$mid]["virtual_{$reg['finals_placement']}"] += 1;
                } else {
                    $members[$mid]["live_{$reg['finals_placement']}"] += 1;
                }
            } elseif( $reg['placement'] != '' ) {
                if( !isset($members[$mid][$reg['placement']]) ) {
                    $members[$mid][$reg['placement']] = 1;
                    $members[$mid]["virtual_{$reg['finals_placement']}"] = 0;
                    $members[$mid]["live_{$reg['placement']}"] = 0;
                } else {
                    $members[$mid][$reg['placement']] += 1;
                }
                if( $reg['participation'] == 1 ) {
                    $members[$mid]["virtual_{$reg['placement']}"] += 1;
                } else {
                    $members[$mid]["live_{$reg['placement']}"] += 1;
                }
            }
        }

        foreach($placements as $p) {
            if( $members[$mid][$p] == 0 ) {
                $members[$mid][$p] = '';
            }
            if( $members[$mid]["virtual_{$p}"] == 0 ) {
                $members[$mid]["virtual_{$p}"] = '';
            }
            if( $members[$mid]["live_{$p}"] == 0 ) {
                $members[$mid]["live_{$p}"] = '';
            }
        }

        unset($members[$mid]['registrations']);
    }
    $festival['stats_members_headerValues'] = array('Name', 'Registrations', 'Live', 'Virtual');
    $festival['stats_members_dataMaps'] = array('name', 'num_registrations', 'num_live', 'num_virtual');
    foreach($placements as $p) {
        $festival['stats_members_headerValues'][] = $p;
        $festival['stats_members_dataMaps'][] = $p;
    }
    $festival['stats_members'] = $members;

    
    //
    // Build the excel spreadsheet
    //
    $sheets = [
        'all' => [
            'label' => 'All',
            'columns' => [
                ['label' => 'Name', 'field' =>'name'],
                ['label' => 'Registrations', 'field' =>'num_registrations'],
                ['label' => 'Live', 'field' =>'num_live'],
                ['label' => 'Virtual', 'field' =>'num_virtual'],
                ],
            'rows' => $members,
            ],
        'live' => [
            'label' => 'Live',
            'columns' => [
                ['label' => 'Name', 'field' =>'name'],
                ['label' => 'Registrations', 'field' =>'num_live'],
                ],
            'rows' => $members,
            ],
        'virtual' => [
            'label' => 'Virtual',
            'columns' => [
                ['label' => 'Name', 'field' =>'name'],
                ['label' => 'Registrations', 'field' =>'num_virtual'],
                ],
            'rows' => $members,
            ],
        ];

    foreach($placements as $p) {
        $sheets['all']['columns'][] = ['label' => $p, 'field' => $p];
        $sheets['live']['columns'][] = ['label' => "{$p}", 'field' => "live_{$p}"];
        $sheets['virtual']['columns'][] = ['label' => "{$p}", 'field' => "virtual_{$p}"];
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'excelGenerate');
    return ciniki_core_excelGenerate($ciniki, $args['tnid'], [
        'sheets' => $sheets,
        'download' => 'yes',
        'filename' => 'Member Statistics.xlsx'
        ]);
}
?>
