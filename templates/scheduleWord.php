<?php
//
// Description
// ===========
// This method will produce a PDF of the class.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_scheduleWord(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');

    //
    // Load the tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    $division_date_format = '%W, %M %D, %Y';
    if( isset($festival['schedule-date-format']) && $festival['schedule-date-format'] != '' ) {
        $division_date_format = $festival['schedule-date-format'];
    }

    //
    // Load the adjudicators
    //
    if( isset($args['section_adjudicator_bios'])
        && $args['section_adjudicator_bios'] == 'yes' 
        ) {
        $strsql = "SELECT adjudicators.id, "
            . "customers.display_name AS name, "
            . "adjudicators.image_id, "
            . "adjudicators.description "
            . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "adjudicators.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'adjudicators', 'fname'=>'id', 'fields'=>array('id', 'name', 'image_id', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.980', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
        }
        $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();
    }

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, "
        . "ssections.sponsor_settings, "
        . "ssections.provincial_settings, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $strsql .= "divisions.adjudicator_id AS adjudicator_id, ";
        $strsql .= "members.name AS member_name, ";
    } elseif( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x0800) ) {
        $strsql .= "divisions.adjudicator_id AS adjudicator_id, ";
        $strsql .= "'' AS member_name, ";
    } else {
        $strsql .= "ssections.adjudicator1_id AS adjudicator_id, ";
        $strsql .= "'' AS member_name, ";
    }
    $strsql .= "customers.display_name AS adjudicator, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "locations.name AS location, "
        . "CONCAT_WS(' ', divisions.division_date, timeslots.slot_time) AS division_sort_key, "
        . "DATE_FORMAT(divisions.division_date, '" . ciniki_core_dbQuote($ciniki, $division_date_format) . "') AS division_date_text, ";
    if( isset($festival['schedule-separate-classes']) && $festival['schedule-separate-classes'] == 'yes' ) {
        $strsql .= "CONCAT_WS('-', timeslots.id, classes.id) AS timeslot_id, ";
    } else {
        $strsql .= "timeslots.id AS timeslot_id, ";
    }
    $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.groupname AS timeslot_groupname, "
        . "timeslots.description, "
        . "timeslots.start_num, "
        . "registrations.id AS reg_id, ";
    if( isset($festival['schedule-include-pronouns']) && $festival['schedule-include-pronouns'] == 'yes' ) {
        $strsql .= "registrations.pn_display_name AS display_name, "
            . "registrations.pn_public_name AS public_name, ";
    } else {
        $strsql .= "registrations.display_name, "
            . "registrations.public_name, ";
    }
    $strsql .= "registrations.title1, "
        . "registrations.title2, "
        . "registrations.title3, "
        . "registrations.title4, "
        . "registrations.title5, "
        . "registrations.title6, "
        . "registrations.title7, "
        . "registrations.title8, "
        . "registrations.composer1, "
        . "registrations.composer2, "
        . "registrations.composer3, "
        . "registrations.composer4, "
        . "registrations.composer5, "
        . "registrations.composer6, "
        . "registrations.composer7, "
        . "registrations.composer8, "
        . "registrations.movements1, "
        . "registrations.movements2, "
        . "registrations.movements3, "
        . "registrations.movements4, "
        . "registrations.movements5, "
        . "registrations.movements6, "
        . "registrations.movements7, "
        . "registrations.movements8, "
        . "registrations.video_url1, "
        . "registrations.video_url2, "
        . "registrations.video_url3, "
        . "registrations.video_url4, "
        . "registrations.video_url5, "
        . "registrations.video_url6, "
        . "registrations.video_url7, "
        . "registrations.video_url8, "
        . "registrations.participation, "
        . "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS timeslot_time, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.name AS category_name, "
        . "sections.name AS syllabus_section_name "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_adjudicators AS adjudicators ON (";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x0800) ) {
        $strsql .= "divisions.adjudicator_id = adjudicators.id ";
    } else {
        $strsql .= "ssections.adjudicator1_id = adjudicators.id ";
    }
    $strsql .= "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "( "
                . "timeslots.id = registrations.timeslot_id "
                . "OR timeslots.id = registrations.finals_timeslot_id "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $strsql .= "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
            . "registrations.member_id = members.id "
            . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    }
    $strsql .= "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id " 
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id " 
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND ssections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    if( isset($args['division_id']) && $args['division_id'] > 0 ) {
        $strsql .= "AND divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['division_id']) . "' ";
    }
    if( isset($args['published']) && $args['published'] == 'yes' ) {
        $strsql .= "AND (ssections.flags&0x10) = 0x10 ";
    }
    if( isset($args['ipv']) && $args['ipv'] == 'inperson' ) {
//        $strsql .= "AND (registrations.participation < 1 || ISNULL(registrations.participation) ) ";
        $strsql .= "AND (registrations.participation = 0 OR registrations.participation = 2) ";
    } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    }
    $strsql .= "ORDER BY ssections.sequence, ssections.name, divisions.division_date, divisions.name, divisions.id, slot_time, timeslots.name, timeslots.id, registrations.timeslot_sequence, class_code, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'sponsor_settings', 'provincial_settings', 'adjudicator_id'),
            'json'=>array('sponsor_settings', 'provincial_settings'),
            ),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 
                'location', 'adjudicator_id', 'adjudicator',
                'sort_key' => 'division_sort_key',
                ),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'groupname'=>'timeslot_groupname', 'time'=>'slot_time_text', 
                'description', 'class_code', 'class_name', 'category_name', 'syllabus_section_name', 'start_num',
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 'participation', 'timeslot_time', 'member_name',
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Build word document
    //
    require_once($ciniki['config']['core']['lib_dir'] . '/PHPWord/bootstrap.php');

    $PHPWord = new \PhpOffice\PhpWord\PhpWord();
    $PHPWord->addTitleStyle(1, array('bold'=>true, 'size'=>20), array('spaceBefore'=>240, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(2, array('bold'=>true, 'size'=>16), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $PHPWord->addTitleStyle(3, array('bold'=>false, 'size'=>16), array('spaceBefore'=>120, 'spaceAfter'=>120));
    $PHPWord->addParagraphStyle('Timeslots', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>240,
        'size' => 14,
        'indentation' => ['left' => 1500, 'hanging' => 1500],
        'tabs' => array(
            new \PhpOffice\PhpWord\Style\Tab('left', 1500),
            )),
    );
    $PHPWord->addParagraphStyle('Registrations', array('align' => 'left', 'spaceAfter' => 0, 'spaceBefore'=>60,
        'indentation' => ['left' => 3500, 'hanging' => 2500],
        'tabs' => array(
            new \PhpOffice\PhpWord\Style\Tab('left', 3500),
            )),
    );
//    $PHPWord->addParagraphStyle('pTitles', array('align' => 'left', 'spaceAfter' => 0));
    $PHPWord->addFontStyle('Timeslot Font', ['bold'=>true]);
//    $PHPWord->addParagraphStyle('pNotes', ['align' => 'left', 'spaceAfter' => 0, 'indentation' => ['left' => 500]]);
    $style_table = array('cellMargin'=>80, 'borderColor'=>'aaaaaa', 'borderSize'=>6);
    $style_header = array('borderSize'=>6, 'borderColor'=>'aaaaaa', 'bgColor'=>'dddddd', 'valign'=>'center');
    $style_cell = array('borderSize'=>6, 'borderColor'=>'aaaaaa', 'valign'=>'center', 'bgcolor'=>'ffffff');
    $style_header_font = array('bold'=>true, 'spaceAfter'=>20);
    $style_cell_font = array();
    $style_header_pleft = array('align'=>'left');
    $style_header_pright = array('align'=>'right');
    $style_cell_pleft = array('align'=>'left');
    $style_cell_pright = array('align'=>'right');

    $sectionWord = $PHPWord->addSection([
        'marginTop' => 1000,
        'marginBottom' => 1000,
        'marginLeft' => 1000,
        'marginRight' => 1000,
        'orientation' => 'portrait'
        ]);
//    $sectionWord->setMarginLeft(5);
//    $sectionWord->setMarginRight(5);

    $filename = 'Schedule'; 
    $newpage = 'yes';
    $continued_str = ' (continued...)';
    foreach($sections as $section) {
        if( !isset($section['divisions']) ) {
            continue;
        }
    
        if( $newpage == 'no' ) { 
            $sectionWord->addPageBreak();
        }
        $newpage = 'yes';

        //
        // Add adjudicator bio for section
        //
/*        if( isset($args['section_adjudicator_bios'])
            && $args['section_adjudicator_bios'] == 'yes' 
            && isset($adjudicators[$section['adjudicator_id']]['description']) 
            && $adjudicators[$section['adjudicator_id']]['description'] != '' 
            && $prev_adjudicator_id != $section['adjudicator_id']
            ) {
            $adjudicator = $adjudicators[$section['adjudicator_id']];
            
            //
            // Add Title
            //
            $pdf->SetFont('', 'B', '16');
            $pdf->MultiCell($fw, 0, 'Adjudicator ' . $adjudicator['name'], 0, 'C', 0, 1);
            $pdf->Ln(6);

            //
            // Add image
            //
            if( isset($adjudicator['image_id']) && $adjudicator['image_id'] > 0 ) {
                $rc = ciniki_images_loadImage($ciniki, $tnid, $adjudicator['image_id'], 'original');
                if( $rc['stat'] == 'ok' ) {
                    $image = $rc['image'];
                    $height = $image->getImageHeight();
                    $width = $image->getImageWidth();
                    $image_ratio = $width/$height;
                    $img_width = 60; 
                    $h = ($height/$width) * $img_width;
                    $y = $pdf->getY();
                    $pdf->Image('@'.$image->getImageBlob(), ($fw-$img_width) + $pdf->left_margin, $y, $img_width, 0, 'JPEG', '', 'TL', 2, '150');
                    $pdf->setPageRegions(array(array('page'=>'', 'xt'=>($fw-$img_width) + $pdf->left_margin - 5, 'yt'=>$y, 'xb'=>($fw-$img_width) + $pdf->left_margin - 5, 'yb'=>$y+$h+2, 'side'=>'R')));
                    $pdf->setY($y-2.5);
                }
            }

            //
            // Add full bio
            //
            $pdf->SetFont('', '', 12);
            $pdf->MultiCell($fw, 10, $adjudicator['description'] . "\n", 0, 'J', false, 1, '', '', true, 0, false, true, 0, 'T', false);
            $prev_adjudicator_id = $section['adjudicator_id'];
        } */

        //
        // Sort the divisions
        //
        uasort($section['divisions'], function($a, $b) {
            return $a['sort_key'] < $b['sort_key'] ? -1 : 1;
            });

        //
        // Output the divisions
        //
        foreach($section['divisions'] as $division) {
            //
            // Skip empty divisions
            //
            if( !isset($division['timeslots']) ) {
                continue;
            }

            if( $newpage == 'no' && isset($args['division_page_break']) && $args['division_page_break'] == 'yes' ) {
                $newpage = 'yes';
                $sectionWord->addPageBreak();
            } else {
                $newpage = 'no';
            }

            //
            // Output the Division Title
            //
            $fields = array();
            if( isset($args['division_header_format']) 
                && $args['division_header_format'] != 'default' && $args['division_header_format'] != '' 
                ) {
                $fields = explode('-', $args['division_header_format']);
                foreach($fields as $field) {
                    if( $field == 'namedate' ) {
                        $division[$field] = $division['name'] . ' - ' . $division['date'];
                    } elseif( $field == 'adjudicatorlocation' ) {
                        $division[$field] = $division['adjudicator'] . ' - ' . $division['location'];
                    } elseif( $field == 'adjudicatoraddress' ) {
                        // This one is old and can be removed when nobody useing adjudicatoraddress anymore
                        $division[$field] = $division['adjudicator'] . ' - ' . $division['location'];
                    }
                }
//                if( $continued == 'yes' ) {
//                    $division[$fields[0]] .= $continued_str;
//                }
                if( isset($args['division_header_labels']) && $args['division_header_labels'] == 'yes' ) {
                    foreach($fields as $fid => $field) {
                        if( $fid == 0 ) {
                            continue;   // No label on first field
                        }
                        if( $field == 'date' && $division[$field] != '' ) {
                            $division[$field] = 'Date: ' . $division[$field];
                        } elseif( $field == 'name' && $division[$field] != '' ) {
                            $division[$field] = 'Section: ' . $division[$field];
                        } elseif( $field == 'adjudicator' && $division[$field] != '' ) {
                            $division[$field] = 'Adjudicator: ' . $division[$field];
                        } elseif( $field == 'location' && $division[$field] != '' ) {
                            $division[$field] = 'Location: ' . $division[$field];
                        }
                    }
                }
            } else {
                // Default layout
                $fields = array('date-name', 'location');
                $division['date-name'] = $division['date'] . ' - ' . $division['name'];
//                if( $continued == 'yes' ) {
//                    $division['date-name'] .= $continued_str; //' (continued...)';
//                }
            }
            $heading = 1;
            foreach($fields as $field) {
                if( isset($division[$field]) && $division[$field] != '' ) {
                    $sectionWord->addTitle(htmlspecialchars($division[$field]), $heading);
                }
                $heading = 2;
            }
//            $sectionWord->addTitle($section['name'], 1);
//            $sectionWord->addTitle($division['adjudicator_name'], 2);
//            $sectionWord->addTitle($division['location_name'], 2);
//            $sectionWord->addTitle($division['division_date'], 2);

        
            $prev_time = '';
            foreach($division['timeslots'] as $timeslot) {
                $name = $timeslot['name'];
                if( $name == '' 
                    || (isset($festival['schedule-separate-classes']) && $festival['schedule-separate-classes'] == 'yes' && $timeslot['class_code'] != '' )
                    ) {
                    if( isset($festival['schedule-class-format']) 
                        && $festival['schedule-class-format'] == 'code-section-category-class' 
                        ) {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['syllabus_section_name'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                    } elseif( isset($festival['schedule-class-format']) 
                        && $festival['schedule-class-format'] == 'code-category-class' 
                        ) {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                    } else {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['class_name']; 
                    }
                }
                if( $timeslot['groupname'] != '' ) {
                    $name .= ' - ' . $timeslot['groupname'];
                }
                $time = $timeslot['time'];
                if( $prev_time == $time ) {
                    $time = '';
                    $border = '';
                } else {
                    $prev_time = $time;
                }

                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                    $sectionWord->addText(htmlspecialchars($name), 'Timeslot Font', 'Timeslots');
                } else {
                    $sectionWord->addText(htmlspecialchars($time . "\t" . $name), 'Timeslot Font', 'Timeslots');
                }

                $num = 1;
                if( isset($timeslot['start_num']) && $timeslot['start_num'] > 1 ) {
                    $num = $timeslot['start_num'];
                }

                if( isset($timeslot['registrations']) ) {
                    foreach($timeslot['registrations'] as $reg) {
                        if( isset($args['names']) && $args['names'] != 'private' ) {
                            $reg['name'] = $reg['public_name'];
                        }
                        if( isset($args['competitor_numbering']) && $args['competitor_numbering'] == 'yes' ) {
                            $reg['name'] = $num . '. ' . $reg['name'];
                        }
                        $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $reg, 1);
                        $title = isset($rc['title']) ? $rc['title'] : '';
                        $sectionWord->addText(htmlspecialchars($reg['name'] . "\t" . $title), null, 'Registrations');
                        for($i = 2; $i <= 8; $i++) {
                            $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $reg, $i);
                            if( isset($rc['title']) && $rc['title'] != '' ) {
                                $title = isset($rc['title']) ? $rc['title'] : '';
                                $sectionWord->addText(htmlspecialchars("\t" . $title), null, 'Registrations');
                            }
                        }
                        $num++;
                    }
                }
            }
        }
    }

    return array('stat'=>'ok', 'word'=>$PHPWord, 'filename'=>$filename);
}
?>
