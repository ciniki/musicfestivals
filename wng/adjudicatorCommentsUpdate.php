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

    //
    // Go through the registrations and check for updates to comments or score
    //
    foreach($args['registrations'] as $reg) {
        $update_args = array();
        $reg_update_args = array();
        if( isset($_POST["f-{$reg['id']}-comments"])
            && $_POST["f-{$reg['id']}-comments"] != $reg['comments']
            ) {
            $update_args['comments'] = $_POST["f-{$reg['id']}-comments"];
        }
        if( isset($_POST["f-{$reg['id']}-score"])
            && $_POST["f-{$reg['id']}-score"] != $reg['score']
            ) {
            $update_args['score'] = $_POST["f-{$reg['id']}-score"];
        }
        if( isset($_POST["f-{$reg['id']}-placement"])
            && $_POST["f-{$reg['id']}-placement"] != $reg['placement']
            ) {
            $reg_update_args['placement'] = $_POST["f-{$reg['id']}-placement"];
        }
        if( count($update_args) > 0 ) {
            if( $reg['comment_id'] > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.comment', $reg['comment_id'], $update_args, $tmsupdate);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.441', 'msg'=>'Unable to update the comment', 'err'=>$rc['err']));
                }
                //
                // Clear autosave history
                //
                if( !isset($args['autosave']) || $args['autosave'] != 'yes' ) {
                    // Unable to clear history because last autosave could be current value
/*                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbClearAutoSaveHistory');
                    $rc = ciniki_core_objectClearAutoSaveHistory($ciniki, $tnid, 'ciniki.musicfestivals.comment', $reg['comment_id']);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.443', 'msg'=>'Unable to clear autosave history', 'err'=>$rc['err']));
                    } */
                }
                
            } else {
                $update_args['registration_id'] = $reg['id'];
                $update_args['adjudicator_id'] = $args['adjudicator_id'];
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.comment', $update_args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.440', 'msg'=>'Unable to add the comment', 'err'=>$rc['err']));
                }
            }
        }
        if( count($reg_update_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $reg['id'], $reg_update_args, $tmsupdate);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.453', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
