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
function ciniki_musicfestivals__festivalCopy(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    if( !isset($args['festival_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.507', 'msg'=>'No festival specified'));
    }
    $festival_id = $args['festival_id'];
    if( !isset($args['old_festival_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.508', 'msg'=>'No existing festival specified'));
    }
    $old_festival_id = $args['old_festival_id'];

    //
    // Copy festival settings that aren't year specific
    //
    $strsql = "SELECT festivals.id, "
        . "festivals.flags, "
        . "festivals.document_logo_id, "
        . "festivals.document_header_msg, "
        . "festivals.document_footer_msg, "
        . "festivals.comments_grade_label, "
        . "festivals.comments_footer_msg, "
        . "settings.detail_key, "
        . "settings.detail_value "
        . "FROM ciniki_musicfestivals AS festivals "
        . "LEFT JOIN ciniki_musicfestival_settings AS settings ON ("
            . "festivals.id = settings.festival_id "
            . "AND settings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE (festivals.id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "OR festivals.id = '" . ciniki_core_dbQuote($ciniki, $old_festival_id) . "' "
            . ") "
        . "AND festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY festivals.id, settings.detail_key "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('id', 'flags', 'document_logo_id', 'document_header_msg', 'document_footer_msg', 
                'comments_grade_label', 'comments_footer_msg'),
            ),
        array('container'=>'settings', 'fname'=>'detail_key', 'fields'=>array('detail_value')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.509', 'msg'=>'Unable to load festivals', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][$old_festival_id]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.510', 'msg'=>'Could not find old festival'));
    }
    if( !isset($rc['festivals'][$festival_id]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.511', 'msg'=>'Could not find new festival'));
    }
    $old_festival = $rc['festivals'][$old_festival_id];
    $new_festival = $rc['festivals'][$festival_id];

    $update_args = array();
    $new_flags = $new_festival['flags'];
    if( ($old_festival['flags']&0x20) == 0x20 && ($new_festival['flags']&0x20) == 0 ) {
        $new_flags = $old_festival['flags'] | 0x20;
    }
    if( ($old_festival['flags']&0x40) == 0x40 && ($new_festival['flags']&0x40) == 0 ) {
        $new_flags = $old_festival['flags'] | 0x40;
    }
    if( ($old_festival['flags']&0x80) == 0x80 && ($new_festival['flags']&0x80) == 0 ) {
        $new_flags = $old_festival['flags'] | 0x80;
    }
    if( ($old_festival['flags']&0x0100) == 0x0100 && ($new_festival['flags']&0x0100) == 0 ) {
        $new_flags = $old_festival['flags'] | 0x0100;
    }
    if( $new_flags != $new_festival['flags'] ) {
        $update_args['flags'] = $new_flags;
    }
    if( $old_festival['document_logo_id'] > 0 && $new_festival['document_logo_id'] == 0 ) {
        $update_args['document_logo_id'] = $old_festival['document_logo_id'];
    }
    if( $old_festival['document_header_msg'] != '' && $new_festival['document_header_msg'] == '' ) {
        $update_args['document_header_msg'] = $old_festival['document_header_msg'];
    }
    if( $old_festival['document_footer_msg'] != '' && $new_festival['document_footer_msg'] == '' ) {
        $update_args['document_footer_msg'] = $old_festival['document_footer_msg'];
    }
    if( $old_festival['comments_grade_label'] != '' && $new_festival['comments_grade_label'] == '' ) {
        $update_args['comments_grade_label'] = $old_festival['comments_grade_label'];
    }
    if( $old_festival['comments_footer_msg'] != '' && $new_festival['comments_footer_msg'] == '' ) {
        $update_args['comments_footer_msg'] = $old_festival['comments_footer_msg'];
    }
    if( count($update_args) > 0 ) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.festival', $festival_id, $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.468', 'msg'=>'Unable to update the festival', 'err'=>$rc['err']));
        }
    }

    $update_args = [];
    foreach($old_festival['settings'] as $k => $setting) {
        if( $setting['detail_value'] != '' 
            && (!isset($new_festival['settings'][$k]['detail_value']) || $new_festival['settings'][$k]['detail_value'] == '') 
            ) {
            error_log("$k => " . $setting['detail_value']);
            $update_args[$k] = $setting['detail_value'];
        }
    } 
    if( count($update_args) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'settingsUpdate');
        $rc = ciniki_musicfestivals_settingsUpdate($ciniki, $tnid, $festival_id, $update_args);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.469', 'msg'=>'Unable to update settings', 'err'=>$rc['err']));
        }
    }

    //
    // Get the existing syllabuses for the festival
    //
    $strsql = "SELECT syllabuses.id, "
        . "syllabuses.uuid, "
        . "syllabuses.name, "
        . "COUNT(sections.id) AS num_sections "
        . "FROM ciniki_musicfestival_syllabuses AS syllabuses "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "syllabuses.id = sections.syllabus_id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE syllabuses.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND syllabuses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "GROUP BY syllabuses.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'syllabuses', 'fname'=>'name', 'fields'=>array('id', 'uuid', 'name', 'num_sections')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1055', 'msg'=>'Unable to load syllabuses', 'err'=>$rc['err']));
    }
    $new_festival_syllabuses = isset($rc['syllabuses']) ? $rc['syllabuses'] : array();

    //
    // Copy syllabus
    //
    $strsql = "SELECT syllabuses.id AS syllabus_id, "
        . "syllabuses.name AS syllabus_name, "
        . "syllabuses.rules AS syllabus_rules, "
        . "sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "sections.permalink AS section_permalink, "
        . "sections.sequence AS section_sequence, "
        . "sections.flags AS section_flags, "
        . "sections.primary_image_id AS section_image_id, "
        . "sections.synopsis AS section_synopsis, "
        . "sections.description AS section_description, "
        . "sections.live_description AS section_live_description, "
        . "sections.virtual_description AS section_virtual_description, "
        . "sections.recommendations_description AS section_recommendations_description, "
        . "sections.latefees_start_amount, "
        . "sections.latefees_daily_increase, "
        . "sections.latefees_days, "
        . "sections.adminfees_amount, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.permalink AS category_permalink, "
        . "categories.groupname, "
        . "categories.sequence AS category_sequence, "
        . "categories.primary_image_id AS category_image_id, "
        . "categories.synopsis AS category_synopsis, "
        . "categories.description AS category_description, "
        . "classes.id AS class_id, "
        . "classes.code, "
        . "classes.name AS class_name, "
        . "classes.permalink AS class_permalink, "
        . "classes.sequence AS class_sequence, "
        . "classes.flags, "
        . "classes.feeflags, "
        . "classes.titleflags, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee, "
        . "classes.earlybird_plus_fee, "
        . "classes.plus_fee, "
        . "classes.min_competitors, "
        . "classes.max_competitors, "
        . "classes.min_titles, "
        . "classes.max_titles, "
        . "classes.provincials_code, "
        . "classes.synopsis AS class_synopsis, "
        . "classes.schedule_seconds, "
        . "classes.schedule_at_seconds, "
        . "classes.schedule_ata_seconds, "
        . "classes.keywords, "
        . "classes.options, "
        . "trophies.trophy_id "
        . "FROM ciniki_musicfestival_syllabuses AS syllabuses "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "syllabuses.id = sections.syllabus_id "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $old_festival_id) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_trophy_classes AS trophies ON ("
            . "classes.id = trophies.class_id "
            . "AND trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE syllabuses.festival_id = '" . ciniki_core_dbQuote($ciniki, $old_festival_id) . "' "
        . "AND syllabuses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY syllabuses.sequence, syllabuses.name, syllabuses.id, "
            . "sections.sequence, sections.date_added, "
            . "categories.sequence, categories.name, categories.date_added, "
            . "classes.sequence, classes.date_added "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'syllabuses', 'fname'=>'syllabus_id',
            'fields'=>array('name'=>'syllabus_name', 'rules'=>'syllabus_rules',
                )),
        array('container'=>'sections', 'fname'=>'section_id',
            'fields'=>array('name'=>'section_name', 'permalink'=>'section_permalink', 'sequence'=>'section_sequence', 
                'syllabus_id',
                'flags'=>'section_flags', 'primary_image_id'=>'section_image_id', 
                'synopsis'=>'section_synopsis', 'description'=>'section_description', 
                'live_description'=>'section_live_description', 'virtual_description'=>'section_virtual_description',
                'recommendations_description'=>'section_recommendations_description',
                'latefees_start_amount', 'latefees_daily_increase', 'latefees_days', 'adminfees_amount',
                )),
        array('container'=>'categories', 'fname'=>'category_id',
            'fields'=>array('name'=>'category_name', 'permalink'=>'category_permalink', 'sequence'=>'category_sequence', 
                'groupname', 'primary_image_id'=>'category_image_id', 
                'synopsis'=>'category_synopsis', 'description'=>'category_description',
                )),
        array('container'=>'classes', 'fname'=>'class_id',
            'fields'=>array('code', 'name'=>'class_name', 'permalink'=>'class_permalink', 
                'synopsis'=>'class_synopsis', 'sequence'=>'class_sequence', 
// Provincials code will need to be updated after importing
                'provincials_code',   
                'flags', 'feeflags', 'titleflags', 'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee',
                'min_competitors', 'max_competitors', 'min_titles', 'max_titles', 'keywords', 'options',
                )),
        array('container'=>'trophies', 'fname'=>'trophy_id',
            'fields'=>array('trophy_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.530', 'msg'=>'Previous syllabus not found', 'err'=>$rc['err']));
    }

    if( isset($rc['syllabuses']) ) {
        $syllabuses = $rc['syllabuses'];
        foreach($syllabuses as $syllabus) {
            if( isset($new_festival_syllabuses[$syllabus['name']]) ) {
                $syllabus_id = $new_festival_syllabuses[$syllabus['name']]['id'];
            } else {
                $syllabus['festival_id'] = $festival_id;
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.syllabus', $syllabus, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $syllabus_id = $rc['id'];
                $new_festival_syllabuses[$syllabus['name']] = [
                    'id' => $rc['id'],
                    'festival_id' => $festival_id,
                    'name' => $syllabus['name'],
                    ];
            }
            
            foreach($syllabus['sections'] as $section) {
                //
                // Add the section
                //
                $section['festival_id'] = $festival_id;
                $section['syllabus_id'] = $syllabus_id;
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
                                $class_id = $rc['id'];

                                //
                                // Add the trophies
                                //
                                if( isset($class['trophies']) ) {
                                    foreach($class['trophies'] as $trophy) {
                                        $trophy['class_id'] = $class_id;
                                        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.trophyclass', $trophy, 0x04);
                                        if( $rc['stat'] != 'ok' ) {
                                            return $rc;
                                        }
                                        
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    //
    // Copy over certficiate and fields
    //
    $strsql = "SELECT certificates.id, "
        . "certificates.name, "
        . "certificates.image_id, "
        . "certificates.orientation, "
        . "certificates.min_score, "
        . "fields.id AS fid, "
        . "fields.name AS fname, "
        . "fields.field AS field, "
        . "fields.xpos, "
        . "fields.ypos, "
        . "fields.width, "
        . "fields.height, "
        . "fields.font, "
        . "fields.size, "
        . "fields.style, "
        . "fields.align, "
        . "fields.valign, "
        . "fields.color, "
        . "fields.bgcolor, "
        . "fields.text "
        . "FROM ciniki_musicfestival_certificates AS certificates "
        . "LEFT JOIN ciniki_musicfestival_certificate_fields AS fields ON ("
            . "certificates.id = fields.certificate_id "
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE certificates.festival_id = '" . ciniki_core_dbQuote($ciniki, $old_festival_id) . "' "
        . "AND certificates.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'certificates', 'fname'=>'id', 
            'fields'=>array('name', 'image_id', 'orientation', 'min_score'), 
            ),
        array('container'=>'fields', 'fname'=>'fid', 
            'fields'=>array('name'=>'fname', 'field', 'xpos', 'ypos', 'width', 
                'height', 'font', 'size', 'style', 'align', 'valign', 'color', 'bgcolor', 'text'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.465', 'msg'=>'Unable to load certificates', 'err'=>$rc['err']));
    }
    $certificates = isset($rc['certificates']) ? $rc['certificates'] : array();

    foreach($certificates as $cert) {
        //
        // Add the certificate
        //
        $cert['festival_id'] = $festival_id;
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.certificate', $cert, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $cert_id = $rc['id'];
        if( isset($cert['fields']) ) {
            foreach($cert['fields'] as $field) {
                $field['certificate_id'] = $cert_id;
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.certfield', $field, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    //
    // Get the lists and sections, NOT the entries
    //
    $strsql = "SELECT lists.id, "
        . "lists.name, "
        . "lists.category, "
        . "lists.intro, "
        . "sections.id AS sid, "
        . "sections.name AS sname, "
        . "sections.sequence AS sequence "
        . "FROM ciniki_musicfestival_lists AS lists "
        . "LEFT JOIN ciniki_musicfestival_list_sections AS sections ON ("
            . "lists.id = sections.list_id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE lists.festival_id = '" . ciniki_core_dbQuote($ciniki, $old_festival_id) . "' "
        . "AND lists.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'lists', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'category', 'intro'),
            ),
        array('container'=>'sections', 'fname'=>'sid', 
            'fields'=>array('sid', 'name', 'sequence'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.466', 'msg'=>'Unable to load lists', 'err'=>$rc['err']));
    }
    $lists = isset($rc['lists']) ? $rc['lists'] : array();

    //
    // Copy over Lists and List Sections, but NO list entries
    //
    foreach($lists as $list) {
        //
        // Add the list
        //
        $list['festival_id'] = $festival_id;
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.list', $list, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $list_id = $rc['id'];
        if( isset($list['sections']) ) {
            foreach($list['sections'] as $section) {
                $section['list_id'] = $list_id;
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.listsection', $section, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
