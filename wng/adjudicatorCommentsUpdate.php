<?php
//
// Description
// -----------
// This function will check for registrations in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_adjudicatorCommentsUpdate(&$ciniki, $tnid, &$request, $args) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.437', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    if( !isset($args['registrations']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.438', 'msg'=>"No registrations specified"));
    }
    if( !isset($args['adjudicator_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.439', 'msg'=>"No adjudicator specified"));
    }
    $tmsupdate = 0x04;
    if( isset($args['autosave']) && $args['autosave'] == 'yes' ) {
        $tmsupdate = 0x04 | 0x08;
    }

    if( isset($_POST['last_saved']) && $_POST['last_saved'] != '' ) {
        $last_saved_utc = new DateTime($_POST['last_saved'], new DateTimezone('UTC'));
    }
    //
    // Go through the registrations and check for updates to comments or mark
    //
    $updates = [];
    $updated_ids = [];
    foreach($args['registrations'] as $reg) {
        $strsql = "SELECT table_field, MAX(log_date) "
            . "FROM ciniki_musicfestivals_history "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND table_name = 'ciniki_musicfestival_registrations' "
            . "AND table_key = '" . ciniki_core_dbQuote($ciniki, $reg['id']) . "' "
            . "AND table_field IN ('comments','mark','placement','level') "
            . "GROUP BY table_field "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
        $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'last_updates');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.443', 'msg'=>'Unable to load the list of ', 'err'=>$rc['err']));
        }
        $last_updates = isset($rc['last_updates']) ? $rc['last_updates'] : [];

        $update_args = array();
        if( isset($_POST["f-{$reg['id']}-comments"])
            && $_POST["f-{$reg['id']}-comments"] != $reg['comments']
            ) {
            //
            // Get the last time the comments was changed
            //
            if( isset($last_updates['comments']) && $last_updates['comments'] != '' ) {
                $last_updated_utc = new DateTime($last_updates['comments'], new DateTimezone('UTC'));
            }
            if( !isset($last_saved_utc) || !isset($last_updated_utc) 
                || $last_saved_utc >= $last_updated_utc 
                ) {
                $update_args['comments'] = $_POST["f-{$reg['id']}-comments"];
            }
        }
        if( isset($_POST["f-{$reg['id']}-mark"])
            && $_POST["f-{$reg['id']}-mark"] != $reg['mark']
            ) {
            if( isset($last_updates['mark']) && $last_updates['mark'] != '' ) {
                $last_updated_utc = new DateTime($last_updates['mark'], new DateTimezone('UTC'));
            }
            if( !isset($last_saved_utc) || !isset($last_updated_utc) 
                || $last_saved_utc >= $last_updated_utc 
                ) {
                $update_args['mark'] = $_POST["f-{$reg['id']}-mark"];
            }
        }
        if( isset($_POST["f-{$reg['id']}-placement"])
            && $_POST["f-{$reg['id']}-placement"] != $reg['placement']
            ) {
            if( isset($last_updates['placement']) && $last_updates['placement'] != '' ) {
                $last_updated_utc = new DateTime($last_updates['placement'], new DateTimezone('UTC'));
            }
            if( !isset($last_saved_utc) || !isset($last_updated_utc) 
                || $last_saved_utc >= $last_updated_utc 
                ) {
                $update_args['placement'] = $_POST["f-{$reg['id']}-placement"];
            }
        } 
        if( isset($_POST["f-{$reg['id']}-level"])
            && $_POST["f-{$reg['id']}-level"] != $reg['level']
            ) {
            if( isset($last_updates['level']) && $last_updates['level'] != '' ) {
                $last_updated_utc = new DateTime($last_updates['level'], new DateTimezone('UTC'));
            }
            if( !isset($last_saved_utc) || !isset($last_updated_utc) 
                || $last_saved_utc >= $last_updated_utc 
                ) {
                $update_args['level'] = $_POST["f-{$reg['id']}-level"];
            }
        } 
        if( count($update_args) > 0 ) {
            if( $reg['id'] > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $reg['id'], $update_args, $tmsupdate);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.441', 'msg'=>'Unable to update the comment', 'err'=>$rc['err']));
                }
                $updated_ids[] = $reg['id'];
                foreach($update_args as $k => $v) {
                    $updates["f-{$reg['id']}-{$k}"] = str_replace("\r", '', $v);
                }
            }
        }
    }

    return array('stat'=>'ok', 'updates'=>$updates, 'updated_ids'=>$updated_ids);
}
?>
