<?php
//
// Description
// -----------
// This function will copy a previous festival's syllabus into the current festival.
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
function ciniki_musicfestivals_syllabusCopy(&$ciniki, $tnid, $festival_id, $old_festival_id) {
   
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "sections.permalink AS section_permalink, "
        . "sections.sequence AS section_sequence, "
        . "sections.primary_image_id AS section_image_id, "
        . "sections.synopsis AS section_synopsis, "
        . "sections.description AS section_description, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.permalink AS category_permalink, "
        . "categories.sequence AS category_sequence, "
        . "categories.primary_image_id AS category_image_id, "
        . "categories.synopsis AS category_synopsis, "
        . "categories.description AS category_description, "
        . "classes.id AS class_id, "
        . "classes.code, "
        . "classes.name AS iname, "
        . "classes.permalink AS class_permalink, "
        . "classes.sequence AS class_sequence, "
        . "classes.flags, "
        . "classes.feeflags, "
        . "classes.titleflags, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee, "
        . "classes.earlybird_plus_fee "
        . "classes.plus_fee, "
        . "classes.min_competitors, "
        . "classes.max_competitors, "
        . "classes.min_titles, "
        . "classes.max_titles, "
        . "classes.provincials_code, "
        . "classes.synopsis, "
        . "classes.schedule_seconds, "
        . "classes.schedule_at_seconds, "
        . "classes.schedule_ata_seconds, "
        . "classes.keywords, "
        . "classes.options "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $old_festival_id) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY sections.sequence, sections.date_added, "
            . "categories.sequence, categories.name, categories.date_added, "
            . "classes.sequence, classes.date_added "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id',
            'fields'=>array('name'=>'section_name', 'permalink'=>'section_permalink', 'sequence'=>'section_sequence', 
                'primary_image_id'=>'section_image_id', 'synopsis'=>'section_synopsis', 'description'=>'section_description',
                )),
        array('container'=>'categories', 'fname'=>'category_id',
            'fields'=>array('name'=>'category_name', 'permalink'=>'category_permalink', 'sequence'=>'category_sequence', 
                'primary_image_id'=>'category_image_id', 'synopsis'=>'category_synopsis', 'description'=>'category_description',
                )),
        array('container'=>'classes', 'fname'=>'class_id',
            'fields'=>array('code', 'name'=>'iname', 'permalink'=>'class_permalink', 'sequence'=>'class_sequence', 
                'flags', 'feeflags', 'titleflags', 
                'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee',
                'min_competitors', 'max_competitors', 'min_titles', 'max_titles', 
                'provincials_code', 'synopsis',
                'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds',
                'keywords', 'options',
                )),
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
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.section', $section, 0x04);
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
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.category', $category, 0x04);
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
                            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.class', $class, 0x04);
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
