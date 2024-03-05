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
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestivalregistrations';
    $display = 'list';
    $form_errors = '';
    $errors = array();

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
    // Load the festival details
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE ciniki_musicfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_musicfestival_settings.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.422', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    if( isset($rc['settings']) ) {
        foreach($rc['settings'] as $k => $v) {
            $festival[$k] = $v;
        }
    }

    //
    // Load the customer type, or ask for customer type
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountCustomerTypeProcess');
    $rc = ciniki_musicfestivals_wng_accountCustomerTypeProcess($ciniki, $tnid, $request, array(
        'festival' => $festival,
        'base_url' => $base_url,
        ));
    if( $rc['stat'] == 'exit' ) {
        return $rc;
    }
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.362', 'msg'=>'Unable to ', 'err'=>$rc['err']));
    }
    if( isset($rc['stop']) && $rc['stop'] == 'yes' ) {
        // 
        // Return form with select customer type
        //
        return $rc;
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
                'shared' => 'yes',
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

    //
    // Get the list of competitors
    // Search only current festival, as ages will have changed from previous festivals
    //
    $strsql = "SELECT competitors.id, "
        . "competitors.name, "
        . "competitors.pronoun "
        . "FROM ciniki_musicfestival_competitors AS competitors "
        . "WHERE billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "ORDER BY competitors.name "
        . "";
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
        . "sections.name AS section_name, "
        . "sections.live_end_dt, "
        . "sections.virtual_end_dt, "
        . "categories.name AS category_name, "
        . "classes.id AS class_id, "
        . "classes.uuid AS class_uuid, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "classes.flags AS class_flags, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (sections.flags&0x01) = 0 "
        . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'live_end_dt', 'virtual_end_dt'),
            ),
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'uuid'=>'class_uuid', 'category_name', 'code'=>'class_code', 
                'name'=>'class_name', 'flags'=>'class_flags', 'earlybird_fee', 'fee', 'virtual_fee'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.545', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Check for sections that are still open
    //
    if( ($festival['flags']&0x09) == 0x09 ) {
        $dt = new DateTime('now', new DateTimezone('UTC'));
        foreach($sections as $sid => $section) {
            if( $festival['live'] == 'no' && $section['live_end_dt'] != '0000-00-00 00:00:00' ) {
                $live_dt = new DateTime($section['live_end_dt'], new DateTimezone('UTC'));
                if( $live_dt > $dt ) {
                    $festival['live'] = 'sections';
                }
            }
            if( $festival['live'] == 'no' && $section['virtual_end_dt'] != '0000-00-00 00:00:00' ) {
                $virtual_dt = new DateTime($section['virtual_end_dt'], new DateTimezone('UTC'));
                if( $live_dt > $dt ) {
                    $festival['virtual'] = 'sections';
                }
            }
        }
    }

    //
    // Load the teachers
    //
    $teachers = array();
    if( $customer_type != 20 ) {
        $strsql = "SELECT customers.id, "
            . "customers.display_name "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "registrations.teacher_customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
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
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $strsql = "SELECT members.id, "
            . "members.name, "
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
            array('container'=>'members', 'fname'=>'id', 'fields'=>array('id', 'name', 'reg_start_dt', 'reg_end_dt', 'latedays')),
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
    // Load the registration specified
    //
    if( isset($_POST['f-registration_id']) && $_POST['f-registration_id'] > 0 ) {
        $registration_id = $_POST['f-registration_id'];
        $strsql = "SELECT id AS registration_id, "
            . "uuid, "
            . "teacher_customer_id, "
            . "billing_customer_id, "
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
            . "instrument, "
            . "mark, "
            . "placement, "
            . "comments, "
            . "notes "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $registration_id) . "' "
            . "AND billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
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
            $registration['teacher_share'] = ($registration['flags']&0x01) == 0x01 ? 'on' : 'off';
            $registration['accompanist_share'] = ($registration['flags']&0x02) == 0x02 ? 'on' : 'off';
            if( isset($_POST['action']) && $_POST['action'] == 'view' ) {
                $display = 'view';
            } 
            elseif( isset($_POST['f-action']) && $_POST['f-action'] == 'viewupdate' ) {
                $display = 'view';
            }
            elseif( isset($_POST['action']) && $_POST['action'] == 'comments' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'commentsPDF');
                $rc = ciniki_musicfestivals_templates_commentsPDF($ciniki, $tnid, array(
                    'festival_id' => $festival['id'],
                    'registration_id' => $registration['registration_id'],
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

//            } elseif( isset($_POST['action']) && $_POST['action'] == 'update' ) {
//                $display = 'update';
            } else {
                $display = 'form';
            }
        }
    }
    elseif( isset($_GET['r']) && $_GET['r'] != '' ) {
        $registration_uuid = $_GET['r'];
        $strsql = "SELECT id AS registration_id, "
            . "uuid, "
            . "teacher_customer_id, "
            . "billing_customer_id, "
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
            $registration['teacher_share'] = ($registration['flags']&0x01) == 0x01 ? 'on' : 'off';
            $registration['accompanist_share'] = ($registration['flags']&0x02) == 0x02 ? 'on' : 'off';
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
    if( $rc['stat'] != 'ok' ) {
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
        //
        // Check if member is still open
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
            if( !isset($selected_member) ) {
                $errors[] = array(
                    'msg' => "You must specify the recommending local festival.",
                    );
            }
            elseif( !isset($selected_member['open']) || $selected_member['open'] != 'yes' ) {
                $errors[] = array(
                    'msg' => "Registrations are closed for " . $selected_member['oname'] . ".",
                    );
            }
        }
        if( !isset($selected_class) ) {
            $errors[] = array(
                'msg' => "Internal error, please contact us for help.",
                );
        }
       
        if( count($errors) == 0 ) {
            foreach($fields as $field) {
                if( $field['ftype'] == 'line'
                    || ($field['id'] == 'parent' && $customer_type == 30)
                    || (($selected_class['flags']&0x40) == 0 && $field['id'] == 'competitor4_id')
                    || (($selected_class['flags']&0x20) == 0 && $field['id'] == 'competitor3_id')
                    || (($selected_class['flags']&0x10) == 0 && $field['id'] == 'competitor2_id')
                    || (($selected_class['flags']&0x04) == 0 && $field['id'] == 'instrument')
    //                || (($selected_class['flags']&0x8000) == 0x8000 && $field['id'] == 'title3')
    //                || (($selected_class['flags']&0x8000) == 0x8000 && $field['id'] == 'perf_time3')
    //                || (($selected_class['flags']&0x4000) == 0 && $field['id'] == 'title3')
    //                || (($selected_class['flags']&0x4000) == 0 && $field['id'] == 'perf_time3')
    //                || (($selected_class['flags']&0x2000) == 0x2000 && $field['id'] == 'title2')
    //                || (($selected_class['flags']&0x2000) == 0x2000 && $field['id'] == 'perf_time2')
    //                || (($selected_class['flags']&0x1000) == 0 && $field['id'] == 'title2')
    //                || (($selected_class['flags']&0x1000) == 0 && $field['id'] == 'perf_time2')
                    || ($customer_type != 20 && $fields['teacher_customer_id'] != -1 && $field['id'] == 'teacher_email')
                    ) {
                    continue;
                }
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) 
                    && $fields['accompanist_customer_id'] != -1 
                    && $field['id'] == 'accompanist_email'
                    ) {
                    continue;
                }
    /*            if( isset($field['required']) && $field['required'] == 'yes' && $field['value'] <= 0 && $field['id'] == 'member_id' ) {
                    $errors[] = array(
                        'msg' => 'You must specify the festival that recommended you.',
                        );
                }
                else */
                if( isset($field['required']) && $field['required'] == 'yes' && $field['value'] < 0 && $field['id'] == 'participation' ) {
                    $errors[] = array(
                        'msg' => 'You must specify how you want to participate.',
                        );
                }
                elseif( isset($field['required']) && $field['required'] == 'yes' && $field['ftype'] == 'file' ) {
                    if( $field['value'] == '' && (!isset($_FILES["file-{$field['id']}"]['name']) || $_FILES["file-{$field['id']}"]['name'] == '') ) {
                        $errors[] = array(
                            'msg' => 'You must specify the registration ' . (isset($field['error_label']) ? $field['error_label'] : $field['label']) . '.',
                            );
                    }
                }
                elseif( isset($field['required']) && $field['required'] == 'yes' && $field['value'] == 0 && $field['ftype'] == 'minsec' ) {
                    $errors[] = array(
                        'msg' => 'You must specify the registration ' . (isset($field['error_label']) ? $field['error_label'] : $field['label']) . '.',
                        );
                }
                elseif( isset($field['required']) && $field['required'] == 'yes' && $field['value'] == 0 && strncmp($field['id'], 'competitor', 10) == 0 ) {
                    $errors[] = array(
                        'msg' => 'You must specify the registration ' . (isset($field['error_label']) ? $field['error_label'] : $field['label']) . '.',
                        );
                }
                elseif( isset($field['required']) && $field['required'] == 'yes' && $field['value'] == 0 && $field['ftype'] == 'minsec' ) {
                    $errors[] = array(
                        'msg' => 'You must specify the registration ' . (isset($field['error_label']) ? $field['error_label'] : $field['label']) . '.',
                        );

                }
                elseif( isset($field['required']) && $field['required'] == 'yes' && $field['value'] == '' && $field['id'] != 'termstitle' ) {
                    $errors[] = array(
                        'msg' => 'You must specify the registration ' . (isset($field['error_label']) ? $field['error_label'] : $field['label']) . '.',
                        );
                }
            }

            //
            // Check if teacher needs to be setup
            //
            if( $customer_type == 20 ) {
                $registration['teacher_customer_id'] = $request['session']['customer']['id'];
            }
            elseif( $fields['teacher_customer_id']['value'] == -1 ) {
                if( $fields['teacher_email']['value'] == '' ) {
                    $errors[] = array(
                        'msg' => "You must specify your teacher's email.",
                        );
                } else {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'teacherCreate');
                    $rc = ciniki_musicfestivals_wng_teacherCreate($ciniki, $tnid, $request, array());
                    if( $rc['stat'] == 'ok' ) {
                        $fields['teacher_customer_id']['value'] = $rc['teacher_customer_id'];
                    }
                    elseif( $rc['stat'] == '404' ) {
                        return $rc;
                    }
                    elseif( $rc['stat'] == 'error' ) {
                        $errors[] = array(
                            'msg' => $rc['err']['msg'],
                            );
                    } else {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.370', 'msg'=>'Unable to create teacher', 'err'=>$rc['err']));
                    }
                }
            }

            //
            // Check if accompanist needs to be setup
            //
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) 
                && $fields['accompanist_customer_id']['value'] == -1 
                ) {
                if( $fields['accompanist_email']['value'] == '' ) {
                    $errors[] = array(
                        'msg' => "You must specify your accompanist's email.",
                        );
                } else {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accompanistCreate');
                    $rc = ciniki_musicfestivals_wng_accompanistCreate($ciniki, $tnid, $request, array());
                    if( $rc['stat'] == 'ok' ) {
                        $fields['accompanist_customer_id']['value'] = $rc['accompanist_customer_id'];
                    }
                    elseif( $rc['stat'] == '404' ) {
                        return $rc;
                    }
                    elseif( $rc['stat'] == 'error' ) {
                        $errors[] = array(
                            'msg' => $rc['err']['msg'],
                            );
                    } else {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.647', 'msg'=>'Unable to create accompanist', 'err'=>$rc['err']));
                    }
                }
            }
        }

        //
        // Check the cart still exists
        //
        if( count($errors) == 0 && isset($request['session']['cart']['sapos_id']) && $request['session']['cart']['sapos_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartLoad');
            $rc = ciniki_sapos_wng_cartLoad($ciniki, $tnid, $request);
            if( $rc['stat'] != 'ok' ) {
                //
                // Unable to load cart, create a new cart
                //
                error_log("WNG: Cart does not exist, creating new one");
                $request['session']['cart']['sapos_id'] = 0;
            }
        }
        //
        // If the cart doesn't exist, create one now
        //
        if( count($errors) == 0 
            && (!isset($request['session']['cart']['sapos_id']) || $request['session']['cart']['sapos_id'] == 0)
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartCreate');
            $rc = ciniki_sapos_wng_cartCreate($ciniki, $tnid, $request, array());
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.310', 'msg'=>'Error opening cart', 'err'=>$rc['err']));
            }
        }


        //
        // If no errors, add/update the registration
        //
        if( $fields['registration_id']['value'] == 0 && count($errors) == 0 ) {
            //
            // Create the registration
            //
            $registration = array(
                'festival_id' => $festival['id'],
                'billing_customer_id' => $request['session']['customer']['id'],
                'teacher_customer_id' => ($customer_type == 20 ? $request['session']['customer']['id'] : $fields['teacher_customer_id']['value']),
                'accompanist_customer_id' => isset($fields['accompanist_customer_id']['value']) ? $fields['accompanist_customer_id']['value'] : 0,
                'member_id' => isset($fields['member_id']['value']) ? $fields['member_id']['value'] : 0,
                'rtype' => 30,
                'status' => 6,
                'flags' => 0,
                'invoice_id' => $request['session']['cart']['id'],
                'display_name' => '',
                'public_name' => '',
                'competitor1_id' => $fields['competitor1_id']['value'],
                'competitor2_id' => $fields['competitor2_id']['value'],
                'competitor3_id' => $fields['competitor3_id']['value'],
                'competitor4_id' => $fields['competitor4_id']['value'],
                'class_id' => $selected_class['id'],
                'timeslot_id' => 0,
                'instrument' => isset($fields['instrument']['value']) ? $fields['instrument']['value'] : '',
                'payment_type' => 0,
                'participation' => (isset($fields['participation']['value']) ? $fields['participation']['value'] : ''),
                'notes' => $fields['notes']['value'],
                );
            for($i = 1; $i <= 8; $i++) {
                $registration["title{$i}"] = isset($fields["title{$i}"]['value']) ? $fields["title{$i}"]['value'] : '';
                $registration["composer{$i}"] = isset($fields["composer{$i}"]['value']) ? $fields["composer{$i}"]['value'] : '';
                $registration["movements{$i}"] = isset($fields["movements{$i}"]['value']) ? $fields["movements{$i}"]['value'] : '';
                $registration["perf_time{$i}"] = isset($fields["perf_time{$i}"]['value']) ? $fields["perf_time{$i}"]['value'] : '';
                $registration["video_url{$i}"] = isset($fields["video_url{$i}"]['value']) ? $fields["video_url{$i}"]['value'] : '';
            }
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) ) {
                $registration['accompanist_customer_id'] = $fields['accompanist_customer_id']['value'];
            }
            if( isset($fields['teacher_share']['value']) && $fields['teacher_share']['value'] == 'on' ) {
                $registration['flags'] |= 0x01;
            }
            if( isset($fields['accompanist_share']['value']) && $fields['accompanist_share']['value'] == 'on' ) {
                $registration['flags'] |= 0x02;
            }
            if( ($selected_class['flags']&0x20) == 0x20 ) {
                $registration['rtype'] = 60;
            } elseif( ($selected_class['flags']&0x10) == 0x10 ) {
                $registration['rtype'] = 50;
            }
            // Virtual pricing
            if( $festival['earlybird'] == 'yes' && $selected_class['earlybird_fee'] > 0 ) {
                $registration['fee'] = $selected_class['earlybird_fee'];
            } else {
                $registration['fee'] = $selected_class['fee'];
            }
            if( ($festival['flags']&0x10) == 0x10 && $fields['participation']['value'] == 2 && $selected_class['plus_fee'] > 0 ) {
                $registration['fee'] = $selected_class['plus_fee'];
            }
            elseif( ($festival['flags']&0x04) == 0x04 && $fields['participation']['value'] == 1 && $selected_class['virtual_fee'] > 0 ) {
                $registration['fee'] = $selected_class['virtual_fee'];
            }

            // Setup the description for the invoice 
            $description = $selected_class['name'];
            if( $registration['participation'] == 1 ) {
                $description .= ' (Virtual)';
            } elseif( $registration['participation'] == 0 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) ) {
                $description .= ' (Live)';
            } elseif( $registration['participation'] == 2) {
                $description .= ' (Adjudication Plus)';
            }

            //
            // Get the UUID so it can be used for adding files
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
            $rc = ciniki_core_dbUUID($ciniki, 'ciniki.musicfestivals');
            if( $rc['stat'] != 'ok' ) {
                error_log('unable to get uuid');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.454', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
            }
            $registration['uuid'] = $rc['uuid'];

            //
            // Check for music uploads
            //
            foreach(['music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3', 'music_orgfilename4', 'music_orgfilename5', 'music_orgfilename6', 'music_orgfilename7', 'music_orgfilename8'] as $fid) {
                $field = $fields[$fid];
                if( isset($_POST["f-{$field['id']}"]) && $_POST["f-{$field['id']}"] != '' ) {
                    if( isset($_FILES["file-{$field['id']}"]["name"]) 
                        && isset($_FILES["file-{$field['id']}"]["tmp_name"]) 
                        && file_exists($_FILES["file-{$field['id']}"]['tmp_name'])
                        ) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'storageFileAdd');
                        $rc = ciniki_core_storageFileAdd($ciniki, $tnid, 'ciniki.musicfestivals.registration', array(
                            'uuid' => $registration['uuid'] . '_' . $field['storage_suffix'],
                            'subdir' => 'files',
                            'binary_content' => file_get_contents($_FILES["file-{$field['id']}"]['tmp_name']),
                            ));
                        if( $rc['stat'] != 'ok' ) {
                            error_log('unable to store file');
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.429', 'msg'=>'Unable to store uploaded file', 'err'=>$rc['err']));
                        }
//                        $registration[$field['id']] = $field['value'];
                        $registration[$field['id']] = $_FILES["file-{$field['id']}"]['name'];
                    }
                }
            }

            //
            // Check for backtrack uploads
            //
            foreach(['backtrack1', 'backtrack2', 'backtrack3', 'backtrack4', 'backtrack5', 'backtrack6', 'backtrack7', 'backtrack8'] as $fid) {
                $field = $fields[$fid];
                if( isset($_POST["f-{$field['id']}"]) && $_POST["f-{$field['id']}"] != '' ) {
                    if( isset($_FILES["file-{$field['id']}"]["name"]) 
                        && isset($_FILES["file-{$field['id']}"]["tmp_name"]) 
                        && file_exists($_FILES["file-{$field['id']}"]['tmp_name'])
                        ) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'storageFileAdd');
                        $rc = ciniki_core_storageFileAdd($ciniki, $tnid, 'ciniki.musicfestivals.registration', array(
                            'uuid' => $registration['uuid'] . '_' . $field['storage_suffix'],
                            'subdir' => 'files',
                            'binary_content' => file_get_contents($_FILES["file-{$field['id']}"]['tmp_name']),
                            ));
                        if( $rc['stat'] != 'ok' ) {
                            error_log('unable to store file');
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.429', 'msg'=>'Unable to store uploaded file', 'err'=>$rc['err']));
                        }
//                        $registration[$field['id']] = $field['value'];
                        $registration[$field['id']] = $_FILES["file-{$field['id']}"]['name'];
                    }
                }
            }
            //
            // Add the registration
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.371', 'msg'=>'Unable to add the competitor', 'err'=>$rc['err']));
            }
            $registration_id = $rc['id'];

            //
            // Update the names
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationNameUpdate');
            $rc = ciniki_musicfestivals_registrationNameUpdate($ciniki, $tnid, $registration_id);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.416', 'msg'=>'Unable to updated registration name', 'err'=>$rc['err']));
            }
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                $registration['display_name'] = $rc['pn_display_name'];
                $registration['public_name'] = $rc['pn_public_name'];
            } else {
                $registration['display_name'] = $rc['display_name'];
                $registration['public_name'] = $rc['public_name'];
            }

            //
            // Generate notes field for invoice
            //
            $notes = $registration['display_name'];
            $titles = '';
            for($i = 1; $i <= 8; $i++) {
                if( $registration["title{$i}"] != '' ) {
                    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x040000) ) {
                        $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $registration, $i);
                        if( isset($rc['title']) ) {
                            $registration["title{$i}"] = $rc['title'];
                        }
                    }
                    if( $titles != '' && $i > 1 ) {
                        if( strncmp($titles, '1', 1) != 0 ) {
                            $titles = "1. " . $titles . "\n{$i}. ";
                        } else {
                            $titles .= "\n{$i}. ";
                        }
                    }
                    $titles .= $registration["title{$i}"];
                }
            }
            if( $titles != '' ) {
                $notes .= "\n" . $titles;
            }

            //
            // Add to the cart
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemAdd');
            $rc = ciniki_sapos_wng_cartItemAdd($ciniki, $tnid, $request, array(
                'object' => 'ciniki.musicfestivals.registration',
                'object_id' => $registration_id,
                'price_id' => 0,
                'quantity' => 1,
                'flags' => 0x08,
                'code' => $selected_class['code'],
                'description' => $description,
                'unit_amount' => $registration['fee'],
                'unit_discount_amount' => 0,
                'unit_discount_percentage' => 0,
                'taxtype_id' => 0,
                'notes' => $notes,
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.309', 'msg'=>'Unable to add to cart', 'err'=>$rc['err']));
            }

            //
            // Make sure member late fee added if applicable
            //
            if( isset($selected_member['latefee']) ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'invoiceMemberLateFeeUpdate');
                $rc = ciniki_musicfestivals_invoiceMemberLateFeeUpdate($ciniki, $tnid, $request['session']['cart']['id'], $selected_member['latefee']);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }

            //
            // Reload cart
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartLoad');
            $rc = ciniki_sapos_wng_cartLoad($ciniki, $tnid, $request);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.332', 'msg'=>'Unable to load cart', 'err'=>$rc['err']));
            }
           
            if( isset($request['session']['account-musicfestivals-registration-return-url']) ) {
                header("Location: {$request['session']['account-musicfestivals-registration-return-url']}");
                unset($request['session']['account-musicfestivals-registration-return-url']);
                return array('stat'=>'exit');
            }

            header("Location: {$request['ssl_domain_base_url']}/account/musicfestivalregistrations");
            return array('stat'=>'exit');
        }
        elseif( count($errors) == 0 ) {
            //
            // Check for updates to selected class
            //
            if( ($selected_class['flags']&0x20) == 0x20 ) {
                $registration['rtype'] = 60;
            } elseif( ($selected_class['flags']&0x10) == 0x10 ) {
                $registration['rtype'] = 50;
            } else {
                $registration['rtype'] = 30;
            }
            // Virtual pricing
            if( isset($selected_class['earlybird_fee']) && $festival['earlybird'] == 'yes' && $selected_class['earlybird_fee'] > 0 ) {
                $new_fee = $selected_class['earlybird_fee'];
            } else {
                $new_fee = $selected_class['fee'];
            }
            if( ($festival['flags']&0x10) == 0x10 && $fields['participation']['value'] == 2 && $selected_class['plus_fee'] > 0 ) {
                $new_fee = $selected_class['plus_fee'];
            } 
            elseif( ($festival['flags']&0x04) == 0x04 && $fields['participation']['value'] == 1 && $selected_class['virtual_fee'] > 0 ) {
                $new_fee = $selected_class['virtual_fee'];
            }

            $update_args = array();
            foreach($fields as $field) {
                if( $field['ftype'] == 'content' || $field['ftype'] == 'hidden' || $field['ftype'] == 'line' 
                    || strncmp($field['id'], 'section', 7) == 0 
                    || $field['id'] == 'teacher_name'
                    || $field['id'] == 'teacher_email'
                    || $field['id'] == 'teacher_phone'
                    || $field['id'] == 'accompanist_name'
                    || $field['id'] == 'accompanist_email'
                    || $field['id'] == 'accompanist_phone'
                    || $field['ftype'] == 'button'
                    || $field['ftype'] == 'newline'
                    ) {
                    continue;
                }
                if( strncmp($field['id'], 'section', 7) == 0 ) {
                    continue;
                }
                if( $field['id'] == 'teacher_share' ) {
                    if( ($registration['flags']&0x01) == 0 && $field['value'] == 'on' ) {
                        $registration['flags'] |= 0x01;
                        $update_args['flags'] = $registration['flags'];
                    } elseif( ($registration['flags']&0x01) == 0x01 && $field['value'] == 'off' ) {
                        $registration['flags'] = ($registration['flags']&0xFFFE);
                        $update_args['flags'] = $registration['flags'];
                    }
                }
                if( $field['id'] == 'accompanist_share' ) {
                    if( ($registration['flags']&0x02) == 0 && $field['value'] == 'on' ) {
                        $registration['flags'] |= 0x02;
                        $update_args['flags'] = $registration['flags'];
                    } elseif( ($registration['flags']&0x02) == 0x02 && $field['value'] == 'off' ) {
                        $registration['flags'] = ($registration['flags']&0xFFFD);
                        $update_args['flags'] = $registration['flags'];
                    }
                }
                //
                // Skip fields when editing a pending or paid registration
                //
                if( isset($registration['status']) && $registration['status'] != 6 
                    && !preg_match("/(title|composer|movements|perf_time|video_url|music_orgfilename|backtrack)/", $field['id'])
//                    && !in_array($field['id'], ['title1', 'title2', 'title3', 'perf_time1', 'perf_time2', 'perf_time3', 'video_url1', 'video_url2', 'video_url3', 'music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3'])
                    ) {
                    continue;
                }

                if( $field['ftype'] == 'file' ) {
                    if( isset($_POST["f-{$field['id']}"]) && $_POST["f-{$field['id']}"] != '' ) {
                        if( isset($_FILES["file-{$field['id']}"]["name"]) 
                            && isset($_FILES["file-{$field['id']}"]["tmp_name"]) 
                            && $_FILES["file-{$field['id']}"]["tmp_name"] != '' 
                            ) {
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'storageFileAdd');
                            $rc = ciniki_core_storageFileAdd($ciniki, $tnid, 'ciniki.musicfestivals.registration', array(
                                'uuid' => $registration['uuid'] . '_' . $field['storage_suffix'],
                                'subdir' => 'files',
                                'binary_content' => file_get_contents($_FILES["file-{$field['id']}"]['tmp_name']),
                                ));
                            if( $rc['stat'] != 'ok' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.428', 'msg'=>'Unable to store uploaded file', 'err'=>$rc['err']));
                            }
                            //$update_args[$field['id']] = $field['value'];
                            $update_args[$field['id']] = $_FILES["file-{$field['id']}"]['name'];
                        }
                    }
                }
                elseif( $field['ftype'] == 'break' ) {
                    // Do nothing
                }
                elseif( !isset($registration[$field['id']]) || $field['value'] != $registration[$field['id']] ) {
                    $update_args[$field['id']] = $field['value'];
                }
            }
            if( !isset($registration['status']) || $registration['status'] < 50 ) {
                if( $selected_class['id'] != $registration['class_id'] ) {
                    $update_args['class_id'] = $selected_class['id'];
                }
                if( isset($new_fee) && $new_fee != $registration['fee'] ) {
                    $update_args['fee'] = $new_fee;
                    $registration['fee'] = $new_fee;
                }
            }
            if( isset($update_args['participation']) ) {
                $registration['participation'] = $update_args['participation'];
            }

            //
            // Make sure the member late fee has been applied to the cart
            //
            if( isset($selected_member['latefee']) ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'invoiceMemberLateFeeUpdate');
                $rc = ciniki_musicfestivals_invoiceMemberLateFeeUpdate($ciniki, $tnid, $registration['invoice_id'], $selected_member['latefee']);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }

            //
            // Update the registration
            //
            if( count($update_args) > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration_id, $update_args, 0x07);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.372', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
                }

                //
                // Check if any names need changing
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationNameUpdate');
                $rc = ciniki_musicfestivals_registrationNameUpdate($ciniki, $tnid, $registration_id);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.311', 'msg'=>'Unable to updated registration name', 'err'=>$rc['err']));
                }
                //
                // Update registration values as they are needed when we update the cart below
                //
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                    $registration['display_name'] = $rc['pn_display_name'];
                    $registration['public_name'] = $rc['pn_public_name'];
                } else {
                    $registration['display_name'] = $rc['display_name'];
                    $registration['public_name'] = $rc['public_name'];
                }
                for($i = 1; $i <= 8; $i++) {
                    if( isset($update_args["title{$i}"]) ) {
                        $registration["title{$i}"] = $update_args["title{$i}"];
                    }
                    if( isset($update_args["composer{$i}"]) ) {
                        $registration["composer{$i}"] = $update_args["composer{$i}"];
                    }
                    if( isset($update_args["movements{$i}"]) ) {
                        $registration["movements{$i}"] = $update_args["movements{$i}"];
                    }
                }

                //
                // Load the cart item
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceObjectItem');
                //$rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $tnid, $request['session']['cart']['id'], 'ciniki.musicfestivals.registration', $registration_id);
                $rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $tnid, $registration['invoice_id'], 'ciniki.musicfestivals.registration', $registration_id);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.541', 'msg'=>'Unable to get invoice item', 'err'=>$rc['err']));
                }
                $item = $rc['item'];

                //
                // Check if anything changed in the cart
                //
                $update_item_args = array();
                $notes = $registration['display_name'];
                $titles = '';
                for($i = 1; $i <= 8; $i++) {
                    if( $registration["title{$i}"] != '' ) {
                        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x040000) ) {
                            $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $registration, $i);
                            if( isset($rc['title']) ) {
                                $registration["title{$i}"] = $rc['title'];
                            }
                        }
                        if( $titles != '' && $i > 1 ) {
                            if( strncmp($titles, '1', 1) != 0 ) {
                                $titles = "1. " . $titles . "\n{$i}. ";
                            } else {
                                $titles .= "\n{$i}. ";
                            }
                        }
                        $titles .= $registration["title{$i}"];
                    }
                }
                if( $titles != '' ) {
                    $notes .= "\n" . $titles;
                }

                if( $item['code'] != $selected_class['code'] ) {
                    $update_item_args['code'] = $selected_class['code'];
                }
                $description = $selected_class['name'];
                if( $registration['participation'] == 1 ) {
                    $description .= ' (Virtual)';
                } elseif( $registration['participation'] == 0 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) ) {
                    $description .= ' (Live)';
                } elseif( $registration['participation'] == 2) {
                    $description .= ' (Adjudication Plus)';
                }
                if( $item['description'] != $description ) {
                    $update_item_args['description'] = $description;
                }
                if( $item['unit_amount'] != $registration['fee'] ) {
                    $update_item_args['unit_amount'] = $registration['fee'];
                }
                if( $item['notes'] != $notes ) {
                    $update_item_args['notes'] = $notes;
                }
                if( count($update_item_args) > 0 ) {
                    $update_item_args['item_id'] = $item['id'];
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemUpdate');
                    $rc = ciniki_sapos_wng_cartItemUpdate($ciniki, $tnid, $request, $update_item_args);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.546', 'msg'=>'Unable to update invoice', 'err'=>$rc['err']));
                    }
                }
            }

            if( isset($request['session']['account-musicfestivals-registration-return-url']) ) {
                header("Location: {$request['session']['account-musicfestivals-registration-return-url']}");
                unset($request['session']['account-musicfestivals-registration-return-url']);
                return array('stat'=>'exit');
            }

            header("Location: {$request['ssl_domain_base_url']}/account/musicfestivalregistrations");
            return array('stat'=>'exit');
        }
    }
    elseif( isset($_POST['f-delete']) && $_POST['f-delete'] == 'Remove' && isset($registration) ) {
        //
        // Check if paid registration
        //
        if( $registration['status'] >= 10 ) {
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
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.329', 'msg'=>'Unable to remove registration', 'err'=>$rc['err']));
                    }
                } 
                //
                // Item doesn't exist in the cart, remove the registration
                //
                else {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
                    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration['registration_id'], $registration['uuid'], 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.331', 'msg'=>'Unable to remove registration', 'err'=>$rc['err']));
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
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
                $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration['registration_id'], $registration['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.331', 'msg'=>'Unable to remove registration', 'err'=>$rc['err']));
                }
            }
           
            header("Location: {$request['ssl_domain_base_url']}/account/musicfestivalregistrations");
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
                $fields[$fid]['ftype'] = 'text';
            } elseif( $field['ftype'] == 'select' 
                && isset($field['id']) && $field['id'] == 'accompanist_customer_id'
                && $field['value'] == 0
                ) {
                $fields[$fid]['value'] = 'No Accompanist';
                $fields[$fid]['ftype'] = 'text';
            } elseif( $field['ftype'] == 'select' 
                && isset($field['id']) && $field['id'] == 'member_id'
                ) {
                $fields[$fid]['ftype'] = 'text';
                $fields[$fid]['value'] = $selected_member['name'];
            } elseif( $field['ftype'] == 'minsec' 
                ) {
//                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) 
//                    && isset($selected_member['open']) 
//                    && $selected_member['open'] == 'yes'
//                $fields[$fid]['ftype'] = 'text';
//                $fields[$fid]['value'] = sprintf("%d:%02d", intval($field['value']/60),$field['value']%60);
            } elseif( $field['ftype'] == 'select' ) {
                $fields[$fid]['ftype'] = 'text';
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
            }
            if( isset($field['id']) && $field['id'] == 'participation' ) {
                $fields[$fid]['ftype'] = 'text';
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
            if( isset($field['label']) && preg_match("/Competitor /", $field['label']) 
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
                    . "\nLevel: " . $competitor['study_level']
                    . "\nInstrument</b>: " . $competitor['instrument']
                    . (isset($competitor['notes']) && $competitor['notes'] != '' ? "\nNotes: " . $competitor['notes'] : '')
                    . "";
                $fields[$fid]['ftype'] = 'textarea';
            }
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) 
                && preg_match("/(title|composer|movements|perf_time|video_url|music_orgfilename|backtrack)/", $fid)
                && isset($selected_member['open']) 
                && $selected_member['open'] == 'yes'
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
            }
            elseif( preg_match("/(title|composer|movements|perf_time|video_url|music_orgfilename|backtrack)/", $fid)
                && $festival['edit'] == 'yes' 
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
            } 
            elseif( in_array($fid, ['video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8'])
                && $festival['upload'] == 'yes' 
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
            } 
            elseif( preg_match("/music_orgfilename/", $fid) 
                && $festival['upload'] == 'yes' 
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
            } 
            elseif( preg_match("/backtrack/", $fid) 
                && $festival['upload'] == 'yes' 
                ) {
                $fields[$fid]['editable'] = 'yes';
                $editable = 'yes';
            } 
            elseif( $field['ftype'] == 'minsec' ) {
                $fields[$fid]['ftype'] = 'text';
                $fields[$fid]['editable'] = 'no';
                $fields[$fid]['required'] = 'no';
                $fields[$fid]['value'] = sprintf("%d:%02d", intval($field['value']/60),$field['value']%60);
            }
            else {
                $fields[$fid]['required'] = 'no';
                $fields[$fid]['editable'] = 'no';
            }
        }
        if( $editable == 'yes' && $display == 'view' ) {
            $fields['action']['value'] = 'viewupdate';
        } elseif( $editable == 'yes' ) {
            $fields['action']['value'] = 'update';
        }
        $blocks[] = array(
            'type' => 'form',
            'title' => 'Registration',
            'class' => 'limit-width limit-width-80',
            'problem-list' => $form_errors,
            'cancel-label' => $editable == 'yes' ? 'Cancel' : 'Back',
            'submit-label' => 'Save',
            'submit-hide' => $editable == 'no' ? 'yes' : 'no',
            'fields' => $fields,
            'js' => $js,
            );
        if( $registration['timeslot_id'] > 0 ) {
            //
            // Get the timeslot->division->section flags to know if comments have been released
            //
            $num_adjudicators = 1;
            $strsql = "SELECT sections.id, "
                . "sections.flags, "
                . "divisions.flags AS division_flags, "
                . "sections.adjudicator1_id "
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
            if( $registration['comments'] != '' 
                && ((isset($rc['schedule']['flags']) && ($rc['schedule']['flags']&0x02) == 0x02)
                || (isset($rc['schedule']['division_flags']) && ($rc['schedule']['division_flags']&0x02) == 0x02))
                ) {
                $blocks[] = array(
                    'type' => 'html',
                    'class' => 'aligncenter',
                    'html' => "<div class='block-text aligncenter'><div class='wrap'><div class='content'>"
                        . "<form action='' target='_blank' method='POST'>"
                        . "<input type='hidden' name='f-registration_id' value='{$registration['registration_id']}' />"
                        . "<input type='hidden' name='action' value='comments' />"
                        . "<input class='button' type='submit' name='submit' value='Download Adjudicators Comments'>"
                        . "</form>"
                        . "<br/>"
                        . "</div></div></div>",
                    );
            }
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
            . "classes.code AS class_code, "
            . "sections.name AS section_name, "
            . "categories.name AS category_name, "
            . "classes.name AS class_name, "
            . "CONCAT_WS(' - ', classes.code, classes.name) AS codename, "
            . "IFNULL(TIME_FORMAT(timeslots.slot_time, '%l:%i %p'), '') AS timeslot_time, "
            . "IFNULL(DATE_FORMAT(divisions.division_date, '%b %D, %Y'), '') AS timeslot_date, "
            . "IFNULL(divisions.address, '') AS timeslot_address, "
            . "IFNULL(ssections.flags, 0) AS timeslot_flags, "
            . "IFNULL(invoices.status, 0) AS invoice_status "
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
            . "LEFT JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
                . "divisions.ssection_id = ssections.id "
                . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
                . "registrations.invoice_id = invoices.id "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") ";
//            . "WHERE ("
//                . "registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
//                . "OR registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
//                . ") "
        if( $customer_type == 20 ) {
            $strsql .= "WHERE (registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . " OR ("
                    . "registrations.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                    . "AND (registrations.flags&0x01) = 0x01 "
                    . ") "
                . " OR ("
                    . "registrations.accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                    . "AND (registrations.flags&0x02) = 0x02 "
                    . ") "
                . ") ";
        } else {
            $strsql .= "WHERE registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' ";
        }
        $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'status', 'invoice_status', 'invoice_id', 
                    'billing_customer_id', 'teacher_customer_id', 'accompanist_customer_id', 'member_id', 'display_name', 
                    'class_code', 'class_name', 'section_name', 'category_name', 'codename', 
                    'fee', 'participation', 
                    'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
                    'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8', 
                    'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8', 
                    'timeslot_time', 'timeslot_date', 'timeslot_address', 'timeslot_flags',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.300', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
        $cart_registrations = array();
        $etransfer_registrations = array();
        $paid_registrations = array();
        $cancelled_registrations = array();
        $parent_registrations = array();
        foreach($registrations as $rid => $reg) {
            if( ($festival['flags']&0x0100) == 0x0100 ) {
                $reg['codename'] = $reg['class_code'] . ' - ' . $reg['section_name'] . ' - ' . $reg['category_name'] . ' - ' . $reg['class_name'];
            }
            $reg['titles'] = '';
            for($i = 1; $i <= 8; $i++) {
                if( isset($reg["title{$i}"]) && $reg["title{$i}"] != '' ) {
                    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x040000) ) {
                        $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $reg, $i);
                        if( isset($rc['title']) ) {
                            $reg["title{$i}"] = $rc['title'];
                        }
                    }
                    $reg['titles'] .= ($reg['titles'] != '' ? '<br/>' : '') . "{$i}. {$reg["title{$i}"]}";
                }
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
                $parent_registrations[] = $reg;
            } elseif( $reg['invoice_status'] == 10 ) {
                $cart_registrations[] = $reg;
            } elseif( $reg['invoice_status'] == 42 ) {
                $etransfer_registrations[] = $reg;
            } elseif( $reg['invoice_status'] == 50 ) {
                $paid_registrations[] = $reg;
            } elseif( $reg['status'] == 60 ) {
                $cancelled_registrations[] = $reg;
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
    
        if( $form_errors != '' ) { 
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => $form_errors,
                );
        }
        if( ($festival['flags']&0x01) == 0x01 && ($festival['live'] != 'no' || $festival['virtual'] != 'no') ) {
            if( count($cart_registrations) > 0 ) {
                $add_button = "<a class='button' href='{$request['ssl_domain_base_url']}/account/musicfestivalregistrations?add=yes'>Add</a>";
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
                    'class' => 'musicfestival-registrations limit-width limit-width-80 fold-at-50',
                    'headers' => 'yes',
                    'columns' => array(
                        array('label' => 'Competitor', 'field' => 'display_name', 'class' => 'alignleft'),
                        array('label' => 'Class', 'fold-label'=>'Class', 'field' => 'codename', 'class' => 'alignleft'),
                        array('label' => 'Title(s)', 'fold-label'=>'Title', 'field' => 'titles', 'class' => 'alignleft'),
                        array('label' => 'Fee', 'fold-label'=>'Fee', 'field' => 'fee', 'class' => 'alignright fold-alignleft'),
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
                    'class' => 'limit-width limit-width-80', 'title' => $festival['name'] . ' Registrations',
                    'content' => 'No pending registrations',
                    );
            }
            $buttons = array(
                'type' => 'buttons',
                'class' => 'limit-width limit-width-80 aligncenter',
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
//        } elseif( ($festival['flags']&0x09) == 0x09 ) {
//            error_log(print_r($festival,true));
        } else {
            $blocks[] = array(
                'type' => 'text',
                'class' => 'limit-width limit-width-80',
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
                'class' => 'musicfestival-registrations limit-width limit-width-80 fold-at-50',
                'headers' => 'yes',
                'columns' => array(
                    array('label' => 'Competitor', 'field' => 'display_name', 'class' => 'alignleft'),
                    array('label' => 'Class', 'fold-label'=>'Class', 'field' => 'codename', 'class' => 'alignleft'),
                    array('label' => 'Title(s)', 'fold-label'=>'Title', 'field' => 'titles', 'class' => 'alignleft'),
                    array('label' => 'Fee', 'fold-label'=>'Fee', 'field' => 'fee', 'class' => 'alignright fold-alignleft'),
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
            // FIXME: Add button to download PDF list of registrations
            //
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
                if( ($registration['timeslot_flags']&0x01) == 0x01 
                    && $registration['timeslot_time'] != ''
                    && $registration['timeslot_date'] != ''
                    ) {
                    if( $registration['participation'] == 1 ) {
                        $paid_registrations[$rid]['scheduled'] = 'Virtual';
                    } else {
                        $paid_registrations[$rid]['scheduled'] = $registration['timeslot_date'] . ' - ' . $registration['timeslot_time'] . '<br/>' . $registration['timeslot_address'];
                    }
                }
            }
            $blocks[] = array(
                'type' => 'table',
                'title' => $festival['name'] . ' Paid Registrations',
                'class' => 'musicfestival-registrations limit-width limit-width-80 fold-at-50',
                'headers' => 'yes',
                'columns' => array(
                    array('label' => 'Competitor', 'field' => 'display_name', 'class' => 'alignleft'),
                    array('label' => 'Class', 'fold-label'=>'Class', 'field' => 'codename', 'class' => 'alignleft'),
                    array('label' => 'Title(s)', 'fold-label'=>'Title', 'field' => 'titles', 'class' => 'alignleft'),
                    array('label' => 'Scheduled', 'fold-label'=>'Scheduled', 'field' => 'scheduled', 'class' => 'alignleft'),
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
                    'class' => 'limit-width limit-width-80 aligncenter',
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
                if( ($registration['timeslot_flags']&0x01) == 0x01 
                    && $registration['timeslot_time'] != ''
                    && $registration['timeslot_date'] != ''
                    ) {
                    if( $registration['participation'] == 1 ) {
                        $parent_registrations[$rid]['scheduled'] = 'Virtual';
                    } else {
                        $parent_registrations[$rid]['scheduled'] = $registration['timeslot_date'] . ' - ' . $registration['timeslot_time'] . '<br/>' . $registration['timeslot_address'];
                    }
                }
            }
            $blocks[] = array(
                'type' => 'table',
                'title' => $festival['name'] . ' Parent Registered',
                'class' => 'musicfestival-registrations limit-width limit-width-80 fold-at-50',
                'headers' => 'yes',
                'columns' => array(
                    array('label' => 'Competitor', 'field' => 'display_name', 'class' => 'alignleft'),
                    array('label' => 'Class', 'fold-label'=>'Class', 'field' => 'codename', 'class' => 'alignleft'),
                    array('label' => 'Title(s)', 'fold-label'=>'Title', 'field' => 'titles', 'class' => 'alignleft'),
                    array('label' => 'Scheduled', 'fold-label'=>'Scheduled', 'field' => 'scheduled', 'class' => 'alignleft'),
//                    array('label' => 'Fee', 'fold-label'=>'Fee', 'field' => 'fee', 'class' => 'alignright fold-alignleft'),
//                    array('label' => '', 'field' => 'viewbutton', 'class' => 'buttons alignright'),
                    ),
                'rows' => $parent_registrations,
//                'footer' => array(
//                    array('value' => '<b>Total</b>', 'colspan' => 3, 'class' => 'alignright'),
//                    array('value' => '$' . number_format($total, 2), 'colspan'=>2, 'class' => 'alignright'),
//                    ),
                );
                $blocks[] = array(
                    'type' => 'buttons',
                    'class' => 'limit-width limit-width-80 aligncenter',
                    'list' => array(array(
                        'text' => 'Download Registrations PDF',
                        'target' => '_blank',
                        'url' => "/account/musicfestivalregistrations?pdf=yes",
                        )),
                    );
        }
        if( count($cancelled_registrations) > 0 ) {
            $blocks[] = array(
                'type' => 'table',
                'title' => $festival['name'] . ' Cancelled Registrations',
                'class' => 'musicfestival-registrations limit-width limit-width-80',
                'headers' => 'yes',
                'columns' => array(
                    array('label' => 'Competitor', 'field' => 'display_name', 'class' => 'alignleft'),
                    array('label' => 'Class', 'fold-label'=>'Class', 'field' => 'codename', 'class' => 'alignleft'),
                    array('label' => 'Title(s)', 'fold-label'=>'Title', 'field' => 'titles', 'class' => 'alignleft'),
                    ),
                'rows' => $cancelled_registrations,
                );
        }

        if( ($festival['flags']&0x01) == 0x01 && ($festival['live'] != 'no' || $festival['virtual'] != 'no') 
            && isset($customer_switch_type_block)
            ) {
            $blocks[] = $customer_switch_type_block;
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
