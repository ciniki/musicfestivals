<?php
//
// Description
// -----------
// This function will check for registrations in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountRegistrationsProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestival/registrations';
    $display = 'list';
    $form_errors = '';
    $errors = array();

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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Check for a cancel
    //
    if( (isset($_POST['cancel']) && $_POST['cancel'] == 'Cancel')
        || (isset($_POST['f-action']) && $_POST['f-action'] == 'cancel')
        ) {
        if( isset($request['session']['account-musicfestivals-registration-return-url']) ) {
            header("Location: {$request['session']['account-musicfestivals-registration-return-url']}");
            unset($request['session']['account-musicfestivals-registration-return-url']);
            return array('stat'=>'exit');
        }
        header("Location: {$base_url}");
        return array('stat'=>'exit');
    }

    if( isset($_POST['f-action']) && $_POST['f-action'] == 'view' ) {
        header("Location: {$base_url}");
        return array('stat'=>'exit');
    }

    if( isset($request['session']['account-musicfestivals-registration-saved']) ) {
        $_POST = $request['session']['account-musicfestivals-registration-saved'];
        if( isset($request['session']['account-musicfestivals-registration-saved']['new-id']) ) {
            for($i=1;$i<5;$i++) {
                if( isset($_POST["f-competitor{$i}_id"]) 
                    && ($_POST["f-competitor{$i}_id"] == -1 || $_POST["f-competitor{$i}_id"] == -2) 
                    ) {
                    $_POST["f-competitor{$i}_id"] = $request['session']['account-musicfestivals-registration-saved']['new-id'];
                }
            }
        }
        unset($request['session']['account-musicfestivals-registration-saved']);
    }
    if( isset($request['session']['account-musicfestivals-competitor-form-return']) ) {
        unset($request['session']['account-musicfestivals-competitor-form-return']);
    }

    if( isset($request['session']['redirect-message']) 
        && $request['session']['redirect-message'] != '' 
        ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'success',
            'content' => $request['session']['redirect-message'],
            ];
        unset($request['session']['redirect-message']);
    }

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.419', 'msg'=>'', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Load the customer type, or ask for customer type
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountCustomerTypeProcess');
    $rc = ciniki_musicfestivals_wng_accountCustomerTypeProcess($ciniki, $tnid, $request, array(
        'festival' => $festival,
        'base_url' => $base_url,
        ));
    if( $rc['stat'] == 'exit' || (isset($rc['stop']) && $rc['stop'] == 'yes') ) {
        return $rc;
    }
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.362', 'msg'=>'Unable to ', 'err'=>$rc['err']));
    }
    $customer_type = $rc['customer_type'];
    if( isset($rc['switch_block']) ) {
        $customer_switch_type_block = $rc['switch_block'];
    }

    //
    // Check if request to download PDF
    //
    if( isset($_GET['pdf']) && $_GET['pdf'] == 'yes' ) {    
        if( $customer_type == 20 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'teacherRegistrationsPDF');
            $rc = ciniki_musicfestivals_templates_teacherRegistrationsPDF($ciniki, $tnid, array(
                'festival_id' => $festival['id'],
                'teacher_customer_id' => $request['session']['customer']['id'],
//                'shared' => 'yes',
                ));
        } else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'parentRegistrationsPDF');
            $rc = ciniki_musicfestivals_templates_parentRegistrationsPDF($ciniki, $tnid, array(
                'festival_id' => $festival['id'],
                'billing_customer_id' => $request['session']['customer']['id'],
                ));
        }
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.444', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        if( isset($rc['pdf']) ) {
            $rc['pdf']->Output($rc['filename'], 'I');
            return array('stat'=>'exit');
        }
    }
    if( isset($_GET['schedulepdf']) && $_GET['schedulepdf'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'registrationsSchedulePDF');
        $rc = ciniki_musicfestivals_templates_registrationsSchedulePDF($ciniki, $tnid, array(
            'festival_id' => $festival['id'],
            'customer_id' => $request['session']['customer']['id'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.700', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        if( isset($rc['pdf']) ) {
            $rc['pdf']->Output($rc['filename'], 'I');
            return array('stat'=>'exit');
        }
    }

    //
    // Get the list of competitors
    // Search only current festival, as ages will have changed from previous festivals
    //
    if( isset($_POST['action']) && $_POST['action'] == 'view' ) {
        $strsql = "SELECT DISTINCT competitors.id, "
            . "competitors.name, "
            . "competitors.pronoun "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "("
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                    . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ("
                . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.teacher2_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.parent_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "ORDER BY competitors.name "
            . "";
    } else {
        $strsql = "SELECT competitors.id, "
            . "competitors.name, "
            . "competitors.pronoun "
            . "FROM ciniki_musicfestival_competitors AS competitors "
            . "WHERE billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "ORDER BY competitors.name "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 'fields'=>array('id', 'name', 'pronoun')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.325', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
        foreach($competitors as $cid => $competitor) {
            if( $competitor['pronoun'] != '' ) {
                $competitors[$cid]['name'] .= ' (' . $competitor['pronoun'] . ')';
            }
        }
    }

    //
    // Load the class list
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.flags AS section_flags, "
        . "sections.name AS section_name, "
        . "sections.live_end_dt, "
        . "sections.virtual_end_dt, "
        . "sections.latefees_start_amount, "
        . "sections.latefees_daily_increase, "
        . "sections.latefees_days "
//        . "categories.name AS category_name, "
/*        . "classes.id AS class_id, "
        . "classes.uuid AS class_uuid, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "classes.flags AS class_flags, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee, "
        . "classes.options " */
        . "FROM ciniki_musicfestival_sections AS sections "
/*        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND (classes.flags&0x01) = 0x01 "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") " */
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (sections.flags&0x01) = 0 "
        . "ORDER BY sections.sequence, sections.name " //, categories.sequence, categories.name, classes.sequence, classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'flags'=>'section_flags', 
                'live_end_dt', 'virtual_end_dt',
                'latefees_start_amount', 'latefees_daily_increase', 'latefees_days',
                ),
            ),
//        array('container'=>'classes', 'fname'=>'class_id', 
//            'fields'=>array('id'=>'class_id', 'uuid'=>'class_uuid', 'category_name', 'code'=>'class_code', 
//                'name'=>'class_name', 'flags'=>'class_flags', 'earlybird_fee', 'fee', 'virtual_fee', 'options'),
//            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.545', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Check for sections that are still open
    // Calculate late fees
    //
    $now = new DateTime('now', new DateTimezone('UTC'));
    foreach($sections as $sid => $section) {
        // No section end dates OR section end date is empty
        if( ($festival['flags']&0x09) == 0x01 || $section['live_end_dt'] == '0000-00-00 00:00:00' ) {
            $sections[$sid]['live_end_dt'] = $festival['live_date'];
            $section['live_end_dt'] = $festival['live_date'];
        }
        // If virtual enabled for festival, and no section end dates OR section end date not set
        if( ($festival['flags']&0x02) == 0x02 
            && (($festival['flags']&0x09) == 0x01 || $section['virtual_end_dt'] == '0000-00-00 00:00:00')
            ) {
            $sections[$sid]['virtual_end_dt'] = $festival['virtual_date'];
            $section['virtual_end_dt'] = $festival['virtual_date'];
        }
        if( $festival['live'] == 'no' && $section['live_end_dt'] != '0000-00-00 00:00:00' ) {
            $live_dt = new DateTime($section['live_end_dt'], new DateTimezone('UTC'));
            if( $live_dt > $now ) {
                if( ($section['flags']&0x04) == 0x04 && $festival['live'] != 'sections' ) {
                    $festival['live'] = 'hiddensections';
                } else {
                    $festival['live'] = 'sections';
                }
            }
            elseif( ($section['flags']&0x30) > 0 && $section['latefees_days'] > 0 ) {
                $interval = $live_dt->diff($now);
                $live_dt->add(new DateInterval("P{$section['latefees_days']}D"));
                if( $live_dt > $now ) {      // is within latefees_days
                    $festival['live'] = 'sections';
                    $sections[$sid]['live_days_past'] = $interval->format('%d');
                    $sections[$sid]['live_latefees'] = $section['latefees_start_amount']
                        + ($section['latefees_daily_increase'] * $sections[$sid]['live_days_past']);
                }
            }
        }
        if( $festival['virtual'] == 'no' && $section['virtual_end_dt'] != '0000-00-00 00:00:00' ) {
            $virtual_dt = new DateTime($section['virtual_end_dt'], new DateTimezone('UTC'));
            if( $virtual_dt > $now ) {
                $festival['virtual'] = 'sections';
            } 
            elseif( ($section['flags']&0x30) > 0 && $section['latefees_days'] > 0 ) {
                $interval = $virtual_dt->diff($now);
                $virtual_dt->add(new DateInterval("P{$section['latefees_days']}D"));
                if( $virtual_dt > $now ) {   // is within latefees_days
                    $festival['virtual'] = 'sections';
                    $sections[$sid]['virtual_days_past'] = $interval->format('%d');
                    $sections[$sid]['virtual_latefees'] = $section['latefees_start_amount']
                        + ($section['latefees_daily_increase'] * $sections[$sid]['virtual_days_past']);
                }
            }
        }
    }

    //
    // Load the teachers
    //
    $teachers = array();
    if( isset($_POST['action']) && $_POST['action'] == 'view' ) {
        $strsql = "SELECT customers.id, "
            . "customers.display_name "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "registrations.teacher_customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ("
                . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.teacher2_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
//                . "OR registrations.parent_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            // Search teachers from all previous festivals
//            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'teachers', 'fname'=>'id', 'fields'=>array('id', 'name'=>'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.716', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
        }
        $teachers = isset($rc['teachers']) ? $rc['teachers'] : array();
    }
    elseif( $customer_type != 20 ) {
        $strsql = "SELECT customers.id, "
            . "customers.display_name "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "registrations.teacher_customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ("
                . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
//                . "OR registrations.parent_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            // Search teachers from all previous festivals
//            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'teachers', 'fname'=>'id', 'fields'=>array('id', 'name'=>'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.653', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
        }
        $teachers = isset($rc['teachers']) ? $rc['teachers'] : array();
    }

    //
    // Load the Accompanists
    //
    $accompanists = array();
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) ) {
        if( isset($_POST['action']) && $_POST['action'] == 'view' ) {
            $strsql = "SELECT customers.id, "
                . "customers.display_name "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_customers AS customers ON ("
                    . "registrations.accompanist_customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE ("
                    . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                    . "OR registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                    . "OR registrations.teacher2_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                    . "OR registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                    . ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                // Search accompanists from all previous festivals
    //            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                . "";
        } else {
            $strsql = "SELECT customers.id, "
                . "customers.display_name "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "INNER JOIN ciniki_customers AS customers ON ("
                    . "registrations.accompanist_customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                // Search accompanists from all previous festivals
    //            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                . "";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'accompanists', 'fname'=>'id', 'fields'=>array('id', 'name'=>'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.297', 'msg'=>'Unable to load accompanists', 'err'=>$rc['err']));
        }
        $accompanists = isset($rc['accompanists']) ? $rc['accompanists'] : array();
    }

    //
    // Load the members
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $strsql = "SELECT members.id, "
            . "members.name, "
            . "members.flags, "
            . "IFNULL(festivalmembers.reg_start_dt, '') AS reg_start_dt, "
            . "IFNULL(festivalmembers.reg_end_dt, '') AS reg_end_dt, "
            . "IFNULL(festivalmembers.latedays, 0) AS latedays "
            . "FROM ciniki_musicfestivals_members AS members "
            . "LEFT JOIN ciniki_musicfestival_members AS festivalmembers ON ("
                . "members.id = festivalmembers.member_id "
                . "AND festivalmembers.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                . "AND festivalmembers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE members.status = 10 "
            . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY members.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'members', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'flags', 'reg_start_dt', 'reg_end_dt', 'latedays')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.649', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
        }
        $members = isset($rc['members']) ? $rc['members'] : array();
        $dt = new DateTime('now', new DateTimezone('UTC'));
        foreach($members as $mid => $member) {
            $members[$mid]['oname'] = $member['name'];
            if( $member['reg_start_dt'] == '' ) {
                $members[$mid]['name'] .= ' - Not yet open';
            } else {
                $sdt = new DateTime($member['reg_start_dt'], new DateTimezone('UTC'));
                $edt = new DateTime($member['reg_end_dt'], new DateTimezone('UTC'));
                if( $dt < $sdt ) {
                    $members[$mid]['name'] .= ' - Not yet open';
                } elseif( $dt > $edt ) {
                    $diff = $dt->diff($edt);
                    if( $diff->days < 1 && $member['latedays'] >= 1 ) {
                        $members[$mid]['name'] .= ' - Late fee $25';
                        $members[$mid]['open'] = 'yes';
                        $members[$mid]['latefee'] = 25;
                    } elseif( $diff->days < 2 && $member['latedays'] >= 2 ) {
                        $members[$mid]['name'] .= ' - Late fee $50';
                        $members[$mid]['open'] = 'yes';
                        $members[$mid]['latefee'] = 50;
                    } elseif( $diff->days < 3 && $member['latedays'] >= 3 ) {
                        $members[$mid]['name'] .= ' - Late fee $75';
                        $members[$mid]['open'] = 'yes';
                        $members[$mid]['latefee'] = 75;
                    } else {
                        $members[$mid]['name'] .= ' - Closed';
                    }
                } else {
                    $members[$mid]['name'] .= ' - Open';
                    $members[$mid]['open'] = 'yes';
                }
            }
        }
    }

    //
    // Check if comments or certificate requested
    //
    if( isset($request['uri_split'][4]) 
        && $request['uri_split'][4] == 'comments'
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'commentsPDF');
        $rc = ciniki_musicfestivals_templates_commentsPDF($ciniki, $tnid, array(
            'festival_id' => $festival['id'],
            'registration_uuid' => $request['uri_split'][3]
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.463', 'msg'=>'Unable to load comments', 'err'=>$rc['err']));
        }
        if( isset($rc['pdf']) ) {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Content-Type: application/pdf');
            header('Cache-Control: max-age=0');
            $rc['pdf']->Output($rc['filename'], 'I');
            return array('stat'=>'exit');
        }
    }
    if( isset($request['uri_split'][4]) 
        && $request['uri_split'][4] == 'certificate'
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationCertsPDF');
        $rc = ciniki_musicfestivals_registrationCertsPDF($ciniki, $tnid, array(
            'festival_id' => $festival['id'],
            'registration_uuid' => $request['uri_split'][3],
            'single' => 'yes', // Don't add one for each competitor in registration
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.699', 'msg'=>'Unable to load certificate', 'err'=>$rc['err']));
        }
        if( isset($rc['pdf']) ) {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Content-Type: application/pdf');
            header('Cache-Control: max-age=0');
            $rc['pdf']->Output('Certificate.pdf', 'I');
            return array('stat'=>'exit');
        }
    }

//    if( isset($request['uri_split'][4]) 
//        && $request['uri_split'][4] == 'view'
//        ) {
//    }
    //
    // Load the registration specified
    //
    if( isset($_POST['f-registration_id']) && $_POST['f-registration_id'] > 0 ) {
        $registration_id = $_POST['f-registration_id'];
        $strsql = "SELECT id AS registration_id, "
            . "uuid, "
            . "teacher_customer_id, "
            . "teacher2_customer_id, "
            . "billing_customer_id, "
            . "parent_customer_id, "
            . "accompanist_customer_id, "
            . "member_id, "
            . "rtype, "
            . "status, "
            . "flags, "
            . "invoice_id, "
            . "display_name, "
            . "public_name, "
            . "competitor1_id, "
            . "competitor2_id, "
            . "competitor3_id, "
            . "competitor4_id, "
            . "competitor5_id, "
            . "class_id, "
            . "timeslot_id, "
            . "title1, "
            . "composer1, "
            . "movements1, "
            . "perf_time1, "
            . "title2, "
            . "composer2, "
            . "movements2, "
            . "perf_time2, "
            . "title3, "
            . "composer3, "
            . "movements3, "
            . "perf_time3, "
            . "title4, "
            . "composer4, "
            . "movements4, "
            . "perf_time4, "
            . "title5, "
            . "composer5, "
            . "movements5, "
            . "perf_time5, "
            . "title6, "
            . "composer6, "
            . "movements6, "
            . "perf_time6, "
            . "title7, "
            . "composer7, "
            . "movements7, "
            . "perf_time7, "
            . "title8, "
            . "composer8, "
            . "movements8, "
            . "perf_time8, "
            . "fee, "
            . "participation, "
            . "video_url1, "
            . "video_url2, "
            . "video_url3, "
            . "video_url4, "
            . "video_url5, "
            . "video_url6, "
            . "video_url7, "
            . "video_url8, "
            . "music_orgfilename1, "
            . "music_orgfilename2, "
            . "music_orgfilename3, "
            . "music_orgfilename4, "
            . "music_orgfilename5, "
            . "music_orgfilename6, "
            . "music_orgfilename7, "
            . "music_orgfilename8, "
            . "backtrack1, "
            . "backtrack2, "
            . "backtrack3, "
            . "backtrack4, "
            . "backtrack5, "
            . "backtrack6, "
            . "backtrack7, "
            . "backtrack8, "
            . "artwork1, "
            . "artwork2, "
            . "artwork3, "
            . "artwork4, "
            . "artwork5, "
            . "artwork6, "
            . "artwork7, "
            . "artwork8, "
            . "instrument, "
            . "mark, "
            . "placement, "
            . "comments, "
            . "notes "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $registration_id) . "' ";
        if( (isset($_POST['action']) && ($_POST['action'] == 'view' || $_POST['action'] == 'comments' || $_POST['action'] == 'certificate' || $_POST['action'] == 'download' ))
            || (isset($_POST['f-action']) && $_POST['f-action'] == 'crsubmit' )
            ) {
            $strsql .= "AND ("
                . "billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR teacher2_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR parent_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . ") ";
        } else {
            $strsql .= "AND billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' ";
        }
        $strsql .= "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.423', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
        }
        if( !isset($rc['registration']) ) {
            $errors[] = array(
                'msg' => 'Unable to find the registration',
                );
            $display = 'list';
        } else {
            $registration = $rc['registration'];
//            $registration['teacher_share'] = ($registration['flags']&0x01) == 0x01 ? 'on' : 'off';
//            $registration['accompanist_share'] = ($registration['flags']&0x02) == 0x02 ? 'on' : 'off';
            if( isset($_POST['action']) && $_POST['action'] == 'view' ) {
                $display = 'view';
            } 
            elseif( isset($_POST['f-action']) && $_POST['f-action'] == 'viewupdate' ) {
                $display = 'view';
            }
            elseif( isset($_POST['action']) && $_POST['action'] == 'download' 
                && (isset($_POST['f-comments']) && $_POST['f-comments'] == 'Download Adjudicators Comments'
//                    || isset($_POST['submit']) && $_POST['submit'] == 'Comments'
                    )
                ) {
                
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'commentsPDF');
                $rc = ciniki_musicfestivals_templates_commentsPDF($ciniki, $tnid, array(
                    'festival_id' => $festival['id'],
                    'registration_id' => $registration['registration_id'],
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1022', 'msg'=>'Unable to load comments', 'err'=>$rc['err']));
                }
                if( isset($rc['pdf']) ) {
                    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Pragma: no-cache');
                    header('Content-Type: application/pdf');
                    header('Cache-Control: max-age=0');
                    $rc['pdf']->Output($rc['filename'], 'I');
                    return array('stat'=>'exit');
                }
            }
            elseif( isset($_POST['action']) && $_POST['action'] == 'download' 
                && (isset($_POST['f-certificate']) && $_POST['f-certificate'] == 'Download Certificate'
//                    || isset($_POST['submit']) && $_POST['submit'] == 'Certificate'
                    )
                ) {
                //
                // Get the certificate
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationCertsPDF');
                $rc = ciniki_musicfestivals_registrationCertsPDF($ciniki, $tnid, array(
                    'festival_id' => $festival['id'],
                    'registration_id' => $registration['registration_id'],
                    'single' => 'yes', // Don't add one for each competitor in registration
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1023', 'msg'=>'Unable to load certificate', 'err'=>$rc['err']));
                }
                if( isset($rc['pdf']) ) {
                    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Pragma: no-cache');
                    header('Content-Type: application/pdf');
                    header('Cache-Control: max-age=0');
                    $rc['pdf']->Output('Certificate.pdf', 'I');
                    return array('stat'=>'exit');
                }
            } 
            else {
                $display = 'form';
            }
        }
    }
    elseif( isset($_GET['r']) && $_GET['r'] != '' ) {
        $registration_uuid = $_GET['r'];
        $strsql = "SELECT id AS registration_id, "
            . "uuid, "
            . "teacher_customer_id, "
            . "teacher2_customer_id, "
            . "billing_customer_id, "
            . "parent_customer_id, "
            . "accompanist_customer_id, "
            . "member_id, "
            . "rtype, "
            . "status, "
            . "flags, "
            . "invoice_id, "
            . "display_name, "
            . "public_name, "
            . "competitor1_id, "
            . "competitor2_id, "
            . "competitor3_id, "
            . "competitor4_id, "
            . "competitor5_id, "
            . "class_id, "
            . "timeslot_id, "
            . "title1, "
            . "composer1, "
            . "movements1, "
            . "perf_time1, "
            . "title2, "
            . "composer2, "
            . "movements2, "
            . "perf_time2, "
            . "title3, "
            . "composer3, "
            . "movements3, "
            . "perf_time3, "
            . "title4, "
            . "composer4, "
            . "movements4, "
            . "perf_time4, "
            . "title5, "
            . "composer5, "
            . "movements5, "
            . "perf_time5, "
            . "title6, "
            . "composer6, "
            . "movements6, "
            . "perf_time6, "
            . "title7, "
            . "composer7, "
            . "movements7, "
            . "perf_time7, "
            . "title8, "
            . "composer8, "
            . "movements8, "
            . "perf_time8, "
            . "fee, "
            . "participation, "
            . "video_url1, "
            . "video_url2, "
            . "video_url3, "
            . "video_url4, "
            . "video_url5, "
            . "video_url6, "
            . "video_url7, "
            . "video_url8, "
            . "music_orgfilename1, "
            . "music_orgfilename2, "
            . "music_orgfilename3, "
            . "music_orgfilename4, "
            . "music_orgfilename5, "
            . "music_orgfilename6, "
            . "music_orgfilename7, "
            . "music_orgfilename8, "
            . "backtrack1, "
            . "backtrack2, "
            . "backtrack3, "
            . "backtrack4, "
            . "backtrack5, "
            . "backtrack6, "
            . "backtrack7, "
            . "backtrack8, "
            . "artwork1, "
            . "artwork2, "
            . "artwork3, "
            . "artwork4, "
            . "artwork5, "
            . "artwork6, "
            . "artwork7, "
            . "artwork8, "
            . "instrument, "
            . "mark, "
            . "placement, "
            . "comments, "
            . "notes "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE uuid = '" . ciniki_core_dbQuote($ciniki, $registration_uuid) . "' "
            . "AND billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.368', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
        }
        if( !isset($rc['registration']) ) {
            $errors[] = array(
                'msg' => 'Unable to find the registration',
                );
            $display = 'list';
        } else {
            $registration = $rc['registration'];
            $registration_id = $registration['registration_id'];
//            $registration['teacher_share'] = ($registration['flags']&0x01) == 0x01 ? 'on' : 'off';
//            $registration['accompanist_share'] = ($registration['flags']&0x02) == 0x02 ? 'on' : 'off';
            $display = 'form';
            if( isset($_GET['ru']) && $_GET['ru'] != '' ) {
                if( strncmp('http', $_GET['ru'], 4) == 0 ) {
                    $return_url = $_GET['ru'];
                } else {
                    $return_url = $request['ssl_domain_base_url'] . $_GET['ru'];
                }
                $request['session']['account-musicfestivals-registration-return-url'] = $return_url;
            }
        }
    }

    //
    // Check if change request submitt
    //
    if( isset($_POST['f-action']) && $_POST['f-action'] == 'crsubmit' ) {
        $display = 'view';
        if( isset($_POST['f-cr']) && $_POST['f-cr'] != '' 
            && isset($registration)
            ) {
            $strsql = "SELECT MAX(crs.cr_number) AS max "
                . "FROM ciniki_musicfestival_crs AS crs "
                . "WHERE crs.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                . "AND crs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                error_log("ERR: Unable to get max cr_number");
                $blocks[] = [
                    'type' => 'msg',
                    'level' => 'error', 
                    'content' => 'Internal Error',
                    ];
            } else {
                if( !isset($rc['num']['max']) ) {
                    $cr_number = 1;
                } else {
                    $cr_number = $rc['num']['max'] + 1;
                }
                
                $dt = new DateTime('now', new DateTimezone('UTC'));
                
                //
                // Add the change request
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.cr', [
                    'cr_number' => $cr_number,
                    'status' => 20,
                    'festival_id' => $festival['id'],
                    'customer_id' => $request['session']['customer']['id'],
                    'object' => 'ciniki.musicfestivals.registration',
                    'object_id' => $registration['registration_id'],
                    'dt_submitted' => $dt->format("Y-m-d H:i:s"),
                    'content' => $_POST['f-cr'],
                    ], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $blocks[] = [
                        'type' => 'msg',
                        'level' => 'error', 
                        'content' => 'Unable to submit change request, please try again or contact us for help.',
                        ];
                } else {
                    //
                    // Send the email to the admin
                    //
                    if( isset($festival['registration-crs-notify-emails']) && $festival['registration-crs-notify-emails'] != '' ) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'crEmail');
                        $rc = ciniki_musicfestivals_crEmail($ciniki, $tnid, [
                            'cr' => [
                                'cr_number' => $cr_number,
                                'status' => 20,
                                'customer_id' => $request['session']['customer']['id'],
                                'object' => 'ciniki.musicfestivals.registration',
                                'object_id' => $registration['registration_id'],
                                'dt_submitted' => $dt->format('Y-m-d H:i:s'),
                                'content' => $_POST['f-cr'],
                                ],
                            'registration' => $registration,
                            'notify-emails' => $festival['registration-crs-notify-emails'],
                            ]);
                        if( $rc['stat'] != 'ok' ) {
                            error_log("ERR: " . print_r($rc, true));
                        }
                    }
                    $request['session']['redirect-message'] = 'Your request has been submitted and will be reviewed.';

                    header("Location: {$base_url}");
                    return array('stat'=>'exit');
                }

            }
        } 
    }

    //
    // Get the payment status for the invoice 
    //
    if( isset($registration) ) {
        $registration['payment_status'] = 0;
        if( $registration['invoice_id'] > 0 ) {
            $strsql = "SELECT invoices.payment_status "
                . "FROM ciniki_sapos_invoices AS invoices "
                . "WHERE invoices.id = '" . ciniki_core_dbQuote($ciniki, $registration['invoice_id']) . "' "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'invoice');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.807', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
            }
            if( !isset($rc['invoice']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.808', 'msg'=>'Unable to find requested invoice'));
            }
            $invoice = $rc['invoice'];
            $registration['payment_status'] = $invoice['payment_status'];
        }
    }

    //
    // Setup the fields for the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'registrationFormGenerate');
    $rc = ciniki_musicfestivals_wng_registrationFormGenerate($ciniki, $tnid, $request, array(
        'festival' => $festival,
        'display' => $display,
        'competitors' => $competitors,
        'teachers' => $teachers,
        'accompanists' => $accompanists,
        'members' => isset($members) ? $members : null,
        'registration' => isset($registration) ? $registration : array(),
        'customer_type' => $customer_type,
        'customer_id' => $request['session']['customer']['id'],
        ));
    if( $rc['stat'] == 'noexist' ) {
        return array('stat'=>'ok', 'blocks'=>array(
            array(
                'type' => 'msg',
                'level' => 'error', 
                'content' => $rc['err']['msg'],
                ),
            ));
    } elseif( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.348', 'msg'=>'', 'err'=>$rc['err']));
    }
    $fields = $rc['fields'];
    $js = $rc['js'];
    $sections = $rc['sections'];
    if( isset($rc['selected_section']) ) {
        $selected_section = $rc['selected_section'];
    }
    if( isset($rc['selected_class']) ) {
        $selected_class = $rc['selected_class'];
        if( ($festival['flags']&0x0100) == 0x0100 ) {
            $selected_class['name'] = $selected_class['category_name'] . ' - ' . $selected_class['name'];
        }
    }
    if( isset($rc['selected_member']) ) {
        $selected_member = $rc['selected_member'];
    }

    //
    // Check if form submitted
    //
    if( isset($_POST['f-registration_id']) && isset($_POST['f-action']) && $_POST['f-action'] == 'addcompetitor' && count($errors) == 0 ) {
        //
        // Returning from add competitor form
        //
        $registration_id = $_POST['f-registration_id'];
        $display = 'form';
    }
    elseif( isset($_POST['f-registration_id']) && isset($_POST['f-action']) 
        && ($_POST['f-action'] == 'update' || $_POST['f-action'] == 'viewupdate') 
        && count($errors) == 0 
        ) {
        $registration_id = $_POST['f-registration_id'];
        $display = 'form';
        if( $_POST['f-action'] == 'viewupdate' ) {
            $display = 'view';    
        }

        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'registrationFormUpdateProcess');
        $rc = ciniki_musicfestivals_wng_registrationFormUpdateProcess($ciniki, $tnid, $request, [
            'festival' => $festival,
            'registration_id' => $registration_id,
            'display' => $display,
            'selected_class' => isset($selected_class) ? $selected_class : null,
            'selected_member' => isset($selected_member) ? $selected_member : null,
            'fields' => $fields,
            'registration' => isset($registration) ? $registration : null,
            'customer_type' => $customer_type,
            'customer_id' => $request['session']['customer']['id'],
            ]);
        if( $rc['stat'] == 'exit' || $rc['stat'] == '404' ) {
            return $rc;
        } elseif( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>[[
                'type' => 'msg',
                'level' => 'error', 
                'content' => $rc['err']['msg'],
                ]]);
        }
        $errors = $rc['errors'];
    }
    elseif( isset($_POST['f-delete']) && $_POST['f-delete'] == 'Remove' && isset($registration) ) {
        //
        // Check if paid invoice
        //
        if( $registration['payment_status'] >= 20 ) {
            $blocks[] = array(
                'type' => 'msg',
                'class' => 'limit-width limit-width-70',
                'level' => 'error',
                'content' => "This registration has been paid, please contact us to cancel.",
                );
            $display = 'list';
        }
        elseif( isset($_POST['submit']) && $_POST['submit'] == 'Remove Registration'
            && isset($_POST['f-action']) && $_POST['f-action'] == 'confirmdelete'
            ) {
            //
            // Check for a defined cart
            //
            if( isset($request['session']['cart']['id']) && $request['session']['cart']['id'] > 0 ) {
                //
                // Load the cart item
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceObjectItem');
                $rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $tnid, $request['session']['cart']['id'], 'ciniki.musicfestivals.registration', $registration['registration_id']);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.312', 'msg'=>'Unable to get invoice item', 'err'=>$rc['err']));
                }
                
                //
                // Remove the cart item
                //
                if( isset($rc['item']) ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemDelete');
                    $rc = ciniki_sapos_wng_cartItemDelete($ciniki, $tnid, $request, array(
                        'item_id' => $rc['item']['id'],
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        $blocks[] = array(
                            'type' => 'msg',
                            'level' => 'error',
                            'content' => 'This registration cannot be removed, please contact us.',
                            );
                        return array('stat'=>'ok', 'blocks'=>$blocks);
                    }
                } 
                //
                // Item doesn't exist in the cart, remove the registration
                //
                else {
                    //
                    // Remove registration files and object
                    //
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationDelete');
                    $rc = ciniki_musicfestivals__registrationDelete($ciniki, $tnid, $registration['registration_id']);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.714', 'msg'=>'Unable to remove registration', 'err'=>$rc['err']));
                    }
                }

                //
                // Reload cart
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartLoad');
                $rc = ciniki_sapos_wng_cartLoad($ciniki, $tnid, $request);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.421', 'msg'=>'Unable to load cart', 'err'=>$rc['err']));
                }

            } else {
                //
                // Remove registration files and object
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationDelete');
                $rc = ciniki_musicfestivals__registrationDelete($ciniki, $tnid, $registration['registration_id']);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.788', 'msg'=>'Unable to remove registration', 'err'=>$rc['err']));
                }
            }
           
            header("Location: {$request['ssl_domain_base_url']}/account/musicfestival/registrations");
            return array('stat'=>'exit');
        }
        else {
            $display = 'delete';
        }
    }
    elseif( isset($_GET['r']) && $_GET['r'] == 'yes' ) {
        $display = 'form';
        if( $festival['live'] == 'no' && (($festival['flags']&0x02) == 0 || $festival['virtual'] == 'no') ) {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'Registrations are closed for ' . $festival['name'],
                );
            return array('stat' => 'ok', 'blocks'=>$blocks);
        }
    }
    elseif( isset($_GET['add']) && $_GET['add'] == 'yes' ) {
        //
        // Check if registrations are still open
        //
        if( $festival['live'] == 'no' && (($festival['flags']&0x02) == 0 || $festival['virtual'] == 'no') ) {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'Registrations are closed for ' . $festival['name'],
                );
            return array('stat' => 'ok', 'blocks'=>$blocks);
        }
        $registration_id = 0;
        $display = 'form';
    }

    //
    // Prepare any errors
    //
    $form_errors = '';
    if( isset($errors) && count($errors) > 0 ) {
        foreach($errors as $err) {
            $form_errors .= ($form_errors != '' ? '<br/>' : '') . $err['msg'];
        }
    }

    //
    // Show the registration edit/add form
    //
    if( $display == 'form' ) {
        $guidelines = '';
        if( $customer_type == 10 && isset($festival['registration-parent-msg']) ) {
            $guidelines = $festival['registration-parent-msg'];
        } elseif( $customer_type == 20 && isset($festival['registration-teacher-msg']) ) {
            $guidelines = $festival['registration-teacher-msg'];
        } elseif( $customer_type == 30 && isset($festival['registration-adult-msg']) ) {
            $guidelines = $festival['registration-adult-msg'];
        }
        $blocks[] = array(
            'type' => 'form',
            'form-id' => 'addregform',
            'guidelines' => $guidelines,
            'title' => ($registration_id > 0 ? 'Update Registration' : 'Add Registration'),
            'class' => 'limit-width limit-width-80',
            'submit-buttons-class' => (isset($members) && $fields['member_id']['value'] == 0 ? 'hidden' : ''),
            'problem-list' => $form_errors,
            'cancel-label' => 'Cancel',
            'js-submit' => 'formSubmit();',
            'js-cancel' => 'formCancel();',
            'submit-label' => ($registration_id > 0 ? 'Save' : 'Save'),
            'fields' => $fields,
            'js' => $js,
            );
    }

    //
    // Show the registration in view only mode
    //
    elseif( $display == 'view' ) {
        $editable = 'no';
        foreach($fields as $fid => $field) {
            if( isset($field['id']) && $field['id'] == 'action' ) {
                $fields[$fid]['value'] = 'view';
            }
            if( $field['ftype'] == 'select' 
                && isset($field['id']) && $field['id'] == 'teacher_customer_id'
                && $field['value'] == 0
                ) {
                $fields[$fid]['value'] = 'No Teacher';
                $fields[$fid]['ftype'] = 'viewtext';
//            } elseif( $fid == 'teacher_share' ) {
//                $fields[$fid]['class'] = 'hidden';
            } elseif( $field['ftype'] == 'select' 
                && isset($field['id']) && $field['id'] == 'accompanist_customer_id'
                ) {
                // 
                // Only disable field if no editing allowed of accompanist
                //
                if( !isset($festival['edit-accompanist']) || $festival['edit-accompanist'] == 'no' 
                    || $registration['billing_customer_id'] != $request['session']['customer']['id']
                    ) {
                    $fields[$fid]['ftype'] = 'viewtext';
//                    $fields['accompanist_share']['class'] = 'hidden';
                    if( $field['value'] == 0) {
                        $fields[$fid]['value'] = 'No Accompanist';
                    } elseif( isset($field['options'][$field['value']]['name']) ) {
                        $fields[$fid]['value'] = $field['options'][$field['value']]['name'];
                    } elseif( isset($field['options'][$field['value']]) ) {
                        $fields[$fid]['value'] = $field['options'][$field['value']];
                    } elseif( isset($accompanists[$field['value']]['name']) ) {
                        $fields[$fid]['value'] = $accompanists[$field['value']]['name'];
                    } else {
                        $fields[$fid]['value'] = '';
                    }
                }
            } elseif( $field['ftype'] == 'select' 
                && isset($field['id']) && $field['id'] == 'member_id'
                ) {
                $fields[$fid]['ftype'] = 'viewtext';
                $fields[$fid]['value'] = preg_replace('/ - Late fee.*/', '', $selected_member['name']);
            } elseif( $field['ftype'] == 'minsec' 
                ) {
                $fields[$fid]['ftype'] = 'text';
                $fields[$fid]['size'] = 'small';
                $fields[$fid]['flex-basis'] = '50%';
            } elseif( $field['ftype'] == 'select' ) {
                $fields[$fid]['ftype'] = 'viewtext';
                if( isset($field['options'][$field['value']]['codename']) ) {
                    $fields[$fid]['value'] = $field['options'][$field['value']]['codename'];
                } 
                elseif( isset($field['options'][$field['value']]['name']) ) {
                    $fields[$fid]['value'] = $field['options'][$field['value']]['name'];
                }
                elseif( isset($field['options'][$field['value']]) ) {
                    $fields[$fid]['value'] = $field['options'][$field['value']];
                } 
                else {
                    $fields[$fid]['value'] = '';
                }
            } elseif( preg_match("/(video_|music_|backtrack[0-9]|artwork)/", $fid) && $fields[$fid]['value'] == '' ) {
                $fields[$fid]['value'] = 'None';
            } elseif( $fid == 'notes' && $fields[$fid]['value'] == '' ) {
                $fields[$fid]['value'] = 'None';
            }
            if( isset($field['id']) && $field['id'] == 'participation' ) {
                $fields[$fid]['ftype'] = 'viewtext';
                if( $field['value'] == 0 ) {
                    $fields[$fid]['value'] = 'in person on a date to be scheduled';
                } elseif( $field['value'] == 1 ) {
                    $fields[$fid]['value'] = 'virtually and submit a video';
                } elseif( $field['value'] == 2 ) {
                    $fields[$fid]['value'] = 'Adjudication Plus';
                }
            }
            if( isset($field['id']) && $field['id'] == 'class_id' ) {
                //
                // Load class info
                //
                $strsql = "SELECT code, name "
                    . "FROM ciniki_musicfestival_classes "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $field['value']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'class');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.256', 'msg'=>'Unable to load class', 'err'=>$rc['err']));
                }
                if( isset($rc['class']) ) {
                    $fields[$fid]['value'] = $rc['class']['code'] . ' - ' . $rc['class']['name'];
                }
            }
            // Check if competitor address should be added for view registration
            if( isset($field['label']) && preg_match("/^{$festival['competitor-label-singular']}/", $field['label']) 
                && $field['id'] != 'instrument'
                && isset($field['class']) && $field['class'] == '' 
                ) {
                //
                // Load competitor details
                //
                $strsql = "SELECT id AS competitor_id, "
                    . "uuid, "
                    . "billing_customer_id, "
                    . "name, "
                    . "pronoun, "
                    . "flags, "
                    . "public_name, "
                    . "parent, "
                    . "address, "
                    . "city, "
                    . "province, "
                    . "postal, "
                    . "phone_home, "
                    . "phone_cell, "
                    . "email, "
                    . "age, "
                    . "study_level, "
                    . "instrument, "
                    . "notes "
                    . "FROM ciniki_musicfestival_competitors "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $field['value']) . "' "
                    . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'competitor');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.418', 'msg'=>'Unable to load competitor', 'err'=>$rc['err']));
                }
                if( isset($rc['competitor']) ) {
                    $competitor = isset($rc['competitor']) ? $rc['competitor'] : array();
                    $address = $competitor['address']
                        . ($competitor['city'] != '' ? ', ' . $competitor['city'] : '')
                        . ($competitor['province'] != '' ? ', ' . $competitor['province'] : '')
                        . ($competitor['postal'] != '' ? ', ' . $competitor['postal'] : '')
                        . "";
                    $fields[$fid]['value'] = $competitor['name'] . ($competitor['pronoun'] != '' ? ' (' . $competitor['pronoun'] . ')' : '')
                        . (isset($competitor['parent']) && $competitor['parent'] != '' ? "\nParent: " . $competitor['parent'] : '')
                        . "\nAddress: " . $address
                        . "\nCell Phone: " . $competitor['phone_cell']
                        . ($competitor['phone_home'] != '' ? "\nHome Phone: " . $competitor['phone_home'] : '')
                        . "\nEmail: " . $competitor['email']
                        . "\nAge: " . $competitor['age']
                        . (isset($competitor['study_level']) && $competitor['study_level'] != '' ? "\nLevel: " . $competitor['study_level'] : '')
                        . (isset($competitor['instrument']) && $competitor['instrument'] != '' ? "\nInstrument: " . $competitor['instrument'] : '')
                        . (isset($competitor['notes']) && $competitor['notes'] != '' ? "\nNotes: " . $competitor['notes'] : '')
                        . "";
                } else {
                    $competitor = [];
                    $fields[$fid]['value'] = 'Invalid registration - No Competitor specified';
                }
                $fields[$fid]['ftype'] = 'textarea';
            }
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) 
                && preg_match("/(title|composer|movements|perf_time|video_url|music_orgfilename|backtrack[0-9]|artwork)/", $fid)
                && !preg_match("/line-title/", $fid)
                && isset($selected_member['open']) 
                && $selected_member['open'] == 'yes'
                && $registration['billing_customer_id'] == $request['session']['customer']['id']
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
                if( preg_match("/perf_time/", $fid) ) {
                    $fields[$fid]['ftype'] = 'minsec';
                }
                // Note: This is a hack to stop editing at end of registrations
                $fields[$fid]['editable'] = 'no';
//                $editable = 'no';
                $fields[$fid]['required'] = 'no';
                $fields[$fid]['ftype'] = 'viewtext'; 
                if( preg_match("/perf_time/", $fid) ) {
                    $fields[$fid]['ftype'] = 'viewtext';
                    $fields[$fid]['value'] = sprintf("%d:%02d", intval($field['value']/60),$field['value']%60);
                }  
            }
            elseif( preg_match("/^accompanist_/", $fid) 
                && isset($festival['edit-accompanist']) && $festival['edit-accompanist'] == 'yes' 
                && $registration['billing_customer_id'] == $request['session']['customer']['id']
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
                if( $fid == 'accompanist_customer_id' ) {
                    $fields[$fid]['ftype'] = 'select';
                }
            }
            elseif( preg_match("/(title|composer|movements|perf_time|video_url|music_orgfilename|backtrack[0-9]|artwork)/", $fid)
                && ($festival['edit'] == 'yes' || (isset($selected_section['edit']) && $selected_section['edit'] == 'yes'))
                && $registration['billing_customer_id'] == $request['session']['customer']['id']
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
                if( preg_match("/perf_time/", $fid) ) {
                    $fields[$fid]['ftype'] = 'minsec';
                }
            } 
            elseif( in_array($fid, ['video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8'])
                && $festival['upload'] == 'yes' 
                && $registration['billing_customer_id'] == $request['session']['customer']['id']
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
            } 
            elseif( preg_match("/music_orgfilename/", $fid) 
                && $festival['upload'] == 'yes' 
                && $registration['billing_customer_id'] == $request['session']['customer']['id']
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
            } 
            elseif( preg_match("/backtrack[0-9]/", $fid) 
                && $festival['upload'] == 'yes' 
                && $registration['billing_customer_id'] == $request['session']['customer']['id']
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
            } 
            elseif( preg_match("/(title|composer|movements)/", $fid)
                && $festival['upload'] == 'yes' 
                && ($selected_class['titleflags']&0x0300) > 0   // Artwork Class
                && $registration['billing_customer_id'] == $request['session']['customer']['id']
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
            }
            elseif( preg_match("/artwork/", $fid) 
                && $festival['upload'] == 'yes' 
                && $registration['billing_customer_id'] == $request['session']['customer']['id']
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
            } 
            elseif( $field['ftype'] == 'minsec' ) {
                $fields[$fid]['ftype'] = 'viewtext';
                $fields[$fid]['editable'] = 'no';
                $fields[$fid]['required'] = 'no';
                $fields[$fid]['value'] = sprintf("%d:%02d", intval($field['value']/60),$field['value']%60);
            }
            else {
                if( isset($fields[$fid]['ftype']) 
                    && ($fields[$fid]['ftype'] == 'text' || $fields[$fid]['ftype'] == 'file' || $fields[$fid]['ftype'] == 'textarea')
                    ) {
                    $fields[$fid]['ftype'] = 'viewtext';
                }
                $fields[$fid]['required'] = 'no';
                $fields[$fid]['editable'] = 'no';
            }
        }
        if( $editable == 'yes' && $display == 'view' ) {
            $fields['action']['value'] = 'viewupdate';
        } elseif( $editable == 'yes' ) {
            $fields['action']['value'] = 'update';
        }
        if( isset($registration['timeslot_id']) && $registration['timeslot_id'] > 0 ) {
            //
            // Get the timeslot->division->section flags to know if comments have been released
            //
            $num_adjudicators = 1;
            $strsql = "SELECT sections.id, "
                . "sections.flags, "
                . "divisions.flags AS division_flags "
//                . "sections.adjudicator1_id "
                . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
                . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                    . "timeslots.sdivision_id = divisions.id "
                    . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
                    . "divisions.ssection_id = sections.id "
                    . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE timeslots.id = '" . ciniki_core_dbQuote($ciniki, $registration['timeslot_id']) . "' "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'schedule');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.461', 'msg'=>'Unable to load schedule', 'err'=>$rc['err']));
            }
            //
            // Check if released comments
            //
            $download_buttons = '';
            if( $registration['comments'] != '' 
                && ((isset($rc['schedule']['flags']) && ($rc['schedule']['flags']&0x02) == 0x02)
                    || (isset($rc['schedule']['division_flags']) && ($rc['schedule']['division_flags']&0x02) == 0x02)
                    )
                ) {
//                $download_buttons .= "<input class='button' type='submit' name='f-comments' value='Download Adjudicators Comments'>";
                $download_buttons .= "<a class='button' target='_blank' href='{$base_url}/{$registration['uuid']}/comments'>Download Adjudicators Comments</a>";

            }
            if( $registration['comments'] != '' 
                && ((isset($rc['schedule']['flags']) && ($rc['schedule']['flags']&0x04) == 0x04)
                    || (isset($rc['schedule']['division_flags']) && ($rc['schedule']['division_flags']&0x04) == 0x04)
                    )
                ) {
                $download_buttons .= ($download_buttons != '' ? ' &nbsp; ' : '')
                    . "<a class='button' target='_blank' href='{$base_url}/{$registration['uuid']}/certificate'>Download Certificate</a>";
//                    . "<input class='button' type='submit' name='f-certificate' value='Download Certificate'>";
            }
        }
        $intro = '';
        if( isset($download_buttons) && $download_buttons != '' ) {
            $intro = "<div class='block-buttons aligncenter'><div class='content'><div class='buttons'>"
//                    . "<form action='{$base_url}' method='POST'>"
//                    . "<input type='hidden' name='f-registration_id' value='{$registration['registration_id']}' />"
//                    . "<input type='hidden' name='action' value='download' />"
                    . $download_buttons
//                    . "</form>"
                    . "</div></div></div>";
        }
        if( $display == 'view' 
            && isset($festival['registration-crs-enable']) && $festival['registration-crs-enable'] == 'yes' 
            ) {
            if( isset($festival['registration-crs-open']) && $festival['registration-crs-open'] == 'yes' ) {
                $fields['line-cr'] = array(
                    'id' => 'line-cr',
                    'ftype' => 'line',
                    'class' => (isset($_POST['f-cr']) && $_POST['f-cr'] != '' ? '' : 'hidden'),
                    );
                $fields['cr'] = [
                    'id' => 'cr',
                    'label' => 'Request a Change',
                    'separator' => 'yes',
                    'ftype' => 'textarea',
                    'size' => 'medium',
                    'class' => (isset($_POST['f-cr']) && $_POST['f-cr'] != '' ? '' : 'hidden'),
                    'value' => (isset($_POST['f-cr']) ? trim($_POST['f-cr']) : ''),
                    ];
                $fields['cr-button'] = [
                    'id' => 'cr-button',
                    'ftype' => 'button',
                    'label' => '',
                    'class' => 'alignright' . (isset($_POST['f-cr']) && $_POST['f-cr'] != '' ? '' : ' hidden'),
                    'href' => 'javascript: submitCR();',
                    'value' => 'Submit Request',
                    ];
                $js .= 'function showChangeRequest(e) {'
                        . "C.rC(C.gE('f-line-cr'), 'hidden');"
                        . "C.rC(C.gE('f-cr').parentNode, 'hidden');"
                        . "C.rC(C.gE('f-cr-button').parentNode, 'hidden');"
                        . "C.aC(e.srcElement.parentNode, 'hidden');"
                        . "if(e.srcElement.parentNode.previousSibling != null){"
                            . "e.srcElement.parentNode.previousSibling.firstChild.innerHTML='Cancel';"
                        . "}else{"
                            . "C.gE('registration-form_submit_buttons').lastChild.style.display='none';"
                        . "}"
                    . '};'
                    . 'function submitCR() {'
                        . "C.gE('f-action').value = 'crsubmit';"
                        . ($editable == 'no' ? "C.gE('registration-form').submit();" : "C.gE('submit-button').click();")
                    . '};';
            }
            //
            // Get the list of CRs already submitted
            //
            $strsql = "SELECT crs.id, "
                . "crs.cr_number, "
                . "crs.status, "
                . "crs.status AS status_text, "
                . "crs.dt_submitted, "
                . "crs.dt_completed, "
                . "crs.content "
                . "FROM ciniki_musicfestival_crs AS crs "
                . "WHERE crs.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                . "AND crs.object_id = '" . ciniki_core_dbQuote($ciniki, $registration['registration_id']) . "' "
                . "AND crs.object = 'ciniki.musicfestivals.registration' "
                . "AND crs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY crs.dt_submitted DESC "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'crs', 'fname'=>'id', 
                    'fields'=>array(
                        'id', 'cr_number', 'status', 'status_text', 'dt_submitted', 'dt_completed', 'content',
                        ),
                    'maps'=>array(
                        'status_text' => $maps['cr']['status'],
                        ),
                    'utctotz'=>array(
                        'dt_submitted' => array('timezone'=>$intl_timezone, 'format'=>'M j, Y H:i a'),
                        'dt_completed' => array('timezone'=>$intl_timezone, 'format'=>'M j, Y H:i a'),
                        ),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1086', 'msg'=>'Unable to load crs', 'err'=>$rc['err']));
            }
            $crs = isset($rc['crs']) ? $rc['crs'] : array();
            if( count($crs) > 0 ) {
                $fields['line-crs'] = array(
                    'id' => 'line-crs',
                    'ftype' => 'line',
                    'class' => '',
                    );
            }
            $crs_content = '';
            foreach($crs as $cr) {
                $status_date = "<b>Submitted</b>: " . $cr['dt_submitted'] . "<br>";
                if( $cr['status'] == 70 ) {
                    $status_date = "<b>Completed</b>: " . $cr['dt_completed'] . "<br>";
                }
                $crs_content .= ($crs_content != '' ? '<br><br>' : '') 
                    . "<b>ID</b>: " . sprintf("%04d", $cr['cr_number']) . "<br>"
                    . "<b>Status</b>: " . $cr['status_text'] . "<br>"
                    . $status_date
                    . "<span style='display: inline-block; padding-left: 1rem;'>" . preg_replace("/\n/", "<br>", $cr['content']) . "</span>";
            }
            if( $crs_content != '' ) {
                $fields["crs"] = [
                    'id' => "crs",
                    'ftype' => 'content',
                    'label' => 'Change Requests',
                    'description' => $crs_content,
                    ];
            }
        }
        $blocks[] = [
            'form-id' => 'registration-form',
            'type' => 'form',
            'title' => 'Registration',
            'guidelines' => $intro,
            'class' => 'limit-width limit-width-80',
            'problem-list' => $form_errors,
            'cancel-label' => $editable == 'yes' ? 'Cancel' : '',
            'submit-label' => 'Save',
            'submit-hide' => $editable == 'no' ? 'yes' : 'no',
            'fields' => $fields,
            'js' => $js,
            ];
        if( $editable == 'no' ) {
            $buttons = [];
            $buttons[] = ['url' => '/account/musicfestival/registrations', 'text' => 'Back'];
            if( isset($festival['registration-crs-open']) && $festival['registration-crs-open'] == 'yes' ) {
                $buttons[] = ['js' => 'showChangeRequest(event);', 'text' => 'Request Change'];
            }
            $blocks[] = [
                'id' => 'form-buttons',
                'type' => 'buttons',
                'class' => 'limit-width limit-width-80 alignapart',
                'items' => $buttons,
                ];
        } elseif( isset($festival['registration-crs-open']) && $festival['registration-crs-open'] == 'yes' ) {
            $buttons = [['js' => 'showChangeRequest(event);', 'text' => 'Request Change']];
            $blocks[] = [
                'id' => 'form-buttons',
                'type' => 'buttons',
                'class' => 'limit-width limit-width-80 alignapart',
                'items' => $buttons,
                ];
        }
        if( isset($download_buttons) && $download_buttons != '' ) {
            $blocks[] = array(
                'type' => 'html',
                'class' => 'aligncenter',
                'html' => "<div class='block-text aligncenter'><div class='wrap'><div class='content'>"
                    . "<form action='{$base_url}' method='POST'>"
                    . "<input type='hidden' name='f-registration_id' value='{$registration['registration_id']}' />"
                    . "<input type='hidden' name='action' value='download' />"
                    . $download_buttons
                    . "</form>"
                    . "<br/>"
                    . "</div></div></div>",
                );
        } 
    }
    //
    // Show the delete form
    //
    elseif( $display == 'delete' ) {
        
        $blocks[] = array(
            'type' => 'form',
            'title' => 'Remove Registration',
            'class' => 'limit-width limit-width-50',
            'cancel-label' => 'Cancel',
            'submit-label' => 'Remove Registration',
            'fields' => array(
                'registration_id' => array(
                    'id' => 'registration_id',
                    'ftype' => 'hidden',
                    'value' => $registration['registration_id'],
                    ),
                'delete' => array(
                    'id' => 'delete',
                    'ftype' => 'hidden',
                    'value' => 'Remove',
                    ),
                'action' => array(
                    'id' => 'action',
                    'ftype' => 'hidden',
                    'value' => 'confirmdelete',
                    ),
                'msg' => array(
                    'id' => 'content',
                    'ftype' => 'content',
                    'label' => 'Are you sure you want to remove ' . $registration['display_name'] . ' in ' . $selected_class['codename'] . '?',
                    ),
                ),
            );
    }
    //
    // Show the list of registrations
    //
    else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'registrationsListGenerate');
        $rc = ciniki_musicfestivals_wng_registrationsListGenerate($ciniki, $tnid, $request, [
            'festival' => $festival,
            'form_errors' => $form_errors,
            'customer_type' => $customer_type,
            'base_url' => $base_url,
            ]);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['blocks']) ) {
            foreach($rc['blocks'] as $block) {
                $blocks[] = $block;
            }
        }

        if( ($festival['flags']&0x01) == 0x01 && isset($customer_switch_type_block)) {
            $blocks[] = $customer_switch_type_block;
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
