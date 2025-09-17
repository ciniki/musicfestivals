<?php
//
// Description
// -----------
// This function returns the settings for the module and the main menu items and settings menu items
//
// Arguments
// ---------
// ciniki:
// tnid:
// args: The arguments for the hook
//
// Returns
// -------
//
function ciniki_musicfestivals_hooks_uiSettings(&$ciniki, $tnid, $args) {
    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array(), 'settings_menu_items'=>array());

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['tenant']['modules']['ciniki.musicfestivals'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['ciniki.musicfestivals'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>2800,
            'label'=>'Music Festivals',
            'edit'=>array('app'=>'ciniki.musicfestivals.main'),
            );
        $rsp['menu_items'][] = $menu_item;
    }

    //
    // Get the latest current festival
    //
    $strsql = "SELECT festivals.id, "
        . "festivals.name, "
        . "festivals.start_date, "
        . "festivals.end_date, "
        . "COUNT(sections.id) AS num_schedule_sections "
        . "FROM ciniki_musicfestivals AS festivals "
        . "LEFT JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
            . "festivals.id = sections.festival_id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND festivals.status = 30 "
        . "GROUP BY festivals.id "
        . "ORDER BY festivals.start_date DESC "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.261', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    $festival = isset($rc['festival']) ? $rc['festival'] : array();

    if( isset($festival['start_date']) && $festival['start_date'] != '' ) {
        $start_dt = new DateTime($festival['start_date'], new DateTimezone($intl_timezone));
        $end_dt = new DateTime($festival['end_date'] . ' 11:59pm', new DateTimezone($intl_timezone));
        $now = new DateTime('now', new DateTimezone($intl_timezone));

        if( $start_dt < $now && $end_dt > $now ) {
            $current_festival = 'yes';
        }
    }


    //
    // Check if current/active festivals to show in main menu
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x0100)
        && isset($festival['id'])
        && isset($ciniki['tenant']['modules']['ciniki.musicfestivals'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['ciniki.musicfestivals'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>2805,
            'label'=>$festival['name'],
            'edit'=>array('app'=>'ciniki.musicfestivals.main', 'args'=>array('festival_id'=>$festival['id'])),
            );
        $rsp['menu_items'][] = $menu_item;

        //
        // Display current festival and trophies and awards enabled, show shortcut to trophies and awards
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x40) ) {
            $menu_item = array(
                'priority'=>2800,
                'label'=>'Accolades',
                'edit'=>array('app'=>'ciniki.musicfestivals.main', 'args'=>array('accolades'=>1)),
                );
            $rsp['menu_items'][] = $menu_item;
        }
    }

    //
    // Photos link when current festival or in middle of festival
    //
    if( isset($festival['num_schedule_sections']) && $festival['num_schedule_sections'] > 0 
        && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x04) // photos
        && isset($festival['id'])
        && isset($current_festival)
        && isset($ciniki['tenant']['modules']['ciniki.musicfestivals'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['ciniki.musicfestivals'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>2803,
            'label'=>$festival['name'] . ' - Photos',
            'edit'=>array('app'=>'ciniki.musicfestivals.photos', 'args'=>array('festival_id'=>$festival['id'])),
            );
        $rsp['menu_items'][] = $menu_item;
    }

    //
    // Social media post ideas
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x100000)
        && isset($ciniki['tenant']['modules']['ciniki.musicfestivals'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['ciniki.musicfestivals'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>1150,
            'label'=>'Social Content Sharing',
            'edit'=>array('app'=>'ciniki.musicfestivals.socialposts'),
            );
        $rsp['menu_items'][] = $menu_item;
    }

    return $rsp;
}
?>
