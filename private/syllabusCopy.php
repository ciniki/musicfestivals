<?php
//
// Description
// -----------
// This function will copy a previous festival's syllabus into the current festival.
//
// Arguments
// ---------
// ciniki:
// business_id:                 The business ID to check the session user against.
// method:                      The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_musicfestivals_syllabusCopy(&$ciniki, $business_id, $festival_id, $old_festival_id) {
   
    $strsql = "SELECT s.id AS sid, s.name AS sn, s.permalink AS sp, s.sequence AS so, "
        . "s.primary_image_id AS si, s.synopsis AS ss, s.description AS sd, "
        . "c.id AS cid, c.name AS cn, c.permalink AS cp, c.sequence AS co, c.primary_image_id AS ci, c.synopsis AS cs, c.description AS cd, "
        . "i.id AS iid, i.code, i.name AS iname, i.permalink AS ip, i.sequence AS io, i.flags, i.fee "
        . "FROM ciniki_musicfestival_sections AS s "
        . "LEFT JOIN ciniki_musicfestival_categories AS c ON ("
            . "s.id = c.section_id "
            . "AND c.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS i ON ("
            . "c.id = i.category_id "
            . "AND i.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE s.festival_id = '" . ciniki_core_dbQuote($ciniki, $old_festival_id) . "' "
        . "AND s.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "ORDER BY s.sequence, s.date_added, c.sequence, c.date_added, i.sequence, i.date_added "
        . "";
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'sid',
            'fields'=>array('name'=>'sn', 'permalink'=>'sp', 'sequence'=>'so', 'primary_image_id'=>'si', 'synopsis'=>'ss', 'description'=>'sd')),
        array('container'=>'categories', 'fname'=>'cid',
            'fields'=>array('name'=>'cn', 'permalink'=>'cp', 'sequence'=>'co', 'primary_image_id'=>'ci', 'synopsis'=>'cs', 'description'=>'cd')),
        array('container'=>'classes', 'fname'=>'iid',
            'fields'=>array('code', 'name'=>'iname', 'permalink'=>'ip', 'sequence'=>'io', 'flags', 'fee')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.104', 'msg'=>'Previous syllabus not found', 'err'=>$rc['err']));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    if( isset($rc['sections']) ) {
        $sections = $rc['sections'];
        foreach($sections as $section) {
            //
            // Add the section
            //
            $section['festival_id'] = $festival_id;
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.musicfestivals.section', $section, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $section_id = $rc['id'];
            if( isset($section['categories']) ) {
                foreach($section['categories'] as $category) {
                    //
                    // Add the category
                    //
                    $category['festival_id'] = $festival_id;
                    $category['section_id'] = $section_id;
                    $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.musicfestivals.category', $category, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $category_id = $rc['id'];
                    if( isset($category['classes']) ) {
                        foreach($category['classes'] as $class) {
                            //
                            // Add the class
                            //
                            $class['festival_id'] = $festival_id;
                            $class['category_id'] = $category_id;
                            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.musicfestivals.class', $class, 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                return $rc;
                            }
                        }
                    }
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
