<?php
//
// Description
// -----------
// Convert any included teachers/competitors from syllabus/schedule/tags into teacher/competitor objects
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_messageExtractRecipients(&$ciniki, $tnid, $message_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'messageLoad');
    $rc = ciniki_musicfestivals_messageLoad($ciniki, $tnid, array(
        'message_id'=>$message_id,
        'allrefs'=>'yes',
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $message = $rc['message'];
    $teachers = $rc['teachers'];
    $competitors = $rc['competitors'];
   
    //
    // Setup teachers as objects
    //
    foreach($teachers as $teacher) {
        if( isset($teacher['included']) && $teacher['included'] == 'yes' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.messageref', array(
                'message_id' => $message_id,
                'object' => 'ciniki.musicfestivals.teacher',
                'object_id' => $teacher['id'],
                ), 0x04);
            if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.core.73' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.556', 'msg'=>'Unable to add the messageref', 'err'=>$rc['err']));
            }
        }
    }
    foreach($competitors as $competitor) {
        if( isset($competitor['included']) && $competitor['included'] == 'yes' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.messageref', array(
                'message_id' => $message_id,
                'object' => 'ciniki.musicfestivals.competitor',
                'object_id' => $competitor['id'],
                ), 0x04);
            if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.core.73' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.557', 'msg'=>'Unable to add the messageref', 'err'=>$rc['err']));
            }
        }
    }
    if( isset($message['objects']) ) {
        foreach($message['objects'] as $ref) {
            //
            // Remove ref to non-teacher/competitor objects
            //
            if( $ref['object'] != 'ciniki.musicfestivals.teacher' && $ref['object'] != 'ciniki.musicfestivals.competitor' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
                $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.messageref', $ref['id'], null, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.538', 'msg'=>'Unable to remove the messageref', 'err'=>$rc['err']));
                }
                
            }
        }
    }

    return array('stat'=>'ok');
}
?>
