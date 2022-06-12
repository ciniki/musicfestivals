<?php
//
// Description
// -----------
// This function will process a wng request for the blog module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_syllabusSectionProcess(&$ciniki, $tnid, $request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.214', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.215', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.247', 'msg'=>"No festival specified"));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Check for syllabus section requested
    //
    if( !isset($request['uri_split'][$request['cur_uri_pos']])
        || $request['uri_split'][$request['cur_uri_pos']] == '' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.216', 'msg'=>"No syllabus specified"));
    }

    $section_permalink = $request['uri_split'][$request['cur_uri_pos']];

    //
    // Check for image format
    //
    $thumbnail_format = 'square-cropped';
    $thumbnail_padding_color = '#ffffff';
    if( isset($s['thumbnail-format']) && $s['thumbnail-format'] == 'square-padded' ) {
        $thumbnail_format = $s['thumbnail-format'];
        if( isset($s['thumbnail-padding-color']) && $s['thumbnail-padding-color'] != '' ) {
            $thumbnail_padding_color = $s['thumbnail-padding-color'];
        } 
    }
    
    //
    // Get the music festival details
    //
    $dt = new DateTime('now', new DateTimezone($intl_timezone));
    $strsql = "SELECT id, name, flags, "
        . "IFNULL(DATEDIFF(earlybird_date, '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "'), -1) AS earlybird "
        . "FROM ciniki_musicfestivals "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['festival']) ) {
        $festival = $rc['festival'];
//        $festival['settings'] = array(
//            'age-restriction-msg' => '',
//            );
    }

    //
    // Get the section details
    //
    $strsql = "SELECT sections.id, "
        . "sections.permalink, "
        . "sections.name, "
        . "sections.primary_image_id, "
        . "sections.synopsis, "
        . "sections.description "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND sections.permalink = '" . ciniki_core_dbQuote($ciniki, $section_permalink) . "' "
        . "ORDER BY sections.sequence, sections.name "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.217', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
    }
    if( !isset($rc['section']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.218', 'msg'=>'Unable to find requested section'));
    }
    $section = $rc['section'];
  
    if( isset($section['description']) && $section['description'] != '' ) {
        $blocks[] = array(
            'type' => 'text',
            'title' => (isset($s['title']) ? $s['title'] : 'Syllabus') . ' - ' . $section['name'],
            'content' => $section['description'],
            );
    } else {
        $blocks[] = array(
            'type' => 'title', 
            'title' => (isset($s['title']) ? $s['title'] : 'Syllabus') . ' - ' . $section['name'],
            );
    }

    //
    // Load the syllabus for the section
    //
    $strsql = "SELECT classes.id, "
        . "classes.uuid, "
        . "classes.festival_id, "
        . "classes.category_id, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.primary_image_id AS category_image_id, "
        . "categories.synopsis AS category_synopsis, "
        . "categories.description AS category_description, "
        . "classes.code, "
        . "classes.name, "
        . "classes.permalink, "
        . "classes.sequence, "
        . "classes.flags, ";
    if( $festival['earlybird'] >= 0 ) {
        $strsql .= "CONCAT('$', FORMAT(classes.earlybird_fee, 2)) AS fee ";
    } else {
        $strsql .= "CONCAT('$', FORMAT(classes.fee, 2)) AS fee ";
    }
    $strsql .= "FROM ciniki_musicfestival_categories AS categories "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $section['id']) . "' "
        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY categories.sequence, categories.name, classes.sequence, classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'categories', 'fname'=>'category_id', 
            'fields'=>array('name'=>'category_name', 'image_id'=>'category_image_id', 'synopsis'=>'category_synopsis', 'description'=>'category_description')),
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 'fee')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        $categories = $rc['categories'];
        foreach($categories as $category) {
            $blocks[] = array(
                'type' => 'text', 
                'title' => $category['name'], 
                'content' => ($category['description'] != '' ? $category['description'] : ($category['synopsis'] != '' ? $category['synopsis'] : ' ')),
                );
            if( isset($category['classes']) && count($category['classes']) > 0 ) {
                //
                // FIXME: Check if online registrations enabled, and online registrations enabled for this class
                //
                if( ($festival['flags']&0x01) == 0x01 ) {
                    foreach($category['classes'] as $cid => $class) {
                        $category['classes'][$cid]['register'] = "<a href='" . $args['base_url'] . "/registrations?r=new&cl=" . $class['uuid'] . "'>Register</a>";
                    }
                    $blocks[] = array(
                        'type' => 'table', 
                        'section' => 'classes', 
                        'headers' => 'no',
                        'columns' => array(
                            array('label'=>'Code', 'field'=>'code', 'class'=>''),
                            array('label'=>'Course', 'field'=>'name', 'class'=>''),
                            array('label'=>'Fee', 'field'=>'fee', 'class'=>'aligncenter'),
                            array('label'=>'', 'field'=>'register', 'class'=>'alignright'),
                            ),
                        'rows' => $category['classes'],
                        );
                } else {
                    $blocks[] = array(
                        'type' => 'table', 
                        'section' => 'classes', 
                        'headers' => 'no',
                        'columns' => array(
                            array('label'=>'', 'field'=>'code', 'class'=>''),
                            array('label'=>'', 'field'=>'name', 'class'=>''),
                            array('label'=>'Fee', 'field'=>'fee', 'class'=>'aligncenter'),
                            ),
                        'rows' => $category['classes'],
                        );
                }
            }
        }
    }


    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
