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

    $setting_keys = array(
        'registration-parent-msg',
        'registration-teacher-msg',
        'registration-adult-msg',
        'competitor-parent-msg',
        'competitor-teacher-msg',
        'competitor-adult-msg',
        'competitor-group-parent-msg',
        'competitor-group-teacher-msg',
        'competitor-group-adult-msg',
        );
    $update_args = array();
    foreach($setting_keys as $k) {
        if( isset($old_festival['settings'][$k]['detail_value']) && $old_festival['settings'][$k]['detail_value'] != '' 
            && (!isset($new_festival['settings'][$k]['detail_value']) || $new_festival['settings'][$k]['detail_value'] == '') 
            ) {
            $update_args[$k] = $old_festival['settings'][$k]['detail_value'];
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
    // Copy syllabus
    //
    $strsql = "SELECT s.id AS sid, "
        . "s.name AS sn, "
        . "s.permalink AS sp, "
        . "s.sequence AS so, "
        . "s.flags AS sf, "
        . "s.primary_image_id AS si, "
        . "s.synopsis AS ss, "
        . "s.description AS sd, "
        . "c.id AS cid, "
        . "c.name AS cn, "
        . "c.permalink AS cp, "
        . "c.sequence AS co, "
        . "c.primary_image_id AS ci, "
        . "c.synopsis AS cs, "
        . "c.description AS cd, "
        . "i.id AS iid, "
        . "i.code, "
        . "i.name AS iname, "
        . "i.permalink AS ip, "
        . "i.sequence AS io, "
        . "i.flags, "
        . "i.earlybird_fee, "
        . "i.fee, "
        . "i.virtual_fee, "
        . "i.earlybird_plus_fee, "
        . "i.plus_fee, "
        . "trophies.trophy_id "
        . "FROM ciniki_musicfestival_sections AS s "
        . "LEFT JOIN ciniki_musicfestival_categories AS c ON ("
            . "s.id = c.section_id "
            . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS i ON ("
            . "c.id = i.category_id "
            . "AND i.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_trophy_classes AS trophies ON ("
            . "i.id = trophies.class_id "
            . "AND trophies.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE s.festival_id = '" . ciniki_core_dbQuote($ciniki, $old_festival_id) . "' "
        . "AND s.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY s.sequence, s.date_added, c.sequence, c.name, c.date_added, i.sequence, i.date_added "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'sid',
            'fields'=>array('name'=>'sn', 'permalink'=>'sp', 'sequence'=>'so', 'flags'=>'sf', 'primary_image_id'=>'si', 'synopsis'=>'ss', 'description'=>'sd')),
        array('container'=>'categories', 'fname'=>'cid',
            'fields'=>array('name'=>'cn', 'permalink'=>'cp', 'sequence'=>'co', 'primary_image_id'=>'ci', 'synopsis'=>'cs', 'description'=>'cd')),
        array('container'=>'classes', 'fname'=>'iid',
            'fields'=>array('code', 'name'=>'iname', 'permalink'=>'ip', 'sequence'=>'io', 'flags', 'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee')),
        array('container'=>'trophies', 'fname'=>'trophy_id',
            'fields'=>array('trophy_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.530', 'msg'=>'Previous syllabus not found', 'err'=>$rc['err']));
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
