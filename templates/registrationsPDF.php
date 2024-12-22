<?php
//
// Description
// ===========
// This method will produce a PDF of the teachers registrations by parent.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_registrationsPDF(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'template', 'registrationPDF');

    //
    // Make sure festival_id was passed in
    //
    if( !isset($args['festival_id']) || $args['festival_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.876', 'msg'=>'No festival specified'));
    }

    //
    // Make sure teacher_customer_id was passed in
    //
    if( !isset($args['registration_ids']) || !is_array($args['registration_ids']) || count($args['registration_ids']) <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.877', 'msg'=>'No registrations specified'));
    }

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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

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

    //
    // Load the registrations
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.festival_id, "
        . "registrations.teacher_customer_id, "
        . "registrations.billing_customer_id, "
        . "registrations.accompanist_customer_id, "
        . "registrations.member_id, "
        . "registrations.rtype, "
        . "registrations.status, "
        . "registrations.flags, "
        . "registrations.invoice_id, "
        . "registrations.display_name, "
        . "registrations.competitor1_id, "
        . "registrations.competitor2_id, "
        . "registrations.competitor3_id, "
        . "registrations.competitor4_id, "
        . "registrations.competitor5_id, "
        . "registrations.class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.name AS category_name, "
        . "sections.name AS section_name, "
        . "registrations.title1, "
        . "registrations.composer1, "
        . "registrations.movements1, "
        . "registrations.perf_time1, "
        . "registrations.title2, "
        . "registrations.composer2, "
        . "registrations.movements2, "
        . "registrations.perf_time2, "
        . "registrations.title3, "
        . "registrations.composer3, "
        . "registrations.movements3, "
        . "registrations.perf_time3, "
        . "registrations.title4, "
        . "registrations.composer4, "
        . "registrations.movements4, "
        . "registrations.perf_time4, "
        . "registrations.title5, "
        . "registrations.composer5, "
        . "registrations.movements5, "
        . "registrations.perf_time5, "
        . "registrations.title6, "
        . "registrations.composer6, "
        . "registrations.movements6, "
        . "registrations.perf_time6, "
        . "registrations.title7, "
        . "registrations.composer7, "
        . "registrations.movements7, "
        . "registrations.perf_time7, "
        . "registrations.title8, "
        . "registrations.composer8, "
        . "registrations.movements8, "
        . "registrations.perf_time8, "
        . "registrations.fee, "
/*        . "registrations.timeslot_id, "
        . "registrations.finals_timeslot_id, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time, ";
        $strsql .= "TIME_FORMAT(registrations.finals_timeslot_time, '%l:%i %p') AS finals_slot_time, ";
    } else {
        $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time, ";
        $strsql .= "TIME_FORMAT(finals_timeslots.slot_time, '%l:%i %p') AS finals_slot_time, ";
    }
    $strsql .= "IFNULL(divisions.name, '') AS division_name, "
        . "IFNULL(finals_divisions.name, '') AS finals_division_name, "
        . "IFNULL(DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y'), '') AS division_date, "
        . "IFNULL(DATE_FORMAT(finals_divisions.division_date, '%W, %M %D, %Y'), '') AS finals_division_date, "
        . "IFNULL(locations.name, '') AS location_name, "
        . "IFNULL(finals_locations.name, '') AS finals_location_name, "
        . "IFNULL(ssections.name, '') AS section_name, "
        . "IFNULL(finals_ssections.name, '') AS finals_section_name, " */
        . "registrations.participation, "
        . "registrations.video_url1, "
        . "registrations.video_url2, "
        . "registrations.video_url3, "
        . "registrations.video_url4, "
        . "registrations.video_url5, "
        . "registrations.video_url6, "
        . "registrations.video_url7, "
        . "registrations.video_url8, "
        . "registrations.music_orgfilename1, "
        . "registrations.music_orgfilename2, "
        . "registrations.music_orgfilename3, "
        . "registrations.music_orgfilename4, "
        . "registrations.music_orgfilename5, "
        . "registrations.music_orgfilename6, "
        . "registrations.music_orgfilename7, "
        . "registrations.music_orgfilename8, "
        . "registrations.backtrack1, "
        . "registrations.backtrack2, "
        . "registrations.backtrack3, "
        . "registrations.backtrack4, "
        . "registrations.backtrack5, "
        . "registrations.backtrack6, "
        . "registrations.backtrack7, "
        . "registrations.backtrack8, "
        . "registrations.instrument, "
        . "registrations.mark, "
        . "registrations.placement, "
        . "registrations.level, "
        . "registrations.finals_mark, "
        . "registrations.finals_placement, "
        . "registrations.finals_level, "
        . "registrations.provincials_status, "
        . "registrations.provincials_position, "
        . "registrations.comments, "
        . "registrations.notes, "
        . "registrations.internal_notes "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
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
/*        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "registrations.timeslot_id = timeslots.id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "timeslots.sdivision_id = divisions.id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS finals_timeslots ON ("
            . "registrations.finals_timeslot_id = finals_timeslots.id "
            . "AND finals_timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
            . "divisions.ssection_id = ssections.id "
            . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS finals_divisions ON ("
            . "finals_timeslots.sdivision_id = finals_divisions.id "
            . "AND finals_divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_locations AS finals_locations ON ("
            . "finals_divisions.location_id = finals_locations.id "
            . "AND finals_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_sections AS finals_ssections ON ("
            . "finals_divisions.ssection_id = finals_ssections.id "
            . "AND finals_ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") " */
        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND registrations.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['registration_ids']) . ") "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'teacher_customer_id', 'billing_customer_id', 
                'accompanist_customer_id', 'member_id',
//                'timeslot_id', 'finals_timeslot_id', 
//                'slot_time', 'division_date', 'division_name', 'location_name', 'section_name',
//                'finals_slot_time', 'finals_division_date', 'finals_division_name', 'finals_location_name', 'finals_section_name',
                'rtype', 'status', 'flags', 'invoice_id', 'display_name', 
                'competitor1_id', 'competitor2_id', 'competitor3_id', 
                'competitor4_id', 'competitor5_id', 
                'class_id', 'class_code', 'class_name', 'category_name', 'section_name', 
                'title1', 'composer1', 'movements1', 'perf_time1', 
                'title2', 'composer2', 'movements2', 'perf_time2', 
                'title3', 'composer3', 'movements3', 'perf_time3', 
                'title4', 'composer4', 'movements4', 'perf_time4', 
                'title5', 'composer5', 'movements5', 'perf_time5', 
                'title6', 'composer6', 'movements6', 'perf_time6', 
                'title7', 'composer7', 'movements7', 'perf_time7', 
                'title8', 'composer8', 'movements8', 'perf_time8', 
                'fee',
                'participation', 
                'video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8',
                'music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3',  'music_orgfilename4', 
                'music_orgfilename5', 'music_orgfilename6',  'music_orgfilename7', 'music_orgfilename8',  
                'backtrack1', 'backtrack2', 'backtrack3',  'backtrack4', 
                'backtrack5', 'backtrack6',  'backtrack7', 'backtrack8',  
                'instrument', 'mark', 'placement', 'level', 'comments', 'provincials_status', 'provincials_position',
                'finals_mark', 'finals_placement', 'finals_level',
                'notes', 'internal_notes',
                ),
            'naprices' => array('fee'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.878', 'msg'=>'Registrations not found', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();


    foreach($registrations AS $rid => $registration) {
        //
        // Get the teacher details
        //
        if( isset($registration['teacher_customer_id']) && $registration['teacher_customer_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $tnid, 
                array('customer_id'=>$registration['teacher_customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $registration['teacher_details'] = $rc['details'];
        } else {
            $registration['teacher_details'] = array();
        }
        $registration['teacher_name'] = isset($registration['teacher_details'][0]['value']) ? $registration['teacher_details'][0]['value'] : '';

        //
        // Get the member festival
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) && $registration['member_id'] > 0 ) {
            $strsql = "SELECT members.name "
                . "FROM ciniki_musicfestivals_members AS members "
                . "WHERE members.id = '" . ciniki_core_dbQuote($ciniki, $registration['member_id']) . "' "
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'member');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.654', 'msg'=>'Unable to load member', 'err'=>$rc['err']));
            }
            if( isset($rc['member']) ) {
                $registration['member_details'] = array(
                    array('label'=>'Name', 'value'=>$rc['member']['name']),
                    );
            } else {
                $registration['member_details'] = array();
            }
        }
       
        //
        // Get the Accompanist details
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) 
            && isset($registration['accompanist_customer_id']) && $registration['accompanist_customer_id'] > 0 
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $tnid, 
                array('customer_id'=>$registration['accompanist_customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $registration['accompanist_details'] = $rc['details'];
        } else {
            $registration['accompanist_details'] = array();
        }
        $registration['accompanist_name'] = isset($registration['accompanist_details'][0]['value']) ? $registration['accompanist_details'][0]['value'] : '';
       
        //
        // Get the competitor details
        //
        for($i = 1; $i <= 5; $i++) {
            if( $registration['competitor' . $i . '_id'] > 0 ) {
                $strsql = "SELECT ciniki_musicfestival_competitors.id, "
                    . "ciniki_musicfestival_competitors.festival_id, "
                    . "ciniki_musicfestival_competitors.ctype, "
                    . "ciniki_musicfestival_competitors.flags, "
                    . "ciniki_musicfestival_competitors.name, "
                    . "ciniki_musicfestival_competitors.pronoun, "
                    . "ciniki_musicfestival_competitors.conductor, "
                    . "ciniki_musicfestival_competitors.num_people, "
                    . "ciniki_musicfestival_competitors.parent, "
                    . "ciniki_musicfestival_competitors.address, "
                    . "ciniki_musicfestival_competitors.city, "
                    . "ciniki_musicfestival_competitors.province, "
                    . "ciniki_musicfestival_competitors.postal, "
                    . "ciniki_musicfestival_competitors.phone_home, "
                    . "ciniki_musicfestival_competitors.phone_cell, "
                    . "ciniki_musicfestival_competitors.email, "
                    . "ciniki_musicfestival_competitors.age AS _age, "
                    . "ciniki_musicfestival_competitors.study_level, "
                    . "ciniki_musicfestival_competitors.instrument, "
                    . "ciniki_musicfestival_competitors.notes "
                    . "FROM ciniki_musicfestival_competitors "
                    . "WHERE ciniki_musicfestival_competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND ciniki_musicfestival_competitors.id = '" . ciniki_core_dbQuote($ciniki, $registration['competitor' . $i . '_id']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                    array('container'=>'competitors', 'fname'=>'id', 
                        'fields'=>array('festival_id', 'ctype', 'flags', 'name', 'pronoun', 'parent', 'conductor', 'num_people', 
                            'address', 'city', 'province', 'postal', 'phone_home', 'phone_cell', 
                            'email', '_age', 'study_level', 'instrument', 'notes'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.440', 'msg'=>"{$festival['competitor-label-singular']} not found", 'err'=>$rc['err']));
                }
                if( !isset($rc['competitors'][0]) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.452', 'msg'=>"Unable to find {$festival['competitor-label-singular']}"));
                }
                $competitor = $rc['competitors'][0];
                $competitor['age'] = $competitor['_age'];
                $details = array();
                $name = $competitor['name'];
                if( isset($festival['waiver-name-status']) && $festival['waiver-name-status'] != 'off' 
                    && ($competitor['flags']&0x04) == 0
                    ) {
                    $name .= "\n***** NAME WITHHELD *****";
                }
                if( isset($festival['waiver-photo-status']) && $festival['waiver-photo-status'] != 'off' 
                    && ($competitor['flags']&0x02) == 0
                    ) {
                    $name .= "\n*****     NO PHOTOS     *****";
                }
                $details[] = array('label'=>'Name', 'value'=>$name);
                if( $competitor['ctype'] == 50 ) { 
                    if( $competitor['conductor'] != '' ) {
                        $details[] = array('label'=>'Conductor', 'value'=>$competitor['conductor']);
                    }
                    if( $competitor['num_people'] != '' ) {
                        $details[] = array('label'=>'# People', 'value'=>$competitor['num_people']);
                    }
                } else {
                    $details[] = array('label'=>'Age', 'value'=>$competitor['age']);
                }
                if( $competitor['ctype'] == 10 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                    $details[] = array('label'=>'Pronoun', 'value'=>$competitor['pronoun']);
                }
                if( $competitor['ctype'] == 10 && $competitor['parent'] != '' ) { 
                    $details[] = array('label'=>'Parent', 'value'=>$competitor['parent']); 
                }
                if( $competitor['ctype'] == 50 && $competitor['parent'] != '' ) { 
                    $details[] = array('label'=>'Contact', 'value'=>$competitor['parent']); 
                }
                $address = '';
                if( $competitor['address'] != '' ) { $address .= $competitor['address']; }
                $city = $competitor['city'];
                if( $competitor['province'] != '' ) { $city .= ($city != '' ? ", " : '') . $competitor['province']; }
                if( $competitor['postal'] != '' ) { $city .= ($city != '' ? "  " : '') . $competitor['postal']; }
                if( $city != '' ) { $address .= ($address != '' ? "\n" : '' ) . $city; }
                if( $address != '' ) {
                    $details[] = array('label'=>'Address', 'value'=>$address);
                }
                if( $competitor['phone_home'] != '' ) { $details[] = array('label'=>'Home', 'value'=>$competitor['phone_home']); }
                if( $competitor['phone_cell'] != '' ) { $details[] = array('label'=>'Cell', 'value'=>$competitor['phone_cell']); }
                if( $competitor['email'] != '' ) { $details[] = array('label'=>'Email', 'value'=>$competitor['email']); }
//                if( $competitor['age'] != '' ) { $details[] = array('label'=>'Age', 'value'=>$competitor['_age']); }
//                if( $competitor['study_level'] != '' ) { $details[] = array('label'=>'Study/Level', 'value'=>$competitor['study_level']); }
                if( $competitor['instrument'] != '' ) { $details[] = array('label'=>'Instrument', 'value'=>$competitor['instrument']); }
//                if( ($competitor['flags']&0x01) == 0x01 ) { $details[] = array('label'=>'Waiver', 'value'=>'Signed'); }
//                if( $competitor['notes'] != '' ) {
//                    $details[] = array('label'=>'Notes', 'value'=>$competitor['notes']);
//                }
                $registration['competitor' . $i . '_details'] = $details;
                $registration["competitor{$i}"] = $competitor;
            }
        }
        
        $registrations[$rid] = $registration;
    }

    //
    // Load the festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $left_margin = 18;
        public $right_margin = 18;
        public $top_margin = 15;
        public $header_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_msg = '';
        public $tenant_details = array();
        public $fill = 0;

        public function Header() {
            //
            // Check if there is an image to be output in the header.   The image
            // will be displayed in a narrow box if the contact information is to
            // be displayed as well.  Otherwise, image is scaled to be 100% page width
            // but only to a maximum height of the header_height (set far below).
            //
            $img_width = 0;
            if( $this->header_image != null ) {
                $height = $this->header_image->getImageHeight();
                $width = $this->header_image->getImageWidth();
                $image_ratio = $width/$height;
                $img_width = 60;
                $available_ratio = $img_width/$this->header_height;
                // Check if the ratio of the image will make it too large for the height,
                // and scaled based on either height or width.
                if( $available_ratio < $image_ratio ) {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, 'JPEG', '', 'L', 2, '150');
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, 0, $this->header_height-13, 'JPEG', '', 'L', 2, '150');
                }
            }

            $this->Ln(8);
            $this->SetFont('helvetica', 'B', 20);
            if( $img_width > 0 ) {
                $this->Cell($img_width, 10, '', 0);
            }
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 12, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(7);

            $this->SetFont('helvetica', 'B', 14);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);

            $this->SetFont('helvetica', 'B', 12);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(90, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->SetFont('helvetica', '', 10);
            $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        } 
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_title = $festival['name'];
    $pdf->header_sub_title = 'Registration';
//    $pdf->header_msg = $festival['document_header_msg'];
    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    $pdf->footer_msg = 'Printed: ' . $dt->format('M j, Y - H:i a');

    //
    // Set the minimum header height
    //
    if( $pdf->header_height < 30 ) {
        $pdf->header_height = 30;
    }

    //
    // Load the header image
    //
    if( isset($festival['document_logo_id']) && $festival['document_logo_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
        $rc = ciniki_images_loadImage($ciniki, $tnid, $festival['document_logo_id'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    }

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Registrations');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetCellPadding(1.5);

    // add a page
    $pdf->SetFillColor(239);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetDrawColor(200);
    $pdf->SetLineWidth(0.15);

    $filename = 'registrations';

    foreach($registrations AS $reg) {

        
//        $pdf->header_sub_title = $registration['display_name'];
        $pdf->AddPage(); 

        $pdf->SetFont('', '', 12);
        if( ($festival['flags']&0x0100) == 0x0100 ) {
            $pdf->MultiCell(180, 0, "{$reg['class_code']} - {$reg['section_name']} - {$reg['category_name']} - {$reg['class_name']}", 0, 'L', 0, 1);
        } else {
            $pdf->MultiCell(180, 0, "{$reg['class_code']} - {$reg['class_name']}", 0, 'L', 0, 1);
        }
        $pdf->Ln(1);
        $pdf->SetFont('', '', 12);

        $w = [60, 60, 60];
        $lh = 0;
        $competitor_name = $reg['display_name']; 
        if( $reg['instrument'] != '' ) {
            $competitor_name .= ' [' . $reg['instrument'] . ']';
        }
        $lh = $pdf->getStringHeight($w[0], $competitor_name);
        $name_width = $pdf->getStringWidth($competitor_name);
        if( $reg['teacher_name'] != '' ) {
            $width = $pdf->getStringWidth($reg['teacher_name']);
            if( $name_width > 60 && $width < 56 ) {
                $w[1] = $width + 4;
            }
            if( $pdf->getStringHeight($w[1], $reg['teacher_name']) > $lh ) {
                $lh = $pdf->getStringHeight($w[1], $reg['teacher_name']);
            }
        } else {
            $w[1] = 35;
        }
        if( $reg['accompanist_name'] != '' ) {
            $width = $pdf->getStringWidth($reg['accompanist_name']);
            if( $name_width > 60 && $width < 56 ) {
                $w[2] = $width + 4;
            }
            if( $pdf->getStringHeight($w[2], $reg['accompanist_name']) > $lh ) {
                $lh = $pdf->getStringHeight($w[2], $reg['accompanist_name']);
            }
        } else {
            $w[2] = 35;
        }
        /* Redo line height after seeing if we can shrink teacher/accompanist columns */
        $w[0] = 180 - $w[1] - $w[2];
        $lh = $pdf->getStringHeight($w[0], $competitor_name);
        if( $pdf->getStringHeight($w[1], $reg['teacher_name']) > $lh ) {
            $lh = $pdf->getStringHeight($w[1], $reg['teacher_name']);
        }
        if( $pdf->getStringHeight($w[2], $reg['accompanist_name']) > $lh ) {
            $lh = $pdf->getStringHeight($w[2], $reg['accompanist_name']);
        }
        $pdf->MultiCell($w[0], 8, $festival['competitor-label-singular'], 1, 'L', 1, 0);
        $pdf->MultiCell($w[1], 8, "Teacher", 1, 'L', 1, 0);
        $pdf->MultiCell($w[2], 8, "Accompanist", 1, 'L', 1, 1);
        $pdf->MultiCell($w[0], $lh, $competitor_name, 1, 'L', 0, 0);
        $pdf->MultiCell($w[1], $lh, $reg['teacher_name'], 1, 'L', 0, 0);
        $pdf->MultiCell($w[2], $lh, $reg['accompanist_name'], 1, 'L', 0, 1);
        //
        // Check if notes
        //
        if( $reg['notes'] != '' ) {
            $w = [20, 160];
            $lh = $pdf->getStringHeight($w[1], $reg['notes']);
            $pdf->MultiCell($w[0], $lh, 'Notes', 1, 'L', 1, 0);
            $pdf->MultiCell($w[1], $lh, $reg['notes'], 1, 'L', 0, 1);
        }
        $pdf->Ln(5);

        $w = [6, 160, 14];
        $pdf->MultiCell($w[0], 8, "#", 1, 'C', 1, 0);
        $pdf->MultiCell($w[1], 8, "Title", 1, 'L', 1, 0);
//        $pdf->MultiCell($w[2], 8, "Movements/Musical", 1, 'L', 1, 0);
//        $pdf->MultiCell($w[3], 8, "Composer", 1, 'L', 1, 0);
        $pdf->MultiCell($w[2], 8, "Time", 1, 'R', 1, 1);

        for($i = 1; $i <= 8; $i++) {
            if( isset($reg["title{$i}"]) && $reg["title{$i}"] != '' ) {
                $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $reg, $i);
                $reg["title{$i}"] = $rc['title'];
                $reg["time{$i}"] = intval($reg["perf_time{$i}"]/60) . ':' . str_pad(($reg["perf_time{$i}"]%60), 2, '0', STR_PAD_LEFT);
                $lh = $pdf->getStringHeight($w[1], $reg["title{$i}"]);
                $pdf->MultiCell($w[0], $lh, $i, 1, 'R', 0, 0);
                $pdf->MultiCell($w[1], $lh, $reg["title{$i}"], 1, 'L', 0, 0);
                $pdf->MultiCell($w[2], $lh, $reg["time{$i}"], 1, 'R', 0, 1);
            }
        }
        $pdf->Ln(5);

        //
        // Add the competitor details
        //
        for($i = 1; $i <= 5; $i++) {
            if( isset($reg["competitor{$i}_details"]) ) {
                $w = [23, 67];
                $lh = null;
                foreach($reg["competitor{$i}_details"] as $did => $detail) {
                    //
                    // On left side, check right side height
                    //
                    if( $lh == null ) {
                        $lh = $pdf->getStringHeight($w[1], $detail['value']);
                        if( isset($reg["competitor{$i}_details"][($did+1)]['value']) ) {
                            $lh2 = $pdf->getStringHeight($w[1], $reg["competitor{$i}_details"][($did+1)]['value']);
                            if( $lh2 > $lh ) {
                                $lh = $lh2;
                            }
                            $pdf->MultiCell($w[0], $lh, $detail['label'], 1, 'R', 1, 0);
                            $pdf->MultiCell($w[1], $lh, $detail['value'], 1, 'L', 0, 0);
                        } else {
                            $pdf->MultiCell($w[0], $lh, $detail['label'], 1, 'R', 1, 0);
                            $pdf->MultiCell($w[1]+$w[0]+$w[1], $lh, $detail['value'], 1, 'L', 0, 1);
                        }
                    } else {
                        $pdf->MultiCell($w[0], $lh, $detail['label'], 1, 'R', 1, 0);
                        $pdf->MultiCell($w[1], $lh, $detail['value'], 1, 'L', 0, 1);
                        $lh = null;
                    }
                }
                if( isset($reg["competitor{$i}"]['notes']) && $reg["competitor{$i}"]['notes'] != '' ) {
                    $lh = $pdf->getStringHeight($w[1]+$w[0]+$w[1], $reg["competitor{$i}"]['notes']);
                    $pdf->MultiCell($w[0], $lh, 'Notes', 1, 'R', 1, 0);
                    $pdf->MultiCell($w[1]+$w[0]+$w[1], $lh, $reg["competitor{$i}"]['notes'], 1, 'L', 0, 1);
                }
                $pdf->Ln(5);
            }
        }
//        $pdf->MultiCell(180, 0, print_r($reg, true), 1, 'L', 0, 1);
//        $pdf->MultiCell(180, 0, print_r($festival, true), 1, 'L', 0, 1);
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
