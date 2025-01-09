<?php
//
// Description
// -----------
// This function will generate the list(s) of registrations
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_registrationsListGenerate(&$ciniki, $tnid, &$request, $args) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.453', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a festival was specified
    //
    if( !isset($args['festival']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.521', 'msg'=>"No festival specified"));
    }
    $festival = $args['festival'];

    //
    // Make sure customer type is passed
    //
    if( !isset($args['customer_type']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.522', 'msg'=>"No customer type specified"));
    }
    $customer_type = $args['customer_type'];

    //
    // Make sure base_url is passed
    //
    if( !isset($args['base_url']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.723', 'msg'=>"No location specified"));
    }
    $base_url = $args['base_url'];


    $blocks = array();

    //
    // Get the list of registrations
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.status, "
        . "registrations.invoice_id, "
        . "registrations.billing_customer_id, "
        . "registrations.teacher_customer_id, "
        . "registrations.accompanist_customer_id, "
        . "registrations.member_id, "
        . "registrations.participation, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
        $strsql .= "registrations.pn_display_name AS display_name, ";
    } else {
        $strsql .= "registrations.display_name, ";
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
        . "registrations.fee, "
        . "registrations.participation, "
        . "registrations.comments, "
        . "classes.code AS class_code, "
        . "sections.name AS section_name, "
        . "categories.name AS category_name, "
        . "classes.name AS class_name, "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS codename, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        $strsql .= "IFNULL(TIME_FORMAT(registrations.timeslot_time, '%l:%i %p'), '') AS timeslot_time, ";
    } else {
        $strsql .= "IFNULL(TIME_FORMAT(timeslots.slot_time, '%l:%i %p'), '') AS timeslot_time, ";
    }
    $strsql .= "IFNULL(DATE_FORMAT(divisions.division_date, '%b %D, %Y'), '') AS timeslot_date, "
        . "IFNULL(locations.name, '') AS location_name, "
        . "IFNULL(locations.address1, '') AS location_address, "
        . "IFNULL(locations.city, '') AS location_city, "
        . "IFNULL(ssections.flags, 0) AS timeslot_flags, "
        . "IFNULL(divisions.flags, 0) AS division_flags, "
        . "IFNULL(invoices.status, 0) AS invoice_status, "
        . "IFNULL(invoices.payment_status, 0) AS payment_status "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON (" 
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
        . "LEFT JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
            . "divisions.ssection_id = ssections.id "
            . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
            . "registrations.invoice_id = invoices.id "
            . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    if( $customer_type == 10 ) {
        $strsql .= "WHERE ("
            . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.parent_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . ") ";
    } elseif( $customer_type == 20 ) {
        $strsql .= "WHERE ("
            . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . ") ";
    } else {
        $strsql .= "WHERE ("
            . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . ") ";
    }
    $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "ORDER BY registrations.display_name, registrations.date_added "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'status', 'payment_status', 'invoice_status', 'invoice_id', 
                'billing_customer_id', 'teacher_customer_id', 'accompanist_customer_id', 'member_id', 'display_name', 
                'class_code', 'class_name', 'section_name', 'category_name', 'codename', 
                'fee', 'participation', 
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8', 
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8', 
                'timeslot_time', 'timeslot_date', 'location_name', 'location_address', 'location_city', 
                'timeslot_flags', 'division_flags', 'comments',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.300', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
    $cart_registrations = array();
    $etransfer_registrations = array();
    $paymentrequired_registrations = array();
    $paid_registrations = array();
    $cancelled_registrations = array();
    $parent_registrations = array();
    $accompanist_registrations = array();
    $schedule_pdf = 'no';
    foreach($registrations as $rid => $reg) {
        // FIXME: Add check to see if schedule should be available
//            $schedule_pdf = 'yes';
        if( ($festival['flags']&0x0100) == 0x0100 ) {
            $reg['codename'] = $reg['class_code'] . ' - ' . $reg['section_name'] . ' - ' . $reg['category_name'] . ' - ' . $reg['class_name'];
        }
        $reg['titles'] = '';
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');
        $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $reg, ['basicnumbers'=>'yes', 'newline'=>'<br/>']);
        if( isset($rc['titles']) ) {
            $reg['titles'] = $rc['titles'];
        }
        if( $reg['participation'] == 1 ) {
            $reg['codename'] .= ' (Virtual)';
        } elseif( $reg['participation'] == 0 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) ) {
            $reg['codename'] .= ' (Live)';
        } elseif( $reg['participation'] == 2 ) {
            $reg['codename'] .= ' (Adjudication Plus)';
        }
        if( $reg['teacher_customer_id'] == $request['session']['customer']['id']
            && $reg['billing_customer_id'] != $request['session']['customer']['id']
            ) {
            // Registration was created by parent/student and shared with teacher
            $parent_registrations[] = $reg;
        } elseif( $reg['accompanist_customer_id'] == $request['session']['customer']['id'] 
            && $reg['billing_customer_id'] != $request['session']['customer']['id']
            ) {
            $accompanist_registrations[] = $reg;
        } elseif( $reg['invoice_status'] == 10 ) {
            $cart_registrations[] = $reg;
        } elseif( $reg['invoice_status'] == 50 ) {  
            // Based on status from registration NOT invoice status
            $paid_registrations[] = $reg;
        } elseif( $reg['invoice_status'] == 42 ) {
            $etransfer_registrations[] = $reg;
        } elseif( $reg['invoice_status'] == 40 ) {
            $paymentrequired_registrations[] = $reg;
        } elseif( $reg['status'] == 80 ) {
            $cancelled_registrations[] = $reg;
        } elseif( $reg['accompanist_customer_id'] == $request['session']['customer']['id'] ) {
            $accompanist_registrations[] = $reg;
        }
        /*
        if( $reg['status'] == 6 ) {
            $cart_registrations[] = $reg;
        } elseif( $reg['status'] == 7 ) {
            $etransfer_registrations[] = $reg;
        } elseif( $reg['status'] == 50 ) {
            $paid_registrations[] = $reg;
        } elseif( $reg['status'] == 60 ) {
            $cancelled_registrations[] = $reg;
        }
        */
    }

    if( isset($args['form_errors']) && $args['form_errors'] != '' ) { 
        $blocks[] = array(
            'type' => 'msg',
            'level' => 'error',
            'content' => $args['form_errors'],
            );
    }
    if( ($festival['flags']&0x01) == 0x01 && ($festival['live'] != 'no' || $festival['virtual'] != 'no') ) {
        if( count($cart_registrations) > 0 ) {
            $add_button = "<a class='button' href='{$base_url}?add=yes'>Add</a>";
            $total = 0;
            foreach($cart_registrations as $rid => $registration) {
                $cart_registrations[$rid]['editbutton'] = "<form action='{$base_url}' method='POST'>"
                    . "<input type='hidden' name='f-registration_id' value='{$registration['id']}' />"
                    . "<input type='hidden' name='action' value='edit' />"
                    . "<input class='button' type='submit' name='submit' value='Edit'>"
                    . "<input class='button' type='submit' name='f-delete' value='Remove'>"
                    . "</form>";
                $cart_registrations[$rid]['fee'] = '$' . number_format($registration['fee'], 2);
                $total += $registration['fee'];
            }
            $blocks[] = array(
                'type' => 'table',
                'title' => $festival['name'] . ' Cart',
                'class' => 'musicfestival-registrations limit-width limit-width-90 fold-at-50',
                'headers' => 'yes',
                'columns' => array(
                    array('label' => $festival['competitor-label-singular'], 'field' => 'display_name', 'class' => 'alignleft'),
                    array('label' => 'Class', 'fold-label'=>'Class:', 'field' => 'codename', 'class' => 'alignleft'),
                    array('label' => 'Title(s)', 'fold-label'=>'Title:', 'field' => 'titles', 'class' => 'alignleft'),
                    array('label' => 'Fee', 'fold-label'=>'Fee:', 'field' => 'fee', 'class' => 'alignright fold-alignleft'),
                    array('label' => $add_button, 'field' => 'editbutton', 'class' => 'buttons alignright'),
                    ),
                'footer' => array(
                    array('value' => '<b>Total</b>', 'colspan' => 3, 'class' => 'alignright'),
                    array('value' => '$' . number_format($total, 2), 'class' => 'alignright'),
                    array('value' => '', 'class' => 'alignright fold-hidden'),
                    ),
                'rows' => $cart_registrations,
                );
        } else {
            $blocks[] = array(
                'type' => 'text',
                'class' => 'limit-width limit-width-90', 'title' => $festival['name'] . ' Registrations',
                'content' => 'No pending registrations',
                );
        }
        $buttons = array(
            'type' => 'buttons',
            'class' => 'limit-width limit-width-90 aligncenter',
            'list' => array(array(
                'text' => 'Add Registration',
                'url' => "/account/musicfestivalregistrations?add=yes",
                )),
            );
        if( count($cart_registrations) > 0 ) {
            $buttons['list'][] = array(
                'text' => 'Checkout',
                'url' => "/cart",
                );
        }
        $blocks[] = $buttons;
    } else {
        $blocks[] = array(
            'type' => 'text',
            'class' => 'limit-width limit-width-90',
            'title' => $festival['name'] . ' Registrations',
            'content' => 'Registrations closed',
            );
    } 
    if( count($etransfer_registrations) > 0 ) {
        //
        // Format fee
        //
        $total = 0;
        foreach($etransfer_registrations as $rid => $registration) {
            $etransfer_registrations[$rid]['viewbutton'] = "<form action='{$base_url}' method='POST'>"
                . "<input type='hidden' name='f-registration_id' value='{$registration['id']}' />"
                . "<input type='hidden' name='action' value='view' />"
                . "<input class='button' type='submit' name='submit' value='View'>"
                . "</form>";
            $etransfer_registrations[$rid]['fee'] = '$' . number_format($registration['fee'], 2);
            $total += $registration['fee'];
        }
        $blocks[] = array(
            'type' => 'table',
            'title' => $festival['name'] . ' E-transfers Required',
            'class' => 'musicfestival-registrations limit-width limit-width-90 fold-at-50',
            'headers' => 'yes',
            'columns' => array(
                array('label' => $festival['competitor-label-singular'], 'field' => 'display_name', 'class' => 'alignleft'),
                array('label' => 'Class', 'fold-label'=>'Class:', 'field' => 'codename', 'class' => 'alignleft'),
                array('label' => 'Title(s)', 'fold-label'=>'Title:', 'field' => 'titles', 'class' => 'alignleft'),
                array('label' => 'Fee', 'fold-label'=>'Fee:', 'field' => 'fee', 'class' => 'alignright fold-alignleft'),
                array('label' => '', 'field' => 'viewbutton', 'class' => 'buttons alignright'),
                ),
            'rows' => $etransfer_registrations,
            'footer' => array(
                array('value' => '<b>Total</b>', 'colspan' => 3, 'class' => 'alignright'),
                array('value' => '$' . number_format($total, 2), 'colspan'=>2, 'class' => 'alignright'),
                ),
            );
        //
        // FIXME: add check to see if timeslot assigned and show
        //

        //
        // Add button to download PDF list of registrations
        //
        if( $customer_type == 20 ) {
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'limit-width limit-width-90 aligncenter',
                'list' => array(array(
                    'text' => 'Download Registrations PDF',
                    'target' => '_blank',
                    'url' => "/account/musicfestivalregistrations?pdf=yes",
                    )),
                );
        }
    }
    if( count($paymentrequired_registrations) > 0 ) {
        //
        // Format fee
        //
        $total = 0;
        foreach($paymentrequired_registrations as $rid => $registration) {
            $etransfer_registrations[$rid]['viewbutton'] = "<form action='{$base_url}' method='POST'>"
                . "<input type='hidden' name='f-registration_id' value='{$registration['id']}' />"
                . "<input type='hidden' name='action' value='view' />"
                . "<input class='button' type='submit' name='submit' value='View'>"
                . "</form>";
            $paymentrequired_registrations[$rid]['fee'] = '$' . number_format($registration['fee'], 2);
            $total += $registration['fee'];
        }
        $blocks[] = array(
            'type' => 'table',
            'title' => $festival['name'] . ' Payments Required',
            'class' => 'musicfestival-registrations limit-width limit-width-90 fold-at-50',
            'headers' => 'yes',
            'columns' => array(
                array('label' => $festival['competitor-label-singular'], 'field' => 'display_name', 'class' => 'alignleft'),
                array('label' => 'Class', 'fold-label'=>'Class:', 'field' => 'codename', 'class' => 'alignleft'),
                array('label' => 'Title(s)', 'fold-label'=>'Title:', 'field' => 'titles', 'class' => 'alignleft'),
                array('label' => 'Fee', 'fold-label'=>'Fee:', 'field' => 'fee', 'class' => 'alignright fold-alignleft'),
                array('label' => '', 'field' => 'viewbutton', 'class' => 'buttons alignright'),
                ),
            'rows' => $paymentrequired_registrations,
            'footer' => array(
                array('value' => '<b>Total</b>', 'colspan' => 3, 'class' => 'alignright'),
                array('value' => '$' . number_format($total, 2), 'colspan'=>2, 'class' => 'alignright'),
                ),
            );
    }
    if( count($paid_registrations) > 0 ) {
        foreach($paid_registrations as $rid => $registration) {
            $paid_registrations[$rid]['viewbutton'] = "<form action='{$base_url}' method='POST'>"
                . "<input type='hidden' name='f-registration_id' value='{$registration['id']}' />"
                . "<input type='hidden' name='action' value='view' />"
                . "<input class='button' type='submit' name='submit' value='View'>"
                . "</form>";
            $paid_registrations[$rid]['scheduled'] = '';
            //
            // If the registration has been schedule and schedule released
            //
            if( $registration['participation'] == 1 ) {
                $paid_registrations[$rid]['scheduled'] = 'Virtual';
            } elseif( ($registration['timeslot_flags']&0x01) == 0x01 
                && $registration['timeslot_time'] != ''
                && $registration['timeslot_date'] != ''
                ) {
                $paid_registrations[$rid]['scheduled'] = $registration['timeslot_date'] . ' - ' . $registration['timeslot_time'] . '<br/>' . $registration['location_name'] . '<br/>' . $registration['location_address'] . ', ' . $registration['location_city'];
            }
        }
        $blocks[] = array(
            'type' => 'table',
            'title' => $festival['name'] . ' Paid Registrations',
            'class' => 'musicfestival-registrations limit-width limit-width-90 fold-at-50',
            'headers' => 'yes',
            'columns' => array(
                array('label' => $festival['competitor-label-singular'], 'field' => 'display_name', 'class' => 'alignleft'),
                array('label' => 'Class', 'fold-label'=>'Class:', 'field' => 'codename', 'class' => 'alignleft'),
                array('label' => 'Title(s)', 'fold-label'=>'Title:', 'field' => 'titles', 'class' => 'alignleft'),
                array('label' => 'Scheduled', 'fold-label'=>'Scheduled:', 'field' => 'scheduled', 'class' => 'alignleft'),
                array('label' => '', 'field' => 'viewbutton', 'class' => 'buttons alignright'),
                ),
            'rows' => $paid_registrations,
            );
        //
        // FIXME: add check to see if timeslot assigned and show
        //
       
        //
        // Add button to download PDF list of registrations
        //
        if( count($parent_registrations) == 0 ) {
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'limit-width limit-width-90 aligncenter',
                'list' => array(array(
                    'text' => 'Download Registrations PDF',
                    'target' => '_blank',
                    'url' => "/account/musicfestivalregistrations?pdf=yes",
                    )),
                );
        }
    }
    if( count($parent_registrations) > 0 ) {
        foreach($parent_registrations as $rid => $registration) {
            $parent_registrations[$rid]['viewbutton'] = "<form action='{$base_url}' method='POST'>"
                . "<input type='hidden' name='f-registration_id' value='{$registration['id']}' />"
                . "<input type='hidden' name='action' value='view' />"
                . "<input class='button' type='submit' name='submit' value='View'>"
                . "</form>";
            $parent_registrations[$rid]['scheduled'] = '';
            //
            // If the registration has been schedule and schedule released
            //
            if( $registration['participation'] == 1 ) {
                $parent_registrations[$rid]['scheduled'] = 'Virtual';
            } elseif( ($registration['timeslot_flags']&0x01) == 0x01 
                && $registration['timeslot_time'] != ''
                && $registration['timeslot_date'] != ''
                ) {
                $parent_registrations[$rid]['scheduled'] = $registration['timeslot_date'] . ' - ' . $registration['timeslot_time'] . '<br/>' . $registration['location_name'] . '<br/>' . $registration['location_address'] . ', ' . $registration['location_city'];
            }
        }
        $blocks[] = array(
            'type' => 'table',
            'title' => $festival['name'] . ' Student Registrations',
            'class' => 'musicfestival-registrations limit-width limit-width-90 fold-at-50',
            'headers' => 'yes',
            'columns' => array(
                array('label' => $festival['competitor-label-singular'], 'field' => 'display_name', 'class' => 'alignleft'),
                array('label' => 'Class', 'fold-label'=>'Class:', 'field' => 'codename', 'class' => 'alignleft'),
                array('label' => 'Title(s)', 'fold-label'=>'Title:', 'field' => 'titles', 'class' => 'alignleft'),
                array('label' => 'Scheduled', 'fold-label'=>'Scheduled:', 'field' => 'scheduled', 'class' => 'alignleft'),
//                    array('label' => 'Fee', 'fold-label'=>'Fee:', 'field' => 'fee', 'class' => 'alignright fold-alignleft'),
                array('label' => '', 'field' => 'viewbutton', 'class' => 'buttons alignright'),
                ),
            'rows' => $parent_registrations,
//                'footer' => array(
//                    array('value' => '<b>Total</b>', 'colspan' => 3, 'class' => 'alignright'),
//                    array('value' => '$' . number_format($total, 2), 'colspan'=>2, 'class' => 'alignright'),
//                    ),
            );
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'limit-width limit-width-90 aligncenter',
                'list' => array(array(
                    'text' => 'Download Registrations PDF',
                    'target' => '_blank',
                    'url' => "/account/musicfestivalregistrations?pdf=yes",
                    )),
                );
    }
    if( count($accompanist_registrations) > 0 ) {
        foreach($accompanist_registrations as $rid => $registration) {
            $accompanist_registrations[$rid]['viewbutton'] = "<form action='{$base_url}' method='POST'>"
                . "<input type='hidden' name='f-registration_id' value='{$registration['id']}' />"
                . "<input type='hidden' name='action' value='view' />"
                . "<input class='button' type='submit' name='submit' value='View'>"
                . "</form>";
            $accompanist_registrations[$rid]['scheduled'] = '';
            //
            // If the registration has been schedule and schedule released
            //
            if( $registration['participation'] == 1 ) {
                $accompanist_registrations[$rid]['scheduled'] = 'Virtual';
            } elseif( ($registration['timeslot_flags']&0x01) == 0x01 
                && $registration['timeslot_time'] != ''
                && $registration['timeslot_date'] != ''
                ) {
                $accompanist_registrations[$rid]['scheduled'] = $registration['timeslot_date'] . ' - ' . $registration['timeslot_time'] . '<br/>' . $registration['location_name'] . '<br/>' . $registration['location_address'] . ', ' . $registration['location_city'];
            }
        }
        $blocks[] = array(
            'type' => 'table',
            'title' => $festival['name'] . ' Accompanist Registrations',
            'class' => 'musicfestival-registrations limit-width limit-width-90',
            'headers' => 'yes',
            'columns' => array(
                array('label' => $festival['competitor-label-singular'], 'field' => 'display_name', 'class' => 'alignleft'),
                array('label' => 'Class', 'fold-label'=>'Class:', 'field' => 'codename', 'class' => 'alignleft'),
                array('label' => 'Title(s)', 'fold-label'=>'Title:', 'field' => 'titles', 'class' => 'alignleft'),
                array('label' => 'Scheduled', 'fold-label'=>'Scheduled:', 'field' => 'scheduled', 'class' => 'alignleft'),
                array('label' => '', 'field' => 'viewbutton', 'class' => 'buttons alignright'),
                ),
            'rows' => $accompanist_registrations,
            );
    }
    if( count($cancelled_registrations) > 0 ) {
        $blocks[] = array(
            'type' => 'table',
            'title' => $festival['name'] . ' Cancelled Registrations',
            'class' => 'musicfestival-registrations limit-width limit-width-90',
            'headers' => 'yes',
            'columns' => array(
                array('label' => $festival['competitor-label-singular'], 'field' => 'display_name', 'class' => 'alignleft'),
                array('label' => 'Class', 'fold-label'=>'Class:', 'field' => 'codename', 'class' => 'alignleft'),
                array('label' => 'Title(s)', 'fold-label'=>'Title:', 'field' => 'titles', 'class' => 'alignleft'),
                ),
            'rows' => $cancelled_registrations,
            );
    }

    if( $schedule_pdf == 'yes' ) {
        $blocks[] = array(
            'type' => 'buttons',
            'class' => 'limit-width limit-width-90 aligncenter',
            'list' => array(array(
                'text' => 'Download Schedule PDF',
                'target' => '_blank',
                'url' => "/account/musicfestivalregistrations?schedulepdf=yes",
                )),
            );
    }


    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
