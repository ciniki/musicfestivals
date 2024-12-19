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
    $accompanists = $rc['accompanists'];
   
    //
    // Setup teachers as objects
    //
    foreach($teachers as $teacher) {
        if( (isset($teacher['added']) && $teacher['added'] == 'yes')
            || (isset($teacher['included']) && $teacher['included'] == 'yes')
            || (isset($teacher['students']) && $teacher['students'] == 'yes')
            ) {
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
    foreach($accompanists as $accompanist) {
        if( (isset($accompanist['added']) && $accompanist['added'] == 'yes')
            || (isset($accompanist['included']) && $accompanist['included'] == 'yes')
            || (isset($teacher['students']) && $teacher['students'] == 'yes')
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.messageref', array(
                'message_id' => $message_id,
                'object' => 'ciniki.musicfestivals.accompanist',
                'object_id' => $accompanist['id'],
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
            if( $ref['object'] != 'ciniki.musicfestivals.teacher' 
                && $ref['object'] != 'ciniki.musicfestivals.accompanist' 
                && $ref['object'] != 'ciniki.musicfestivals.competitor' 
                ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
                $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.messageref', $ref['id'], null, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.644', 'msg'=>'Unable to remove the messageref', 'err'=>$rc['err']));
                }
                
            }
        }
    }

    return array('stat'=>'ok');
}
?>
