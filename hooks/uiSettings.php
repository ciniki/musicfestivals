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
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['tenant']['modules']['ciniki.musicfestivals'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
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
    // Check if current/active festivals to show in main menu
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x0100)
        && isset($ciniki['tenant']['modules']['ciniki.musicfestivals'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_musicfestivals "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status < 60 "
            . "ORDER BY start_date DESC "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.261', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
        }
        $rows = isset($rc['rows']) ? $rc['rows'] : array();
        foreach($rows as $festival) {
            $menu_item = array(
                'priority'=>2801,
                'label'=>$festival['name'],
                'edit'=>array('app'=>'ciniki.musicfestivals.main', 'args'=>array('festival_id'=>$festival['id'])),
                );
            $rsp['menu_items'][] = $menu_item;
        }
    }

    //
    // Social media post ideas
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x100000)
        && isset($ciniki['tenant']['modules']['ciniki.musicfestivals'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
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
