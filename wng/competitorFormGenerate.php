<?php
//
// Description
// -----------
// This will generate the form for festival competitors
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_competitorFormGenerate(&$ciniki, $tnid, &$request, $args) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.1356', 'msg'=>"I'm sorry, the page you requested does not exist."));
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
    // Make sure a festival was specified
    //
    if( !isset($args['festival']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1357', 'msg'=>"No festival specified"));
    }
    $festival = $args['festival'];

    //
    // Make sure competitor type is passed
    //
    if( !isset($args['ctype']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1358', 'msg'=>"No competitor type specified"));
    }
    $ctype = $args['ctype'];

    //
    // Make sure customer type is passed
    //
    if( !isset($args['customer_type']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1359', 'msg'=>"No customer type specified"));
    }
    $customer_type = $args['customer_type'];

    if( isset($args['competitor']) ) {
        $competitor = $args['competitor'];
    }

    //
    // Build the form fields
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
        if( isset($festival['competitor-group-instrument']) 
            && in_array($festival['competitor-group-instrument'], ['required', 'optional']) 
            ) {
            $fields['instrument'] = array(
                'id' => 'instrument',
                'label' => 'Instrument',
                'ftype' => 'text',
                'size' => 'small',
                'required' => ($festival['competitor-group-instrument'] == 'required' ? 'yes' : 'no'),
                'class' => '',
                'value' => (isset($_POST['f-instrument']) ? trim($_POST['f-instrument']) : (isset($competitor['instrument']) ? $competitor['instrument'] :'')),
                );
            if( isset($festival['competitor-group-age']) && $festival['competitor-group-age'] == 'hidden' ) {
                $fields['name']['size'] = 'medium';
            }
        }
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
        if( !isset($festival['competitor-group-age']) || in_array($festival['competitor-group-age'], ['required', 'optional']) ) {
            $fields['age'] = array(
                'id' => 'age',
                'label' => 'Age' . (isset($festival['age-restriction-msg']) ? ' ' . $festival['age-restriction-msg'] : ''),
                'ftype' => 'text',
                'required' => (!isset($festival['competitor-group-age']) || $festival['competitor-group-age'] == 'required' ? 'yes' : 'no'),
                'size' => 'tiny',
                'class' => '',
                'value' => (isset($_POST['f-age']) ? trim($_POST['f-age']) : (isset($competitor['age']) ? $competitor['age'] :'')),
                );
            if( isset($festival['competitor-group-age-label']) && $festival['competitor-group-age-label'] != '' ) {
                $fields['age']['label'] = $festival['competitor-group-age-label'];
            }
            if( isset($festival['competitor-group-instrument']) && in_array($festival['competitor-group-instrument'], ['required', 'optional']) ) {
                $fields['name']['size'] = 'medium';
                $fields['age']['size'] = 'tiny';
            }
        }
    } else {
        $fields['first'] = array(
            'id' => 'first',
            'label' => 'First Name',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'small',
            'class' => '',
            'value' => (isset($_POST['f-first']) ? trim($_POST['f-first']) : (isset($competitor['first']) ? $competitor['first'] : '')),
            );
        $fields['last'] = array(
            'id' => 'last',
            'label' => 'Last Name',
            'ftype' => 'text',
            'required' => 'yes',
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
        if( !isset($festival['competitor-individual-age']) || in_array($festival['competitor-individual-age'], ['required', 'optional']) ) {
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
        if( isset($festival['competitor-individual-instrument']) 
            && in_array($festival['competitor-individual-instrument'], ['required', 'optional']) 
            ) {
            $fields['instrument'] = array(
                'id' => 'instrument',
                'label' => 'Instrument',
                'ftype' => 'text',
                'size' => 'small',
                'required' => ($festival['competitor-individual-instrument'] == 'required' ? 'yes' : 'no'),
                'class' => '',
                'value' => (isset($_POST['f-instrument']) ? trim($_POST['f-instrument']) : (isset($competitor['instrument']) ? $competitor['instrument'] :'')),
                );
        }
        if( isset($festival['competitor-individual-study-level']) && $festival['competitor-individual-study-level'] != 'hidden' ) {
            $fields['study_level'] = array(
                'id' => 'study_level',
                'label' => 'Current Level of Study/Method book',
                'ftype' => 'text',
                'size' => 'medium',
                'required' => ($festival['competitor-individual-study-level'] == 'required' ? 'yes' : 'no'),
                'value' => (isset($_POST['f-study_level']) ? trim($_POST['f-study_level']) : (isset($competitor['study_level']) ? $competitor['study_level'] :'')),
                );
            if( isset($festival['competitor-individual-study-level-label']) 
                && $festival['competitor-individual-study-level-label'] != ''
                ) {
                $fields['study_level']['label'] = $festival['competitor-individual-study-level-label'];
            }
        }
        if( isset($festival['competitor-individual-last-exam']) && $festival['competitor-individual-last-exam'] != 'hidden' ) {
            $fields['last_exam'] = array(
                'id' => 'last_exam',
                'label' => 'Last Exam Level',
                'ftype' => 'text',
                'size' => 'medium',
                'required' => ($festival['competitor-individual-last-exam'] == 'required' ? 'yes' : 'no'),
                'value' => (isset($_POST['f-last_exam']) ? trim($_POST['f-last_exam']) : (isset($competitor['last_exam']) ? $competitor['last_exam'] :'')),
                );
            if( isset($festival['competitor-individual-last-exam-label']) 
                && $festival['competitor-individual-last-exam-label'] != ''
                ) {
                $fields['last_exam']['label'] = $festival['competitor-individual-last-exam-label'];
            }
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
    if( $ctype == 50 && isset($festival['competitor-group-phone-cell-label']) && $festival['competitor-group-phone-cell-label'] != '' ) {
        $fields['phone_cell']['label'] = $festival['competitor-group-phone-cell-label'];
    } elseif( isset($festival['competitor-individual-phone-cell-label']) && $festival['competitor-individual-phone-cell-label'] != '' ) {
        $fields['phone_cell']['label'] = $festival['competitor-individual-phone-cell-label'];
    }
    if( ($ctype == 50 && (!isset($festival['competitor-group-phone-home']) || $festival['competitor-group-phone-home'] != 'hidden'))
        || ($ctype != 50 && (!isset($festival['competitor-individual-phone-home']) || $festival['competitor-individual-phone-home'] != 'hidden'))
        ) {
        $fields['phone_home'] = array(
            'id' => 'phone_home',
            'label' => 'Home Phone',
            'ftype' => 'text',
            'size' => 'small',
            'class' => '',
            'value' => (isset($_POST['f-phone_home']) ? trim($_POST['f-phone_home']) : (isset($competitor['phone_home']) ? $competitor['phone_home'] :'')),
            );
        if( $ctype == 50 && isset($festival['competitor-group-phone-home-label']) && $festival['competitor-group-phone-home-label'] != '' ) {
            $fields['phone_home']['label'] = $festival['competitor-group-phone-home-label'];
        } elseif( isset($festival['competitor-individual-phone-home-label']) && $festival['competitor-individual-phone-home-label'] != '' ) {
            $fields['phone_home']['label'] = $festival['competitor-individual-phone-home-label'];
        }
    }
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
    if( ($ctype == 50 
        && isset($festival['competitor-group-email-confirm']) && $festival['competitor-group-email-confirm'] == 'yes' 
        )
        || ($ctype != 50 
        && isset($festival['competitor-individual-email-confirm']) && $festival['competitor-individual-email-confirm'] == 'yes'
        )) {
        $fields['email_confirm'] = array(
            'id' => 'email_confirm',
            'label' => 'Confirm Email',
            'ftype' => 'email',
            'size' => 'small-medium',
            'required' => 'yes',
            'class' => '',
            'value' => (isset($_POST['f-email_confirm']) ? trim($_POST['f-email_confirm']) : (isset($competitor['email']) ? $competitor['email'] :'')),
            );
    } 
    if( $ctype == 50 
        && isset($festival['competitor-group-etransfer-email']) 
        && $festival['competitor-group-etransfer-email'] != 'hidden'
        ) {
        $fields['etransfer_email'] = array(
            'id' => 'etransfer_email',
            'label' => 'Awards etransfer Email',
            'ftype' => 'email',
            'size' => 'small-medium',
            'required' => $festival['competitor-group-etransfer-email'] == 'required' ? 'yes' : 'no',
            'class' => '',
            'value' => (isset($_POST['f-etransfer_email']) ? trim($_POST['f-etransfer_email']) : (isset($competitor['etransfer_email']) ? $competitor['etransfer_email'] :'')),
            );
        if( isset($festival['competitor-group-etransfer-email-label']) 
            && $festival['competitor-group-etransfer-email-label'] != ''
            ) {
            $fields['etransfer_email']['label'] = $festival['competitor-group-etransfer-email-label'];
        }
        if( isset($festival['competitor-group-etransfer-email-confirm']) 
            && $festival['competitor-group-etransfer-email-confirm'] == 'yes' 
            ) {
            $fields['etransfer_email_confirm'] = array(
                'id' => 'etransfer_email_confirm',
                'label' => 'Confirm Awards etransfer Email',
                'ftype' => 'text',
                'size' => 'small-medium',
                'required' => 'yes',
                'class' => '',
                'value' => (isset($_POST['f-etransfer_email_confirm']) ? trim($_POST['f-etransfer_email_confirm']) : (isset($competitor['etransfer_email']) ? $competitor['etransfer_email'] :'')),
                );
        }
    } 
    elseif( $ctype != 50
        && isset($festival['competitor-individual-etransfer-email']) 
        && $festival['competitor-individual-etransfer-email'] != 'hidden'
        ) {
        $fields['etransfer_email'] = array(
            'id' => 'etransfer_email',
            'required' => $festival['competitor-individual-etransfer-email'] == 'required' ? 'yes' : 'no',
            'label' => 'Awards etransfer Email',
            'ftype' => 'email',
            'size' => 'small-medium',
            'required' => $festival['competitor-individual-etransfer-email'] == 'required' ? 'yes' : 'no',
            'class' => '',
            'value' => (isset($_POST['f-etransfer_email']) ? trim($_POST['f-etransfer_email']) : (isset($competitor['etransfer_email']) ? $competitor['etransfer_email'] :'')),
            );
        if( isset($festival['competitor-individual-etransfer-email-label']) 
            && $festival['competitor-individual-etransfer-email-label'] != ''
            ) {
            $fields['etransfer_email']['label'] = $festival['competitor-individual-etransfer-email-label'];
        }
        if( isset($festival['competitor-individual-etransfer-email-confirm']) 
            && $festival['competitor-individual-etransfer-email-confirm'] == 'yes' 
            ) {
            $fields['etransfer_email_confirm'] = array(
                'id' => 'etransfer_email_confirm',
                'label' => 'Confirm Awards etransfer Email',
                'ftype' => 'email',
                'size' => 'small-medium',
                'required' => 'yes',
                'class' => '',
                'value' => (isset($_POST['f-etransfer_email_confirm']) ? trim($_POST['f-etransfer_email_confirm']) : (isset($competitor['etransfer_email']) ? $competitor['etransfer_email'] :'')),
                );
        }
    }
    // 
    // Check if special formatting when only etransfer confirm
    //
    if( !isset($fields['email_confirm']) && isset($fields['etransfer_email_confirm']) ) {
        $fields['email']['size'] = 'large';
    } elseif( isset($fields['email_confirm']) && !isset($fields['etransfer_email_confirm']) && isset($fields['etransfer_emails']) ) {
        $fields['etransfer_email']['size'] = 'large';
    }
    if( ($ctype != 50
            && (!isset($festival['competitor-individual-notes-enable']) 
            || $festival['competitor-individual-notes-enable'] == 'yes')
        ) || (
            $ctype == 50 
            && (!isset($festival['competitor-group-notes-enable']) 
            || $festival['competitor-group-notes-enable'] == 'yes')
        ) ) {
        $fields['comp_notes'] = array(
            'id' => 'comp_notes',
            'label' => "{$festival['competitor-label-singular']} Notes",
            'ftype' => 'textarea',
            'size' => 'tiny',
            'class' => '',
            'value' => (isset($_POST['f-comp_notes']) ? trim($_POST['f-comp_notes']) : (isset($competitor['notes']) ? $competitor['notes'] :'')),
            );
    }
    if( isset($festival['waiver-general-msg']) && $festival['waiver-general-msg'] != '' ) {
        $fields['termstitle'] = array(
            'id' => "termstitle",
            'label' => $festival['waiver-general-title'],
            'ftype' => 'content',
            'required' => 'yes',
            'size' => 'large',
            'value' => '',
            );
        $fields['terms'] = array(
            'id' => "terms",
            'label' => $festival['waiver-general-msg'],
            'ftype' => 'checkbox',
            'size' => 'large',
            'required' => 'yes',
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
    if( isset($festival['waiver-second-msg']) && $festival['waiver-second-msg'] != '' ) {
        $fields['secondwaivertitle'] = array(
            'id' => "secondwaivertitle",
            'label' => isset($festival['waiver-second-title']) ? $festival['waiver-second-title'] : '',
            'ftype' => 'content',
            'required' => 'yes',
            'size' => 'large',
            'value' => '',
            );
        $fields['secondwaiver'] = array(
            'id' => "secondwaiver",
            'label' => $festival['waiver-second-msg'],
            'ftype' => 'checkbox',
            'size' => 'large',
            'required' => 'yes',
            'value' => (isset($competitor['flags']) && ($competitor['flags']&0x20) == 0x20 ? 'on' : ''),
            );
        if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
            if( isset($_POST['f-secondwaiver']) && $_POST['f-secondwaiver'] == 'on' ) {
                $fields['secondwaiver']['value'] = 'on';
            } else {
                $fields['secondwaiver']['value'] = '';
            }
        }
    }
    if( isset($festival['waiver-third-msg']) && $festival['waiver-third-msg'] != '' ) {
        $fields['thirdwaivertitle'] = array(
            'id' => "thirdwaivertitle",
            'label' => isset($festival['waiver-third-title']) ? $festival['waiver-third-title'] : '',
            'ftype' => 'content',
            'required' => 'yes',
            'size' => 'large',
            'value' => '',
            );
        $fields['thirdwaiver'] = array(
            'id' => "thirdwaiver",
            'label' => $festival['waiver-third-msg'],
            'ftype' => 'checkbox',
            'size' => 'large',
            'required' => 'yes',
            'value' => (isset($competitor['flags']) && ($competitor['flags']&0x20) == 0x20 ? 'on' : ''),
            );
        if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
            if( isset($_POST['f-thirdwaiver']) && $_POST['f-thirdwaiver'] == 'on' ) {
                $fields['thirdwaiver']['value'] = 'on';
            } else {
                $fields['thirdwaiver']['value'] = '';
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
    }

    return array('stat'=>'ok', 'fields'=>$fields);
}
?>
