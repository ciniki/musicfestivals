<?php
//
// Description
// -----------
// This function will update a list of tags.
//
// Arguments
// ---------
// ciniki:
// module:              The package.module the tag is located in.
// object:              The object used to push changes in sync.
// table:               The database table that stores the tags.
// key_name:            The name of the ID field that links to the item the tag is for.
// key_value:           The value for the ID field.
// type:                The type of the tag. 
//
//                      0 - unknown
//                      1 - List
//                      2 - Category **future**
//
// list:                The array of tag names to add.
// 
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_musicfestivals_classTagsUpdate(&$ciniki, $tnid, $class_id, $type, $list) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');

    //
    // Get the existing list of tags for the item
    //
    $strsql = "SELECT id, uuid, class_id, tag_type AS type, tag_name AS name "
        . "FROM ciniki_musicfestival_class_tags "
        . "WHERE class_id = '" . ciniki_core_dbQuote($ciniki, $class_id) . "' "
        . "AND tag_type = '" . ciniki_core_dbQuote($ciniki, $type) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'tags', 'name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tags']) || $rc['num_rows'] == 0 ) {
        $dbtags = array();
    } else {
        $dbtags = $rc['tags'];
    }

    //
    // Delete tags no longer used
    //
    foreach($dbtags as $tag_name => $tag) {
        if( !in_array($tag_name, $list, true) ) {
            //
            // The tag does not exist in the new list, so it should be deleted.
            //
            $strsql = "DELETE FROM ciniki_musicfestival_class_tags "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tag['id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.musicfestivals');
            if( $rc['stat'] != 'ok' ) { 
                return $rc;
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $tnid,
                3, 'ciniki_musicfestival_class_tags', $tag['id'], '*', '');

            //
            // Sync push delete
            //
            $ciniki['syncqueue'][] = array('push'=>'ciniki.musicfestivals.classtag',
                'args'=>array('delete_uuid'=>$tag['uuid'], 'delete_id'=>$tag['id']));
        }
    }

    //
    // Add new tags lists
    //
    foreach($list as $tag) {
        if( $tag != '' && !array_key_exists($tag, $dbtags) ) {
            //
            // Get a new UUID
            //
            $rc = ciniki_core_dbUUID($ciniki, 'ciniki.musicfestivals');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $uuid = $rc['uuid'];

            //
            // Setup the overrides for the sort name to make the levels sort properly
            //
            $tag_sort_name = $tag;
            switch($tag_sort_name) {
                case 'Junior': $tag_sort_name = 'B - Junior'; break;
                case 'Intermediate': $tag_sort_name = 'C - Intermediate'; break;
                case 'Senior': $tag_sort_name = 'D - Senior'; break;
            }

            $permalink = ciniki_core_makePermalink($ciniki, $tag);

            // 
            // Setup the SQL statement to insert the new thread
            //
            $strsql = "INSERT INTO ciniki_musicfestival_class_tags (uuid, tnid, class_id, tag_type, tag_name, "
                . "tag_sort_name, permalink, date_added, last_updated) VALUES ("
                . "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $class_id) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $type) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $tag) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $tag_sort_name) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $permalink) . "', "
                . "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.musicfestivals');
            // 
            // Only return the error if it was not a duplicate key problem.  Duplicate key error
            // just means the tag name is already assigned to the item.
            //
            if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.core.73' ) {
                return $rc;
            }
            if( isset($rc['insert_id']) ) {
                $tag_id = $rc['insert_id'];
                //
                // Add history
                //
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $tnid,
                    1, 'ciniki_musicfestival_class_tags', $tag_id, 'uuid', $uuid);
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $tnid,
                    1, 'ciniki_musicfestival_class_tags', $tag_id, 'class_id', $class_id);
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $tnid,
                    1, 'ciniki_musicfestival_class_tags', $tag_id, 'tag_type', $type);
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.musicfestivals', 'ciniki_musicfestivals_history', $tnid,
                    1, 'ciniki_musicfestival_class_tags', $tag_id, 'tag_name', $tag);
                //
                // Sync push
                //
                $ciniki['syncqueue'][] = array('push'=>'ciniki.musicfestivals.classtag',
                    'args'=>array('id'=>$tag_id));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
