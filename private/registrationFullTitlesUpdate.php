<?php
//
// Description
// -----------
// This function will update the fulltitle[1-8] for a registration from the pieces.
//
// Arguments
// ---------
// ciniki:
// tnid:                 The tenant ID to check the session user against.
// method:                      The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_musicfestivals_registrationFullTitlesUpdate(&$ciniki, $tnid, $args) {
  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    //
    // Check if registration passed
    //
    if( isset($args['registration']) ) {
        $reg = $args['registration'];
    } 
    elseif( isset($args['registration_id']) ) {
        $strsql = "SELECT registrations.id, "
            . "registrations.fulltitle1, "
            . "registrations.fulltitle2, "
            . "registrations.fulltitle3, "
            . "registrations.fulltitle4, "
            . "registrations.fulltitle5, "
            . "registrations.fulltitle6, "
            . "registrations.fulltitle7, "
            . "registrations.fulltitle8, "
            . "registrations.title1, "
            . "registrations.title2, "
            . "registrations.title3, "
            . "registrations.title4, "
            . "registrations.title5, "
            . "registrations.title6, "
            . "registrations.title7, "
            . "registrations.title8, "
            . "registrations.opus1, "
            . "registrations.opus2, "
            . "registrations.opus3, "
            . "registrations.opus4, "
            . "registrations.opus5, "
            . "registrations.opus6, "
            . "registrations.opus7, "
            . "registrations.opus8, "
            . "registrations.movements1, "
            . "registrations.movements2, "
            . "registrations.movements3, "
            . "registrations.movements4, "
            . "registrations.movements5, "
            . "registrations.movements6, "
            . "registrations.movements7, "
            . "registrations.movements8, "
            . "registrations.musical1, "
            . "registrations.musical2, "
            . "registrations.musical3, "
            . "registrations.musical4, "
            . "registrations.musical5, "
            . "registrations.musical6, "
            . "registrations.musical7, "
            . "registrations.musical8, "
            . "registrations.composer1, "
            . "registrations.composer2, "
            . "registrations.composer3, "
            . "registrations.composer4, "
            . "registrations.composer5, "
            . "registrations.composer6, "
            . "registrations.composer7, "
            . "registrations.composer8 "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "WHERE registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' " 
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'reg');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1661', 'msg'=>'Unable to load reg', 'err'=>$rc['err']));
        }
        if( !isset($rc['reg']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1662', 'msg'=>'Unable to find requested reg'));
        }
        $reg = $rc['reg'];
    } 
    else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1660', 'msg'=>'No registration specified to update titles.'));
    }

    $update_args = [];
    $titles = [];
    for($i = 1; $i <= 8; $i++) {
        $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $reg, $i);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['title'] != $reg["fulltitle{$i}"] ) {
            $update_args["fulltitle{$i}"] = $rc['title'];
        }
        // Save into array to pass back for calling function to update other processes if necessary
        $titles[$i] = $rc['title'];
    }

    if( count($update_args) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $args['registration_id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1663', 'msg'=>'Unable to update the registration titles', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok', 'titles'=>$titles);
}
?>
