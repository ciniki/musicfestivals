<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_titleUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'title_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Approved Title'),
        'title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'),
        'opus'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Opus'),
        'movements'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Movements'),
        'musical'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Musical'),
        'composer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Composer'),
        'arranger'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Arranger'),
        'source_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Source Type'),
        'list_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'name'=>'Lists'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.titleUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($args['list_id']) && ($args['list_id'] == '' || $args['list_id'] <= 0) ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1159', 'msg'=>'You must choose a list'));
    }

    //
    // Load the current title
    //
    $strsql = "SELECT titles.id, "
        . "titles.fulltitle, "
        . "titles.title, "
        . "titles.opus, "
        . "titles.movements, "
        . "titles.musical, "
        . "titles.composer, "
        . "titles.arranger, "
        . "titles.source_type, "
        . "titles.keywords "
        . "FROM ciniki_musicfestivals_titles AS titles "
        . "WHERE titles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND titles.id = '" . ciniki_core_dbQuote($ciniki, $args['title_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'title');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1655', 'msg'=>'Unable to load title', 'err'=>$rc['err']));
    }
    if( !isset($rc['title']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1656', 'msg'=>'Unable to find requested title'));
    }
    $title = $rc['title'];

    //
    // Load the titlelists
    //
    $strsql = "SELECT tlt.id, "
        . "tlt.uuid, "
        . "tlt.list_id "
        . "FROM ciniki_musicfestivals_titlelists_titles AS tlt "
        . "WHERE tlt.title_id = '" . ciniki_core_dbQuote($ciniki, $args['title_id']) . "' "
        . "AND tlt.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'list_ids', 'fname'=>'list_id', 'fields'=>array('id', 'uuid', 'list_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1297', 'msg'=>'Unable to load list_ids', 'err'=>$rc['err']));
    }
    $title['list_ids'] = isset($rc['list_ids']) ? $rc['list_ids'] : array();

    //
    // Merge args with existing title information
    //
    $updated_title = [
        'title' => isset($args['title']) ? $args['title'] : $title['title'],
        'opus' => isset($args['opus']) ? $args['opus'] : $title['opus'],
        'movements' => isset($args['movements']) ? $args['movements'] : $title['movements'],
        'musical' => isset($args['musical']) ? $args['musical'] : $title['musical'],
        'composer' => isset($args['composer']) ? $args['composer'] : $title['composer'],
        'arranger' => isset($args['arranger']) ? $args['arranger'] : $title['arranger'],
        'source_type' => isset($args['source_type']) ? $args['source_type'] : $title['source_type'],
        ];

    //
    // Create the keywords
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleListKeywordsMake');
    $rc = ciniki_musicfestivals_titleListKeywordsMake($ciniki, $args['tnid'], ['title'=>$updated_title]);
    if( $rc['stat'] != 'ok' ) {
        print_r($rc);
        exit;
    }
    if( $title['keywords'] != $rc['keywords'] ) {
        $args['keywords'] = $rc['keywords'];
    }

    //
    // Create the full title
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
    $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $updated_title, '');
    if( $rc['stat'] != 'ok' ) {
        print_r($rc);
        exit;
    }
    if( $rc['title'] != $title['fulltitle'] ) {
        $args['fulltitle'] = $rc['title'];
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Approved Title in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.title', $args['title_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    if( isset($args['list_ids']) && is_array($args['list_ids']) ) {
        //
        // Remove list_ids no longer attached
        //
        foreach($title['list_ids'] as $id => $item) {
            if( !in_array($id, $args['list_ids']) ) {
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.titlelisttitle', $item['id'], $item['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1688', 'msg'=>'Unable to add the titlelist_title', 'err'=>$rc['err']));
                }
            }
        }

        //
        // Add new list_ids 
        //
        foreach($args['list_ids'] as $id) {
            if( !isset($title['list_ids'][$id]) ) {
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.titlelisttitle', [
                    'list_id' => $id,
                    'title_id' => $title['id'],
                    ], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1689', 'msg'=>'Unable to add the titlelist_title', 'err'=>$rc['err']));
                }
            }
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'musicfestivals');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.title', 'object_id'=>$args['title_id']));

    return array('stat'=>'ok');
}
?>
