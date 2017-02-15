<?php
//
// Description
// ===========
// This method will return all the information about an festival.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the festival is attached to.
// festival_id:          The ID of the festival to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_festivalGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'schedule'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule'),
        'sections'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sections'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        'classes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Classes'),
        'registrations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registrations'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sections'),
        'teacher_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Teacher'),
        'adjudicators'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Adjudicators'),
        'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
        'sponsors'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sponsors'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['business_id'], 'ciniki.musicfestivals.festivalGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load conference maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Setup the arrays for the lists of next/prev ids
    //
    $nplists = array(
        'sections'=>array(),
        'categories'=>array(),
        'classes'=>array(),
        'registrations'=>array(),
        'adjudicators'=>array(),
        'files'=>array(),
        'sponsors'=>array(),
        );

    //
    // Return default for new Festival
    //
    if( $args['festival_id'] == 0 ) {
        $festival = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'start_date'=>'',
            'end_date'=>'',
            'status'=>'10',
            'flags'=>'0',
            'primary_image_id'=>'0',
            'header_logo_id'=>'0',
            'description'=>'',
            'num_registrations'=>0,
            'sponsors'=>array(),
        );
    }

    //
    // Get the details for an existing Festival
    //
    else {
        $strsql = "SELECT ciniki_musicfestivals.id, "
            . "ciniki_musicfestivals.name, "
            . "ciniki_musicfestivals.permalink, "
            . "ciniki_musicfestivals.start_date, "
            . "ciniki_musicfestivals.end_date, "
            . "ciniki_musicfestivals.status, "
            . "ciniki_musicfestivals.flags, "
            . "ciniki_musicfestivals.primary_image_id, "
            . "ciniki_musicfestivals.description, "
            . "ciniki_musicfestivals.document_logo_id, "
            . "ciniki_musicfestivals.document_header_msg, "
            . "ciniki_musicfestivals.document_footer_msg "
            . "FROM ciniki_musicfestivals "
            . "WHERE ciniki_musicfestivals.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_musicfestivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'festivals', 'fname'=>'id', 
                'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'status', 'flags', 'primary_image_id', 'description', 
                    'document_logo_id', 'document_header_msg', 'document_footer_msg'),
                'utctotz'=>array('start_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'end_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.8', 'msg'=>'Festival not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['festivals'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.9', 'msg'=>'Unable to find Festival'));
        }
        $festival = $rc['festivals'][0];

        //
        // Get the number of registrations
        //
        $festival['num_registrations'] = '';

        //
        // Get the list of sections
        //
        if( isset($args['sections']) && $args['sections'] == 'yes' ) {
            $strsql = "SELECT sections.id, "
                . "sections.festival_id, "
                . "sections.name, "
                . "sections.permalink, "
                . "sections.sequence, "
                . "COUNT(registrations.id) AS num_registrations "
                . "FROM ciniki_musicfestival_sections AS sections "
                . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "sections.id = categories.section_id "
                    . "AND categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "classes.id = registrations.class_id "
                    . "AND registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "WHERE sections.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "GROUP BY sections.id "
                . "ORDER BY sections.sequence, sections.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'festival_id', 'name', 'permalink', 'sequence', 'num_registrations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['sections']) ) {
                $festival['sections'] = $rc['sections'];
                foreach($festival['sections'] as $iid => $section) {
                    $nplists['sections'][] = $section['id'];
                }
            } else {
                $festival['sections'] = array();
            }
        }

        //
        // Get the list of categories
        //
        if( isset($args['categories']) && $args['categories'] == 'yes' ) {
            $strsql = "SELECT categories.id, "
                . "categories.festival_id, "
                . "categories.section_id, "
                . "sections.name AS section_name, "
                . "categories.name, "
                . "categories.permalink, "
                . "categories.sequence, "
                . "COUNT(registrations.class_id) AS num_registrations "
                . "FROM ciniki_musicfestival_sections AS sections "
                . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "sections.id = categories.section_id "
                    . "AND categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "classes.id = registrations.class_id "
                    . "AND registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "WHERE categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "GROUP BY sections.id, categories.id "
                . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'categories', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'section_id', 'section_name', 'name', 'permalink', 'sequence', 'num_registrations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['categories']) ) {
                $festival['categories'] = $rc['categories'];
                foreach($festival['categories'] as $iid => $category) {
                    $nplists['categories'][] = $category['id'];
                }
            } else {
                $festival['categories'] = array();
            }
        }

        //
        // Get the list of classes
        //
        if( isset($args['classes']) && $args['classes'] == 'yes' ) {
            $strsql = "SELECT classes.id, "
                . "classes.festival_id, "
                . "classes.category_id, "
                . "sections.name AS section_name, "
                . "categories.name AS category_name, "
                . "classes.code, "
                . "classes.name, "
                . "classes.permalink, "
                . "classes.sequence, "
                . "classes.flags, "
                . "classes.fee, "
                . "COUNT(registrations.id) AS num_registrations "
                . "FROM ciniki_musicfestival_sections AS sections "
                . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "sections.id = categories.section_id "
                    . "AND categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "classes.id = registrations.class_id "
                    . "AND registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "WHERE sections.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "GROUP BY classes.id "
                . "ORDER BY sections.sequence, sections.name, "
                    . "categories.sequence, categories.name, "
                    . "classes.sequence, classes.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'classes', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'category_id', 'section_name', 'category_name', 'code', 'name', 'permalink', 'sequence', 'flags', 'fee', 'num_registrations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['classes']) ) {
                $festival['classes'] = $rc['classes'];
                foreach($festival['classes'] as $iid => $class) {
                    $festival['classes'][$iid]['fee'] = numfmt_format_currency($intl_currency_fmt, $class['fee'], $intl_currency);
                    $nplists['classes'][] = $class['id'];
                }
            } else {
                $festival['classes'] = array();
            }
        }

        //
        // Get the list of registrations
        //
        if( isset($args['registrations']) && $args['registrations'] == 'yes' ) {
            $strsql = "SELECT registrations.id, "
                . "registrations.festival_id, "
                . "sections.id AS section_id, "
                . "registrations.teacher_customer_id, "
                . "teachers.display_name AS teacher_name, "
                . "registrations.billing_customer_id, "
                . "registrations.rtype, "
                . "registrations.rtype AS rtype_text, "
                . "registrations.status, "
                . "registrations.status AS status_text, "
                . "registrations.display_name, "
                . "registrations.class_id, "
                . "classes.code AS class_code, "
                . "classes.name AS class_name, "
                . "registrations.title, "
                . "registrations.perf_time, "
                . "FORMAT(registrations.fee, 2) AS fee, "
                . "registrations.payment_type "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "LEFT JOIN ciniki_customers AS teachers ON ("
                    . "registrations.teacher_customer_id = teachers.id "
                    . "AND teachers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "registrations.class_id = classes.id "
                    . "AND classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "classes.category_id = categories.id "
                    . "AND categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                    . "categories.section_id = sections.id "
                    . "AND sections.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "";
            if( isset($args['section_id']) && $args['section_id'] > 0 ) {
                $strsql .= "HAVING section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
            } elseif( isset($args['teacher_customer_id']) && $args['teacher_customer_id'] > 0 ) {
                $strsql .= "AND registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['teacher_customer_id']) . "' ";
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'registrations', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'teacher_customer_id', 'teacher_name', 'billing_customer_id', 'rtype', 'rtype_text', 'status', 'status_text', 'display_name', 
                        'class_id', 'class_code', 'class_name', 'title', 'perf_time', 'fee', 'payment_type'),
                    'maps'=>array(
                        'rtype_text'=>$maps['registration']['rtype'],
                        'status_text'=>$maps['registration']['status'],
                        'payment_type'=>$maps['registration']['payment_type'],
                        ),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['registrations']) ) {
                $festival['registrations'] = $rc['registrations'];
                $festival['nplists']['registrations'] = array();
                $total = 0;
                foreach($festival['registrations'] as $iid => $registration) {
                    $festival['nplists']['registrations'][] = $registration['id'];
                }
            } else {
                $festival['registrations'] = array();
                $festival['nplists']['registrations'] = array();
            }

            //
            // Get the list of teachers and number of registrations
            //
            $strsql = "SELECT registrations.teacher_customer_id, "
                . "customers.display_name, "
                . "COUNT(registrations.id) AS num_registrations "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "LEFT JOIN ciniki_customers AS customers ON ("
                    . "registrations.teacher_customer_id = customers.id "
                    . "AND customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "WHERE registrations.teacher_customer_id != 0 "
                . "GROUP BY registrations.teacher_customer_id "
                . "ORDER BY customers.display_name "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'teachers', 'fname'=>'teacher_customer_id', 'fields'=>array('id'=>'teacher_customer_id', 'display_name', 'num_registrations')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['teachers']) ) {
                $festival['registration_teachers'] = $rc['teachers'];
            }
        }

        //
        // Get the list of adjudicators
        //
        if( isset($args['adjudicators']) && $args['adjudicators'] == 'yes' ) {
            $strsql = "SELECT ciniki_musicfestival_adjudicators.id, "
                . "ciniki_musicfestival_adjudicators.festival_id, "
                . "ciniki_musicfestival_adjudicators.customer_id, "
                . "ciniki_customers.display_name "
                . "FROM ciniki_musicfestival_adjudicators "
                . "LEFT JOIN ciniki_customers ON ("
                    . "ciniki_musicfestival_adjudicators.customer_id = ciniki_customers.id "
                    . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . ") "
                . "WHERE ciniki_musicfestival_adjudicators.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'adjudicators', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'customer_id', 'name'=>'display_name')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['adjudicators']) ) {
                $festival['adjudicators'] = $rc['adjudicators'];
                foreach($festival['adjudicators'] as $iid => $adjudicator) {
                    $festival['nplists']['adjudicators'][] = $adjudicator['id'];
                }
            } else {
                $festival['adjudicators'] = array();
            }
        }

        //
        // Get the list of files
        //
        if( isset($args['files']) && $args['files'] == 'yes' ) {
            $strsql = "SELECT id, name "
                . "FROM ciniki_musicfestival_files "
                . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'files', 'fname'=>'id', 'fields'=>array('id', 'name')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['files']) ) {
                $festival['files'] = $rc['files'];
            } else {
                $festival['files'] = array();
            }
        }

        //
        // Get any sponsors for this festival, and that references for sponsors is enabled
        //
        if( isset($args['sponsors']) && $args['sponsors'] == 'yes' 
            && isset($ciniki['business']['modules']['ciniki.sponsors']) 
            && ($ciniki['business']['modules']['ciniki.sponsors']['flags']&0x02) == 0x02
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'hooks', 'sponsorList');
            $rc = ciniki_sponsors_hooks_sponsorList($ciniki, $args['business_id'], 
                array('object'=>'ciniki.musicfestivals.festival', 'object_id'=>$args['festival_id']));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['sponsors']) ) {
                $festival['sponsors'] = $rc['sponsors'];
            }
        }

        //
        // Get the number of registrations 
        //
        $strsql = "SELECT COUNT(id) "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'registrations');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['registrations']) ) {
            $festival['num_registrations'] = $rc['registrations'];
        }
    }

    return array('stat'=>'ok', 'festival'=>$festival, 'nplists'=>$nplists);
}
?>
