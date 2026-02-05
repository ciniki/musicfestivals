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
function ciniki_musicfestivals_wng_accountScrutinizeRegistrationProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classNameFormat');

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $festival = $args['festival'];
    $base_url = $args['base_url'];
    $maps = $args['maps'];
    $section = $args['section'];
    $category = $args['category'];
    $class = $args['class'];
    $form_errors = '';

    if( (isset($_POST['cancel']) && $_POST['cancel'] == 'Cancel')
        || (isset($_POST['submit']) && $_POST['submit'] == 'Back')
        ) {
        header("Location: {$base_url}");
        return array('stat'=>'exit');
    }

    $fields = ['title', 'composer', 'movements', 'perf_time', 'video_url', 'music_orgfilename', 'backtrack', 'artwork'];

    //
    // Load the registration
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.festival_id, "
        . "registrations.teacher_customer_id, "
        . "registrations.teacher2_customer_id, "
        . "registrations.billing_customer_id, "
        . "registrations.parent_customer_id, "
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
        . "registrations.class_id, ";
    for($i = 1; $i <= 8; $i++) {
        foreach($fields as $field) {
            $strsql .= "registrations.{$field}{$i}, ";
        }
    }
    $strsql .= "registrations.fee, "
        . "registrations.participation, "
        . "registrations.instrument, "
        . "registrations.notes, "
        . "registrations.internal_notes, "
        . "registrations.runsheet_notes "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "WHERE registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.120', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.121', 'msg'=>'Unable to find requested registration'));
    }
    $registration = $rc['registration'];

    //
    // Check registration status
    //
    $editable = 'no';
    if( $registration['status'] >= 10 ) {
        $editable = 'yes';
    }

    //
    // Check for submit
    //
    if( isset($_POST['submit']) && isset($_POST['submit']) == 'Save' 
        && isset($_POST['f-action']) && $_POST['f-action'] == 'update' 
        ) {
        //
        // check for updates 
        //
        $update_args = [];
        if( isset($_POST['f-status']) && $_POST['f-status'] != $registration['status'] ) {
            $update_args['status'] = $_POST['f-status'];
        }
        if( isset($_POST['f-internal_notes']) && $_POST['f-internal_notes'] > $registration['internal_notes'] ) {
            $update_args['internal_notes'] = $_POST['f-internal_notes'];
        }
        for($i = 1; $i <= 8; $i++) {
            foreach($fields as $field) {
                if( $field == 'perf_time' ) {
                    $perf_time = $registration["{$field}{$i}"];
                    if( isset($_POST["f-perf_time{$i}-min"]) || isset($_POST["f-perf_time{$i}-sec"]) ) {
                        if( isset($_POST["f-perf_time{$i}-min"]) ) {
                            $perf_time = $_POST["f-perf_time{$i}-min"]*60;
                        }
                        if( isset($_POST["f-perf_time{$i}-sec"]) ) {
                            $perf_time += $_POST["f-perf_time{$i}-sec"];
                        }
                    }
                    if( $perf_time != $registration["{$field}{$i}"] ) {
                        $update_args["{$field}{$i}"] = $perf_time;
                    }
                }
                elseif( isset($_POST["f-{$field}{$i}"]) && $_POST["f-{$field}{$i}"] != $registration["$field{$i}"] ) {
                    $update_args["{$field}{$i}"] = $_POST["f-{$field}{$i}"];
                }
            }
        }
        if( count($update_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1142', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
            }
        }

        header("Location: {$base_url}");
        return array('stat'=>'exit');
    }
    
    //
    // Load Competitor information
    //


    //
    // Generate form
    //
    $fields = [
        'action' => [
            'id' => 'action',
            'label' => '',
            'ftype' => 'hidden',
            'value' => 'update',
            ],
        'class' => [
            'id' => 'class',
            'label' => 'Class',
            'ftype' => 'content',
            'description' => $class['code'] . ' - ' . $class['name'],
            ],
        ];
    if( isset($registration['instrument']) && $registration['instrument'] != '' ) {
        $fields['instrument'] = [
            'id' => 'instrument',
            'label' => 'Instrument',
            'ftype' => 'content',
            'description' => $registration['instrument'],
            ];
    }

    //
    // Lookup competitor information
    //
    for($i = 1; $i <= 5; $i++) {
        if( $registration["competitor{$i}_id"] > 0 ) {
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
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $registration["competitor{$i}_id"]) . "' "
                . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'competitor');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1143', 'msg'=>'Unable to load competitor', 'err'=>$rc['err']));
            }
            $competitor = isset($rc['competitor']) ? $rc['competitor'] : array();
            $address = $competitor['address']
                . ($competitor['city'] != '' ? ', ' . $competitor['city'] : '')
                . ($competitor['province'] != '' ? ', ' . $competitor['province'] : '')
                . ($competitor['postal'] != '' ? ', ' . $competitor['postal'] : '')
                . "";
            $content = $competitor['name'] . ($competitor['pronoun'] != '' ? ' (' . $competitor['pronoun'] . ')' : '')
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
            $fields["competitor{$i}"] = [
                'id' => "competitor{$i}",
                'label' => $festival['competitor-label-singular'] . ($registration['competitor2_id'] > 0 ? ' #' . $i : ''),
                'ftype' => 'content',
                'description' => $content,
                ];
        }
    }

    for($i = 1; $i <= $class['max_titles']; $i++) {
        //
        // Setup the title prefix
        //
        $prefix = '1st';
        if( $i == 2 ) {
            $prefix = '2nd';
        } elseif( $i == 3 ) {
            $prefix = '3rd';
        } elseif( $i > 3 ) {
            $prefix = $i . 'th';
        }

        if( $editable == 'yes' ) {
            $fields["line-title-{$i}"] = [
                'id' => "line-title-{$i}",
                'ftype' => 'line',
                ];
            $fields["title{$i}"] = [
                'id' => "title{$i}",
                'ftype' => 'text',
                'flex-basis' => '50%',
                'editable' => $editable,
                'size' => 'small',
                'label' => "{$prefix} " . (isset($festival['registration-title-label']) && $festival['registration-title-label'] != '' ? $festival['registration-title-label'] : "Title"),
                'value' => isset($_POST["f-title{$i}"]) ? $_POST["f-title{$i}"] : $registration["title{$i}"],
                ];
            $fields["movements{$i}"] = [
                'id' => "movements{$i}",
                'ftype' => 'text',
                'flex-basis' => '50%',
                'editable' => $editable,
                'size' => 'small',
                'label' => "{$prefix} " . (isset($festival['registration-movements-label']) && $festival['registration-movements-label'] != '' ? $festival['registration-movements-label'] : "Movements/Musical"),
                'value' => isset($_POST["f-movements{$i}"]) ? $_POST["f-movements{$i}"] : $registration["movements{$i}"],
                ];
            $fields["composer{$i}"] = [
                'id' => "composer{$i}",
                'ftype' => 'text',
                'flex-basis' => '50%',
                'editable' => $editable,
                'size' => 'small',
                'label' => "{$prefix} " . (isset($festival['registration-composer-label']) && $festival['registration-composer-label'] != '' ? $festival['registration-composer-label'] : "Composer"),
                'value' => isset($_POST["f-composer{$i}"]) ? $_POST["f-composer{$i}"] : $registration["composer{$i}"],
                ];
            $fields["perf_time{$i}"] = array(
                'id' => "perf_time{$i}",
                'seconds' => (isset($festival['registration-length-format']) && $festival['registration-length-format'] == 'minonly' ? 'no' : 'yes'),
                'ftype' => 'minsec',
                'second-interval' => 5,
                'max-minutes' => 45,
                'flex-basis' => (isset($festival['registration-length-format']) && $festival['registration-length-format'] == 'minonly' ? '5rem' : '17rem'),
                'size' => (isset($festival['registration-length-format']) && $festival['registration-length-format'] == 'minonly' ? 'tiny' : 'small'),
                'label' => (isset($festival['registration-length-label']) && $festival['registration-length-label'] != '' ? $festival['registration-length-label'] : 'Piece Length'),
                'error_label' => "{$prefix} " . (isset($festival['registration-length-label']) && $festival['registration-length-label'] != '' ? $festival['registration-length-label'] : 'Piece Length'),
                'value' => isset($_POST["f-perf_time{$i}"]) ? $_POST["f-perf_time{$i}"] : $registration["perf_time{$i}"],
                );
        } else {
            if( $registration["title{$i}"] != '' ) {
                $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $registration, $i);
                $fields["title{$i}"] = [
                    'id' => "title{$i}",
                    'ftype' => 'content',
                    'label' => 'Title',
                    'flex-basis' => '50%',
                    'description' => $rc['title'],
                    ];
                $min = floor($registration["perf_time{$i}"]/60);
                $sec = ($registration["perf_time{$i}"]%60);
                $fields["perf_time{$i}"] = [
                    'id' => "perf_time{$i}",
                    'ftype' => 'content',
                    'label' => (isset($festival['registration-length-label']) && $festival['registration-length-label'] != '' ? $festival['registration-length-label'] : 'Piece Length'),
                    'flex-basis' => '50%',
                    'description' => "{$min} minute" . ($min > 1 ? 's' : '')
                        . ($sec > 0 ? " {$sec} second" . ($sec > 1 ? 's' : '') : ''),
                    ];
            }
        }
    }

    if( $registration['notes'] != '' ) {
        $fields['notes'] = [
            'id' => 'notes',
            'label' => 'Notes',
            'ftype' => 'content',
            'description' => $registration['notes'],
            ];
    }

    if( $editable == 'yes' ) {
        $fields["line-notes"] = [
            'id' => "line-notes",
            'ftype' => 'line',
            ];
        $fields['internal_notes'] = [
            'id' => 'internal_notes',
            'label' => 'Internal Notes',
            'ftype' => 'textarea',
            'editable' => $editable,
            'value' => $registration['internal_notes'],
            ];
    } else {
        $fields['internal_notes'] = [
            'id' => 'internal_notes',
            'label' => 'Internal Notes',
            'ftype' => 'content',
            'description' => $registration['internal_notes'],
            ];
    }

    if( $editable == 'yes' ) {
        $options = [];
        foreach($maps['registration']['status'] as $status => $status_text) {
            if( isset($festival["registration-scrutineers-status-{$status}"]) 
                && $festival["registration-scrutineers-status-{$status}"] == 'yes'
                ) {
                $options[$status] = $status_text;
            }
        }
        $fields['status'] = [
            'id' => 'status',
            'label' => 'Registration Status',
            'ftype' => 'select',
            'size' => 'small',
            'options' => $options,
            'value' => $registration['status'],
            ];
    } else {
        $fields['status'] = [
            'id' => 'status',
            'label' => 'Status',
            'ftype' => 'content',
            'description' => $maps['registration']['status'][$registration['status']],
            ];
    }

    $blocks[] = array(
        'type' => 'form',
        'form-id' => 'addregform',
        'title' => 'Registration',
        'class' => 'limit-width limit-width-80',
        'problem-list' => $form_errors,
//        'cancel-label' => ($registration['status'] == 10 ? 'Cancel' : ''),
        'cancel-label' => 'Cancel',
//        'js-submit' => 'formSubmit();',
//        'js-cancel' => 'formCancel();',
//        'submit-label' => ($registration['status'] == 10 ? 'Save' : 'Back'),
        'submit-label' => 'Save',
//        'submit-label' => 'Save',
        'fields' => $fields,
        );

    
    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
