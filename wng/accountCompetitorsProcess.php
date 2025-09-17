<?php
//
// Description
// -----------
// This function will check for competitors in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountCompetitorsProcess(&$ciniki, $tnid, &$request, $args) {

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestival/competitors';
    $display = 'list';

    //
    // Check for a cancel
    //
    if( isset($_POST['cancel']) && $_POST['cancel'] == 'Cancel' ) {
        if( isset($request['session']['account-musicfestivals-competitor-form-return']) ) {
            header("Location: {$request['session']['account-musicfestivals-competitor-form-return']}");
            exit;
        }
        header("Location: {$base_url}");
        exit;
    }

    //
    // Check for a request to add competitor from registration form
    //
    if( isset($_POST['f-action']) && $_POST['f-action'] == 'addcompetitor' ) {
        $request['session']['account-musicfestivals-registration-saved'] = $_POST;
        $return_url = $request['ssl_domain_base_url'] . '/account/musicfestival/registrations';
        $request['session']['account-musicfestivals-competitor-form-return'] = $return_url;
    }
    elseif( isset($_POST['f-action']) && $_POST['f-action'] == 'editcompetitor' ) {
        $request['session']['account-musicfestivals-registration-saved'] = $_POST;
        $return_url = $request['ssl_domain_base_url'] . '/account/musicfestival/registrations';
        $request['session']['account-musicfestivals-competitor-form-return'] = $return_url;
    }

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.424', 'msg'=>'', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Check for any sections that have different end date
    //
    if( ($festival['flags']&0x09) == 0x09 ) {
        $strsql = "SELECT sections.id AS section_id, "
            . "sections.name AS section_name, "
            . "sections.live_end_dt, "
            . "sections.virtual_end_dt "
            . "FROM ciniki_musicfestival_sections AS sections "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (sections.flags&0x01) = 0 "
            . "ORDER BY sections.sequence, sections.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'section_id', 
                'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'live_end_dt', 'virtual_end_dt'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.298', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
        }
        $sections = isset($rc['sections']) ? $rc['sections'] : array();

        //
        // Check for sections that are still open
        //
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

    if( !isset($festival['waiver-general-title']) || $festival['waiver-general-title'] == '' ) {
        $festival['waiver-general-title'] = 'Terms and Conditions';
    }

    //
    // Load the customer type, or ask for customer type
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountCustomerTypeProcess');
    $rc = ciniki_musicfestivals_wng_accountCustomerTypeProcess($ciniki, $tnid, $request, array(
        'festival' => $festival,
        'base_url' => $base_url,
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
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
    // Get the list of competitors
    //
    $strsql = "SELECT competitors.id, "
        . "competitors.name, "
        . "competitors.pronoun, "
        . "competitors.parent, "
        . "competitors.age, "
        . "competitors.instrument "
        . "FROM ciniki_musicfestival_competitors AS competitors "
        . "WHERE competitors.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "ORDER BY competitors.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 'fields'=>array('id', 'name', 'pronoun', 'parent', 'age', 'instrument')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.257', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();

    //
    // Keep track of any errors that have occured
    //
    $errors = array();

    //
    // Check if competitor specified and load
    //
    $ctype = 10;
    if( isset($_POST['f-competitor_id']) && $_POST['f-competitor_id'] > 0 ) {
        $competitor_id = $_POST['f-competitor_id'];
        $strsql = "SELECT id AS competitor_id, "
            . "uuid, "
            . "billing_customer_id, "
            . "ctype, "
            . "first, "
            . "last, "
            . "name, "
            . "public_name, "
            . "pronoun, "
            . "flags, "
            . "conductor, "
            . "num_people, "
            . "parent, "
            . "address, "
            . "city, "
            . "province, "
            . "postal, "
            . "country, "
            . "phone_home, "
            . "phone_cell, "
            . "email, "
            . "age, "
            . "study_level, "
            . "instrument, "
            . "etransfer_email, "
            . "notes "
            . "FROM ciniki_musicfestival_competitors "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $competitor_id) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'competitor');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.355', 'msg'=>'Unable to load competitor', 'err'=>$rc['err']));
        }
        if( !isset($rc['competitor']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.356', 'msg'=>'Unable to find requested competitor'));
            $errors[] = array(
                'msg' => 'Unable to find the specified customer',
                );
            $display = 'list';
        } else {
            $competitor = $rc['competitor'];
            $ctype = $rc['competitor']['ctype'];
            $display = 'form';
        }
    }
    elseif( isset($_GET['add']) && $_GET['add'] == 'group' ) {
        $ctype = 50;
    }

    //
    // Setup the fields for the competitor
    //
    $fields = array(
        'competitor_id' => array(
            'id' => 'competitor_id',
            'label' => '',
            'ftype' => 'hidden',
            'value' => (isset($_POST['f-competitor_id']) ? trim($_POST['f-competitor_id']) : (isset($competitor) ? $competitor['id'] : 0)),
            ),
        'ctype' => array(
            'id' => 'ctype',
            'label' => '',
            'ftype' => 'hidden',
            'value' => (isset($_POST['f-ctype']) ? trim($_POST['f-ctype']) : $ctype),
            ),
        'action' => array(
            'id' => 'action',
            'label' => '',
            'ftype' => 'hidden',
            'value' => 'update',
            ),
        );
    if( $ctype == 50 ) {
        $fields['name'] = array(
            'id' => 'name',
            'label' => 'Group/Ensemble Name',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'large',
            'class' => '',
            'value' => (isset($_POST['f-name']) ? trim($_POST['f-name']) : (isset($competitor['name']) ? $competitor['name'] : '')),
            );
        $fields['conductor'] = array(
            'id' => 'conductor',
            'label' => 'Conductor',
            'ftype' => 'text',
            'required' => 'no',
            'size' => 'small',
            'class' => '',
            'value' => (isset($_POST['f-conductor']) ? trim($_POST['f-conductor']) : (isset($competitor['conductor']) ? $competitor['conductor'] : '')),
            );
        $fields['num_people'] = array(
            'id' => 'num_people',
            'label' => 'Number of People',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'tiny',
            'class' => '',
            'value' => (isset($_POST['f-num_people']) ? trim($_POST['f-num_people']) : (isset($competitor['num_people']) ? $competitor['num_people'] : '')),
            );
    } else {
        $fields['first'] = array(
            'id' => 'first',
            'label' => 'First',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'small',
            'class' => '',
            'value' => (isset($_POST['f-first']) ? trim($_POST['f-first']) : (isset($competitor['first']) ? $competitor['first'] : '')),
            );
        $fields['last'] = array(
            'id' => 'last',
            'label' => 'Last',
            'ftype' => 'text',
            'required' => 'yes',
            //'size' => ($customer_type == 30 ? 'large' : 'medium'),
            'size' => 'small',
            'class' => '',
            'value' => (isset($_POST['f-last']) ? trim($_POST['f-last']) : (isset($competitor['last']) ? $competitor['last'] : '')),
            );
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
            $fields['pronoun'] = array(
                'id' => 'pronoun',
                'label' => 'Pronoun',
                'ftype' => 'text',
                'size' => 'tiny',
                'class' => '',
                'value' => (isset($_POST['f-pronoun']) ? trim($_POST['f-pronoun']) : (isset($competitor['pronoun']) ? $competitor['pronoun'] :'')),
                );
        }
    }
    $fields['newline1'] = array(
        'id' => 'newline1',
        'ftype' => 'newline',
        );
    $fields['parent'] = array(
        'id' => 'parent',
        'label' => ($ctype == 50 ? 'Contact Person' : 'Parent'),
        'ftype' => ($customer_type == 30 ? 'hidden' : 'text'),
        'required' => ($customer_type == 30 ? 'no' : 'yes'),
        'size' => 'large',
        'class' => '',
        'value' => (isset($_POST['f-parent']) ? trim($_POST['f-parent']) : (isset($competitor['parent']) ? $competitor['parent'] :'')),
        );
    $fields['address'] = array(
        'id' => 'address',
        'label' => 'Address',
        'ftype' => 'text',
        'required' => 'yes',
        'size' => 'small',
        'class' => '',
        'value' => (isset($_POST['f-address']) ? trim($_POST['f-address']) : (isset($competitor['address']) ? $competitor['address'] :'')),
        );
    $fields['city'] = array(
        'id' => 'city',
        'label' => 'City',
        'ftype' => 'text',
        'required' => 'yes',
        'size' => 'small',
        'class' => '',
        'value' => (isset($_POST['f-city']) ? trim($_POST['f-city']) : (isset($competitor['city']) ? $competitor['city'] :'')),
        );
    $fields['newline2'] = array(
        'id' => 'newline2',
        'ftype' => 'newline',
        );
    $fields['province'] = array(
        'id' => 'province',
        'label' => 'Province',
        'ftype' => 'text',
        'required' => 'yes',
        'size' => 'small',
        'class' => '',
        'value' => (isset($_POST['f-province']) ? trim($_POST['f-province']) : (isset($competitor['province']) ? $competitor['province'] :'')),
        );
    $fields['postal'] = array(
        'id' => 'postal',
        'label' => 'Postal',
        'ftype' => 'text',
        'required' => 'yes',
        'size' => 'small',
        'class' => '',
        'value' => (isset($_POST['f-postal']) ? trim($_POST['f-postal']) : (isset($competitor['postal']) ? $competitor['postal'] :'')),
        );
    $fields['country'] = array(
        'id' => 'country',
        'label' => 'Country',
        'ftype' => 'text',
        'required' => 'yes',
        'size' => 'small',
        'class' => '',
        'value' => (isset($_POST['f-country']) ? trim($_POST['f-country']) : (isset($competitor['country']) ? $competitor['country'] :'Canada')),
        );
    $fields['newline3'] = array(
        'id' => 'newline3',
        'ftype' => 'newline',
        );
    $fields['phone_cell'] = array(
        'id' => 'phone_cell',
        'label' => 'Cell Phone',
        'ftype' => 'text',
        'required' => 'yes',
        'size' => 'small',
        'class' => '',
        'value' => (isset($_POST['f-phone_cell']) ? trim($_POST['f-phone_cell']) : (isset($competitor['phone_cell']) ? $competitor['phone_cell'] :'')),
        );
    $fields['phone_home'] = array(
        'id' => 'phone_home',
        'label' => 'Home Phone',
        'ftype' => 'text',
        'size' => 'small',
        'class' => '',
        'value' => (isset($_POST['f-phone_home']) ? trim($_POST['f-phone_home']) : (isset($competitor['phone_home']) ? $competitor['phone_home'] :'')),
        );
    $fields['newline4'] = array(
        'id' => 'newline4',
        'ftype' => 'newline',
        );
    $fields['email'] = array(
        'id' => 'email',
        'label' => 'Email',
        'ftype' => 'text',
        'required' => 'yes',
        'size' => 'small-medium',
        'class' => '',
        'value' => (isset($_POST['f-email']) ? trim($_POST['f-email']) : (isset($competitor['email']) ? $competitor['email'] :'')),
            );
    if( $ctype == 50 ) {
        if( !isset($festival['competitor-group-age']) || $festival['competitor-group-age'] != 'hidden' ) {
            $fields['age'] = array(
                'id' => 'age',
                'label' => 'Age' . (isset($festival['age-restriction-msg']) ? ' ' . $festival['age-restriction-msg'] : ''),
                'ftype' => 'text',
                'required' => (!isset($festival['competitor-group-age']) || $festival['competitor-group-age'] == 'required' ? 'yes' : 'no'),
                'size' => 'small',
                'class' => '',
                'value' => (isset($_POST['f-age']) ? trim($_POST['f-age']) : (isset($competitor['age']) ? $competitor['age'] :'')),
                );
            if( isset($festival['competitor-group-age-label']) && $festival['competitor-group-age-label'] != '' ) {
                $fields['age']['label'] = $festival['competitor-group-age-label'];
            }
        }
    } else {
        if( !isset($festival['competitor-individual-age']) || $festival['competitor-individual-age'] != 'optional' ) {
            $fields['age'] = array(
                'id' => 'age',
                'label' => 'Age' . (isset($festival['age-restriction-msg']) ? ' ' . $festival['age-restriction-msg'] : ''),
                'ftype' => 'text',
                'required' => (!isset($festival['competitor-individual-age']) || $festival['competitor-individual-age'] == 'required' ? 'yes' : 'no'),
                'size' => 'small',
                'class' => '',
                'value' => (isset($_POST['f-age']) ? trim($_POST['f-age']) : (isset($competitor['age']) ? $competitor['age'] :'')),
                    );
            if( isset($festival['competitor-individual-age-label']) && $festival['competitor-individual-age-label'] != '' ) {
                $fields['age']['label'] = $festival['competitor-individual-age-label'];
            }
        }
    }
    if( $ctype == 50 && isset($festival['competitor-group-instrument']) ) {
        $ins = $festival['competitor-group-instrument'];
    } elseif( isset($festival['competitor-individual-instrument']) ) {
        $ins = $festival['competitor-individual-instrument'];
    } else {
        $ins = 'hidden';
    }
    if( $ins != 'hidden' ) {
        $fields['instrument'] = array(
            'id' => 'instrument',
            'label' => 'Instrument',
            'ftype' => 'text',
            'size' => 'small',
            'required' => ($ins == 'required' ? 'yes' : 'no'),
            'class' => 'hidden',
            'value' => (isset($_POST['f-instrument']) ? trim($_POST['f-instrument']) : (isset($competitor['instrument']) ? $competitor['instrument'] :'')),
            );
    }
    if( $ctype == 50 && isset($festival['competitor-group-study-level']) ) {
        $sl = $festival['competitor-group-study-level'];
    } elseif( isset($festival['competitor-individual-study-level']) ) {
        $sl = $festival['competitor-individual-study-level'];
    } else {
        $sl = 'hidden';
    }
    if( $sl != 'hidden' ) {
        $fields['study_level'] = array(
            'id' => 'study_level',
            'label' => 'Current Level of Study/Method book',
            'ftype' => 'text',
            'size' => 'large',
            'required' => ($sl == 'required' ? 'yes' : 'no'),
            'class' => 'hidden',
            'value' => (isset($_POST['f-study_level']) ? trim($_POST['f-study_level']) : (isset($competitor['study_level']) ? $competitor['study_level'] :'')),
            );
    }
    if( ($ctype != 50
            && isset($festival['competitor-individual-etransfer-email']) 
            && $festival['competitor-individual-etransfer-email'] != 'hidden'
        ) || (
            $ctype == 50 
            && isset($festival['competitor-group-etransfer-email']) 
            && $festival['competitor-group-etransfer-email'] != 'hidden'
        ) ) {
        $fields['etransfer_email'] = array(
            'id' => 'etransfer_email',
            'label' => 'Awards etransfer Email',
            'ftype' => 'email',
            'size' => 'large',
            'required' => 'no',
            'class' => '',
            'value' => (isset($_POST['f-etransfer_email']) ? trim($_POST['f-etransfer_email']) : (isset($competitor['etransfer_email']) ? $competitor['etransfer_email'] :'')),
            );
        if( $ctype != 50
            && isset($festival['competitor-individual-etransfer-email']) 
            && $festival['competitor-individual-etransfer-email'] == 'required'
            ) {
            $fields['etransfer_email']['required'] = 'yes';
        } 
        if( $ctype == 50
            && isset($festival['competitor-group-etransfer-email']) 
            && $festival['competitor-group-etransfer-email'] == 'required'
            ) {
            $fields['etransfer_email']['required'] = 'yes';
        }
    }
    $fields['comp_notes'] = array(
        'id' => 'comp_notes',
        'label' => "{$festival['competitor-label-singular']} Notes",
        'ftype' => 'textarea',
        'size' => 'tiny',
        'class' => '',
        'value' => (isset($_POST['f-comp_notes']) ? trim($_POST['f-comp_notes']) : (isset($competitor['notes']) ? $competitor['notes'] :'')),
        );
    if( isset($festival['waiver-general-msg']) && $festival['waiver-general-msg'] != '' ) {
        $fields['termstitle'] = array(
            'id' => "termstitle",
            'label' => $festival['waiver-general-title'],
            'ftype' => 'content',
            'required' => 'yes',
            'size' => 'large',
//            'class' => 'hidden',
            'value' => '',
            );
        $fields['terms'] = array(
            'id' => "terms",
            'label' => $festival['waiver-general-msg'],
            'ftype' => 'checkbox',
            'size' => 'large',
            'required' => 'yes',
//            'class' => 'hidden',
            'value' => (isset($competitor['flags']) && ($competitor['flags']&0x01) == 0x01 ? 'on' : ''),
            );
        if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
            if( isset($_POST['f-terms']) && $_POST['f-terms'] == 'on' ) {
                $fields['terms']['value'] = 'on';
            } else {
                $fields['terms']['value'] = '';
            }
        }
    }

    //
    // Photo waiver
    //
    if( isset($festival['waiver-photo-status']) && $festival['waiver-photo-status'] == 'on' 
        && isset($festival['waiver-photo-title']) && $festival['waiver-photo-title'] != '' 
        && isset($festival['waiver-photo-msg']) && $festival['waiver-photo-msg'] != '' 
        ) {
        $fields['photowaiver'] = array(
            'id' => "photowaiver",
            'label' => $festival['waiver-photo-title'],
            'ftype' => 'radio',
            'size' => 'large',
            'required' => 'yes',
            'description' => $festival['waiver-photo-msg'],
            'value' => '',
            'option-2' => isset($festival['waiver-photo-option-yes']) && $festival['waiver-photo-option-yes'] != '' ? $festival['waiver-photo-option-yes'] : 'Yes, I Agree',
            'option-3' => isset($festival['waiver-photo-option-no']) && $festival['waiver-photo-option-no'] != '' ? $festival['waiver-photo-option-no'] : "No, Do Not Publish {$festival['competitor-label-singular']} Photos",
            );
        if( isset($competitor['flags']) ) {
            $fields['photowaiver']['value'] = ($competitor['flags']&0x02) == 0x02 ? $fields['photowaiver']['option-2'] : $fields['photowaiver']['option-3'];
            }
        if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
            if( isset($_POST['f-photowaiver']) ) {
                $fields['photowaiver']['value'] = $_POST['f-photowaiver'];
            }
        }
    }

    //
    // Name waiver
    //
    if( isset($festival['waiver-name-status']) && $festival['waiver-name-status'] == 'on' 
        && isset($festival['waiver-name-title']) && $festival['waiver-name-title'] != '' 
        && isset($festival['waiver-name-msg']) && $festival['waiver-name-msg'] != '' 
        ) {
        $fields['namewaiver'] = array(
            'id' => "namewaiver",
            'label' => $festival['waiver-name-title'],
            'ftype' => 'radio',
            'size' => 'large',
            'required' => 'yes',
            'description' => $festival['waiver-name-msg'],
            'value' => '',
            'option-2' => isset($festival['waiver-name-option-yes']) && $festival['waiver-name-option-yes'] != '' ? $festival['waiver-name-option-yes'] : 'Yes, I Agree',
            'option-3' => isset($festival['waiver-name-option-no']) && $festival['waiver-name-option-no'] != '' ? $festival['waiver-name-option-no'] : "No, Do Not Publish {$festival['competitor-label-singular']} Name",
            );
        if( isset($competitor['flags']) ) {
            $fields['namewaiver']['value'] = ($competitor['flags']&0x04) == 0x04 ? $fields['namewaiver']['option-2'] : $fields['namewaiver']['option-3'];
            }
        if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
            if( isset($_POST['f-namewaiver']) ) {
                $fields['namewaiver']['value'] = $_POST['f-namewaiver'];
            }
        }
/*        $fields['namewaivertitle'] = array(
            'id' => "namewaivertitle",
            'label' => $festival['waiver-name-title'],
            'ftype' => 'content',
            'size' => 'large',
            'value' => '',
            );
        $fields['namewaiver'] = array(
            'id' => "namewaiver",
            'label' => $festival['waiver-name-msg'],
            'ftype' => 'checkbox',
            'size' => 'large',
            'value' => (isset($competitor['flags']) && ($competitor['flags']&0x04) == 0x04 ? 'on' : ''),
            );
        if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
            if( isset($_POST['f-namewaiver']) && $_POST['f-namewaiver'] == 'on' ) {
                $fields['namewaiver']['value'] = 'on';
            } else {
                $fields['namewaiver']['value'] = '';
            }
        } */
    }

    //
    // Check if the form is submitted
    //
    if( isset($_POST['f-competitor_id']) && isset($_POST['f-action']) && $_POST['f-action'] == 'update' && count($errors) == 0 ) {
        $competitor_id = $_POST['f-competitor_id'];
        $fields['competitor_id']['value'] = $_POST['f-competitor_id'];
        $display = 'form';
        foreach($fields as $field) {
            if( isset($field['required']) && $field['required'] == 'yes' 
                && $field['value'] == '' 
                && $field['id'] != 'termstitle' 
                && $field['id'] != 'terms' 
                && $field['id'] != 'photowaiver' 
                && $field['id'] != 'namewaiver' 
                ) {
                $errors[] = array(
                    'msg' => 'You must specify the competitor ' . $field['label'] . '.',
                    );
            }
            elseif( isset($field['id']) && $field['id'] == 'email'
                && !preg_match("/^[^@ ]+@[A-Za-z0-9\.\-]+\.[a-zA-Z]+$/", $field['value']) 
                ) {
                $errors[] = array(
                    'msg' => 'Invalid email address format.',
                    );
            }
            elseif( isset($field['id']) && $field['id'] == 'etransfer_email'
                && !preg_match("/^[^@ ]+@[A-Za-z0-9\.\-]+\.[a-zA-Z]+$/", $field['value']) 
                ) {
                $errors[] = array(
                    'msg' => 'Invalid etransfer email address format.',
                    );
            }
        }
        if( isset($festival['waiver-general-msg']) && $festival['waiver-general-msg'] != '' 
            && (!isset($fields['terms']['value']) || $fields['terms']['value'] != 'on') 
            ) {
            $errors[] = array(
                'msg' => "You must accept the {$festival['waiver-general-title']} for the competitor.",
                );
        }
        if( isset($festival['waiver-photo-status']) && $festival['waiver-photo-status'] == 'on' 
            && $fields['photowaiver']['value'] != $fields['photowaiver']['option-2']
            && $fields['photowaiver']['value'] != $fields['photowaiver']['option-3']
            ) {
            $errors[] = array(
                'msg' => "You must select an option for {$festival['waiver-photo-title']} for the competitor.",
                );
        }
        if( isset($festival['waiver-name-status']) && $festival['waiver-name-status'] == 'on' 
            && $fields['namewaiver']['value'] != $fields['namewaiver']['option-2']
            && $fields['namewaiver']['value'] != $fields['namewaiver']['option-3']
            ) {
            $errors[] = array(
                'msg' => "You must select an option for {$festival['waiver-name-title']} for the competitor.",
                );
        }
        //
        // Create name
        //
        if( $ctype == 50 ) {
            $name = $_POST['f-name'];
        } else {
            $name = $_POST['f-first'] . ' ' . $_POST['f-last'];
        }

        //
        // Check for duplicate child
        //
        if( $fields['competitor_id']['value'] == 0 
//            || (isset($_POST['f-name']) && isset($competitor['name']) && $_POST['f-name'] != $competitor['name']) 
            ) {
            //
            // Check for a duplicate name
            //
            $strsql = "SELECT COUNT(*) AS num "
                . "FROM ciniki_musicfestival_competitors AS competitors "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                . "AND name = '" . ciniki_core_dbQuote($ciniki, $name) . "' "
                . "AND parent = '" . ciniki_core_dbQuote($ciniki, $fields['parent']['value']) . "' "
                . "AND billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
            $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.354', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            if( $rc['num'] > 0 ) {
                $errors[] = array(
                    'msg' => "You already have a competitor with this name.",
                    );
            }
        }
        //
        // If no errors add/update the competitor
        //
        if( count($errors) == 0 ) {
            if( $fields['competitor_id']['value'] == 0 ) {
                //
                // Create the competitor
                //
                $competitor = array(
                    'festival_id' => $festival['id'],
                    'billing_customer_id' => $request['session']['customer']['id'],
                    'ctype' => $fields['ctype']['value'],
                    'flags' => 0,
                    'parent' => $fields['parent']['value'],
                    'address' => $fields['address']['value'],
                    'city' => $fields['city']['value'],
                    'province' => $fields['province']['value'],
                    'postal' => $fields['postal']['value'],
                    'country' => $fields['country']['value'],
                    'phone_home' => $fields['phone_home']['value'],
                    'phone_cell' => $fields['phone_cell']['value'],
                    'email' => $fields['email']['value'],
                    'age' => isset($fields['age']['value']) ? $fields['age']['value'] : '',
                    'study_level' => isset($fields['study_level']['value']) ? $fields['study_level']['value'] : '',
                    'instrument' => isset($fields['instrument']['value']) ? $fields['instrument']['value'] : '',
                    'etransfer_email' => isset($fields['etransfer_email']['value']) ? $fields['etransfer_email']['value'] : '',
                    'notes' => $fields['comp_notes']['value'],
                    );
                if( isset($fields['terms']['value']) && $fields['terms']['value'] == 'on' ) {
                    $competitor['flags'] |= 0x01;
                }
                //
                // Default to photo and name flags to set
                // waiver-(photo/name)-status option can be missing, off, internal and they will be set to on by default
                // Only when waiver-*-status set to on will they be visible in form
                //
                if( !isset($fields['photowaiver']['value']) || $fields['photowaiver']['value'] == $fields['photowaiver']['option-2'] ) {
                    $competitor['flags'] |= 0x02;
                }
                if( !isset($fields['namewaiver']['value']) || $fields['namewaiver']['value'] == $fields['namewaiver']['option-2'] ) {
                    $competitor['flags'] |= 0x04;
                }
                if( $fields['ctype']['value'] == 50 ) {
                    $competitor['first'] = '';
                    $competitor['last'] = '';
                    $competitor['name'] = $fields['name']['value'];
                    $competitor['public_name'] = $fields['name']['value'];
                    $competitor['pronoun'] = '';
                    $competitor['conductor'] = $fields['conductor']['value'];
                    $competitor['num_people'] = $fields['num_people']['value'];
                } else {
                    $competitor['first'] = $fields['first']['value'];
                    $competitor['last'] = $fields['last']['value'];
                    $competitor['name'] = $competitor['first'] . ' ' . $competitor['last'];
                    $competitor['public_name'] = $competitor['first'][0] . '. ' . $competitor['last'];
                    $competitor['pronoun'] = (isset($fields['pronoun']['value']) ? $fields['pronoun']['value'] : '');
                    $competitor['conductor'] = '';
                    $competitor['num_people'] = '';
                }

                //
                // Add the competitor
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.competitor', $competitor, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.353', 'msg'=>'Unable to add the competitor', 'err'=>$rc['err']));
                }
                if( isset($request['session']['account-musicfestivals-competitor-form-return']) ) {
                    $request['session']['account-musicfestivals-registration-saved']['new-id'] = $rc['id'];
                    header("Location: {$request['session']['account-musicfestivals-competitor-form-return']}");
                    return array('stat'=>'exit');
                }
                header("Location: {$request['ssl_domain_base_url']}/account/musicfestival/competitors");
                exit;
            } 
            else {
                $update_args = array();
                foreach($fields as $field) {
                    if( $field['ftype'] == 'content' || $field['ftype'] == 'hidden' || $field['ftype'] == 'newline' || $field['id'] == 'terms' ) {
                        continue;
                    }
                    if( isset($field['id']) && $field['id'] == 'comp_notes' ) {
                        if( isset($field['value']) && $field['value'] != $competitor['notes'] ) {
                            $update_args['notes'] = $field['value'];
                        }
                    }
                    elseif( !isset($competitor[$field['id']]) || (isset($field['value']) && $field['value'] != $competitor[$field['id']]) ) {
                        $update_args[$field['id']] = $field['value'];
                    }
                }
                $flags = $competitor['flags'];
                if( isset($fields['photowaiver']['value']) ) {
                    if( ($competitor['flags']&0x02) == 0 && $fields['photowaiver']['value'] == $fields['photowaiver']['option-2'] ) {
                        $flags |= 0x02;
                    } elseif( ($competitor['flags']&0x02) == 0x02 && $fields['photowaiver']['value'] != $fields['photowaiver']['option-2'] ) {
                        $flags = ($competitor['flags']&0xFFFFFFFD);
                    }
                }
                if( isset($fields['namewaiver']['value']) ) {
                    if( ($competitor['flags']&0x04) == 0 && $fields['namewaiver']['value'] == $fields['namewaiver']['option-2'] ) {
                        $flags |= 0x04;
                    } elseif( ($competitor['flags']&0x04) == 0x04 && $fields['namewaiver']['value'] != $fields['namewaiver']['option-2'] ) {
                        $flags = ($competitor['flags']&0xFFFFFFFB);
                    }
                }
                if( $flags != $competitor['flags'] ) {
                    $update_args['flags'] = $flags;
                }
                if( $ctype == 10 && (isset($update_args['first']) || isset($update_args['last'])) ) {
                    $name = (isset($update_args['first']) ? $update_args['first'] : $competitor['first']) 
                        . ' ' . (isset($update_args['last']) ? $update_args['last'] : $competitor['last']);
                    $public_name = (isset($update_args['first']) ? $update_args['first'][0] : $competitor['first'][0]) 
                        . ' ' . (isset($update_args['last']) ? $update_args['last'] : $competitor['last']);
                    if( $name != $competitor['name'] ) {
                        $update_args['name'] = $name;
                    }
                    if( $public_name != $competitor['public_name'] ) {
                        $update_args['public_name'] = $public_name;
                    }
                }
                elseif( $ctype == 50 && isset($update_args['name']) ) {
                    $update_args['public_name'] = $update_args['name'];
                }
                
                //
                // Check if the competitor is part of any submitted/paid registrations and then 
                // nothing can be changed
                //
                $strsql = "SELECT COUNT(*) AS num "
                    . "FROM ciniki_musicfestival_registrations "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND status >= 7 "    // E-transfer pending, applied or paid
                    . "AND ("
                        . "competitor1_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                        . "OR competitor2_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                        . "OR competitor3_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                        . "OR competitor4_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                        . "OR competitor5_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                        . ") "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
                $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.358', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
                }
                $num_items = isset($rc['num']) ? $rc['num'] : '';

                if( $num_items > 0 ) {
                    $blocks[] = array(
                        'type' => 'msg',
                        'class' => 'limit-width limit-width-60',
                        'level' => 'error',
                        'content' => "There " . ($num_items > 1 ? 'are' : 'is') . " {$num_items} registration" . ($num_items > 1 ? 's' : '') . " for {$competitor['name']}. Please contact us with any changes to competitor information.",
                        );
                    $display = 'list';
                }
                //
                // Update the competitor
                //
                else {
                    if( count($update_args) > 0 ) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.competitor', $competitor_id, $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.357', 'msg'=>'Unable to update the competitor', 'err'=>$rc['err']));
                        }

                        //
                        // Update any registration this competitor is a part of
                        //
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'competitorUpdateNames');
                        $rc = ciniki_musicfestivals_competitorUpdateNames($ciniki, $tnid, [
                            'festival_id' => $festival['id'], 
                            'competitor_id' => $competitor_id,
                            ]);
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.328', 'msg'=>'Unable to update registrations', 'err'=>$rc['err']));
                        }
                    }

                    if( isset($request['session']['account-musicfestivals-competitor-form-return']) ) {
                        header("Location: {$request['session']['account-musicfestivals-competitor-form-return']}");
                        exit;
                    }
                    header("Location: {$request['ssl_domain_base_url']}/account/musicfestival/competitors");
                    exit;
                }
            }
        }
    }
    elseif( isset($_POST['f-delete']) && $_POST['f-delete'] == 'Remove' && isset($competitor) ) {
        //
        // Load the number of registrations for the competitor
        //
        $strsql = "SELECT COUNT(*) AS num "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ("
                . "competitor1_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                . "OR competitor2_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                . "OR competitor3_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                . "OR competitor4_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                . "OR competitor5_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                . ") "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.648', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
        }
        $num_items = isset($rc['num']) ? $rc['num'] : '';

        if( $num_items > 0 ) {
            $blocks[] = array(
                'type' => 'msg',
                'class' => 'limit-width limit-width-60',
                'level' => 'error',
                'content' => "There are still {$num_items} registration" . ($num_items > 1 ? 's' : '') . " for {$competitor['name']}, they cannot be removed.",
                );
            $display = 'list';
        } elseif( isset($_POST['submit']) && $_POST['submit'] == "Remove {$festival['competitor-label-singular']}"
            && isset($_POST['f-action']) && $_POST['f-action'] == 'confirmdelete'
            ) {
            $display = 'list';
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.competitor', $competitor['competitor_id'], $competitor['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.359', 'msg'=>'Unable to remove competitor', 'err'=>$rc['err']));
            }
            header("Location: {$request['ssl_domain_base_url']}/account/musicfestival/competitors");
            exit;
        } else {
            $display = 'delete';
        }
    }
    elseif( isset($_GET['add']) && ($_GET['add'] == 'individual' || $_GET['add'] == 'group') ) {
        $competitor_id = 0;
        if( $customer_type == 10 ) {
            $fields['parent']['value'] = $request['session']['customer']['display_name'];
        } elseif( $customer_type == 30 ) {
            if( $ctype == 50 ) {
                $fields['name']['value'] = $request['session']['customer']['display_name'];
            } else {
                $fields['first']['value'] = $request['session']['customer']['first'];
                $fields['last']['value'] = $request['session']['customer']['last'];
            }
        }
        if( $customer_type == 10 || $customer_type == 30 ) {
            $fields['email']['value'] = $request['session']['customer']['email'];
            //
            // Lookup address
            //
            $strsql = "SELECT address1, "
                . "address2, "
                . "city, "
                . "province, "
                . "postal, "
                . "country "
                . "FROM ciniki_customer_addresses "
                . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "AND ciniki_customer_addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY flags DESC "
                . "LIMIT 1 "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'address');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.260', 'msg'=>'Unable to load address', 'err'=>$rc['err']));
            }
            if( isset($rc['address']) ) {
                $address = $rc['address']['address1'];
                if( $rc['address']['address2'] != '' ) {
                    $address .= ($address != '' ? ', ' : '') . $rc['address']['address2'];
                }
                $fields['address']['value'] = $address;
                $fields['city']['value'] = $rc['address']['city'];
                $fields['province']['value'] = $rc['address']['province'];
                $fields['postal']['value'] = $rc['address']['postal'];
                $fields['country']['value'] = $rc['address']['country'];
            }
            //
            // Lookup phones
            //
            $strsql = "SELECT phone_label, "
                . "phone_number "
                . "FROM ciniki_customer_phones "
                . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "AND ciniki_customer_phones.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY flags DESC "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'address');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.360', 'msg'=>'Unable to load address', 'err'=>$rc['err']));
            }
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $phone) {
                    if( $phone['phone_label'] == 'Cell' ) {
                        $fields['phone_cell']['value'] = $phone['phone_number'];
                    }
                    if( $phone['phone_label'] == 'Home' ) {
                        $fields['phone_home']['value'] = $phone['phone_number'];
                    }
                }
            }
        }
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
    // Show the competitor edit/add form
    //
    if( $display == 'form' ) {
        $guidelines = '';
        if( $customer_type == 10 && $ctype == 50 && isset($festival['competitor-group-parent-msg']) ) {
            $guidelines = $festival['competitor-group-parent-msg'];
        } elseif( $customer_type == 20 && $ctype == 50 && isset($festival['competitor-group-teacher-msg']) ) {
            $guidelines = $festival['competitor-group-teacher-msg'];
        } elseif( $customer_type == 30 && $ctype == 50 && isset($festival['competitor-group-adult-msg']) ) {
            $guidelines = $festival['competitor-group-adult-msg'];
        } elseif( $customer_type == 10 && isset($festival['competitor-parent-msg']) ) {
            $guidelines = $festival['competitor-parent-msg'];
        } elseif( $customer_type == 20 && isset($festival['competitor-teacher-msg']) ) {
            $guidelines = $festival['competitor-teacher-msg'];
        } elseif( $customer_type == 30 && isset($festival['competitor-adult-msg']) ) {
            $guidelines = $festival['competitor-adult-msg'];
        }

        $blocks[] = array(
            'type' => 'form',
            'guidelines' => $guidelines,
            'title' => ($competitor_id > 0 ? 'Update' : 'Add') . ($ctype == 50 ? ' Group/Ensemble' : " Individual {$festival['competitor-label-singular']}"),
            'class' => 'limit-width limit-width-60',
            'problem-list' => $form_errors,
            'cancel-label' => 'Cancel',
            'submit-label' => ($competitor_id > 0 ? 'Save' : 'Save'),
            'fields' => $fields,
            );
    }
    //
    // Show the delete form
    //
    elseif( $display == 'delete' ) {
        $blocks[] = array(
            'type' => 'form',
            'title' => "Remove {$festival['competitor-label-singular']}",
            'class' => 'limit-width limit-width-50',
            'cancel-label' => 'Cancel',
            'submit-label' => "Remove {$festival['competitor-label-singular']}",
            'fields' => array(
                'competitor_id' => array(
                    'id' => 'competitor_id',
                    'ftype' => 'hidden',
                    'value' => $competitor['competitor_id'],
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
                    'label' => 'Are you sure you want to remove ' . $competitor['name'] . '?',
                    ),
                ),
            );
    }
    //
    // Show the list of competitors
    //
    else {
        if( $form_errors != '' ) { 
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => $form_errors,
                );
        }
        if( count($competitors) > 0 ) {
            $add_button = '';
            //if( ($festival['flags']&0x01) == 0x01 ) {
            if( ($festival['flags']&0x01) == 0x01 && ($festival['live'] != 'no' || $festival['virtual'] != 'no') ) {
                foreach($competitors as $cid => $competitor) {
                    $competitors[$cid]['editbutton'] = "<form action='{$base_url}' method='POST'>"
                        . "<input type='hidden' name='f-competitor_id' value='{$cid}' />"
                        . "<input type='hidden' name='action' value='edit' />"
                        . "<input class='button' type='submit' name='submit' value='Edit'>"
                        . "<input class='button' type='submit' name='f-delete' value='Remove'>"
                        . "</form>";
                }
//                $add_button = "<a class='button' href='{$request['ssl_domain_base_url']}/account/musicfestival/competitors?add=yes'>Add</a>";
            }
            $columns = array(
                array('label' => 'Name', 'field' => 'name', 'class' => 'alignleft')
                );
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
                $columns[] = array('label' => 'Pronoun', 'field' => 'pronoun', 'class' => 'alignleft');
            }
            if( $customer_type != 30 ) {
                $columns[] = array('label' => 'Parent', 'fold-label'=>'Parent:', 'field' => 'parent', 'class' => 'alignleft');
            }
            $columns[] = array('label' => 'Age', 'fold-label'=>'Age:', 'field' => 'age', 'class' => 'alignleft');
            $columns[] = array('label' => 'Instrument', 'fold-label'=>'Instrument:', 'field' => 'instrument', 'class' => 'alignleft');
            $columns[] = array('label' => $add_button, 'field' => 'editbutton', 'class' => 'buttons alignright');

            $blocks[] = array(
                'type' => 'table',
                'title' => "{$festival['name']} {$festival['competitor-label-plural']}",
                'class' => 'musicfestival-competitors limit-width limit-width-60 fold-at-40',
                'headers' => 'yes',
                'columns' => $columns,
                'rows' => $competitors,
                );

        } elseif( ($festival['flags']&0x01) == 0 ) {
            $blocks[] = array(
                'type' => 'text',
                'class' => 'limit-width limit-width-40',
                'title' => "{$festival['name']} {$festival['competitor-label-plural']}",
                'content' => 'Registrations closed',
                );
        } else {
            $blocks[] = array(
                'type' => 'text',
                'class' => 'limit-width limit-width-40',
                'title' => "{$festival['name']} {$festival['competitor-label-plural']}",
                'content' => 'No competitors',
                );
        }

        if( ($festival['flags']&0x01) == 0x01 && ($festival['live'] == 'yes' || $festival['virtual'] == 'yes') ) {
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'limit-width limit-width-40 aligncenter',
                'list' => array(
                    array(
                        'text' => 'Add Individual',
                        'url' => "/account/musicfestival/competitors?add=individual",
                        ),
                    array(
                        'text' => 'Add Group/Ensemble',
                        'url' => "/account/musicfestival/competitors?add=group",
                        ),
                    ),
                );
            if( isset($customer_switch_type_block) ) {
                $blocks[] = $customer_switch_type_block;
            }
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
