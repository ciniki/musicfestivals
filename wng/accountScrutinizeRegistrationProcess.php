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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
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

    $fields = ['title', 'opus', 'movements', 'musical', 'composer', 'arranger', 'perf_time', 'video_url', 'music_orgfilename', 'backtrack', 'artwork'];

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
        . "registrations.class_id, "
        . "registrations.fulltitle1, "
        . "registrations.fulltitle2, "
        . "registrations.fulltitle3, "
        . "registrations.fulltitle4, "
        . "registrations.fulltitle5, "
        . "registrations.fulltitle6, "
        . "registrations.fulltitle7, "
        . "registrations.fulltitle8, ";
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1380', 'msg'=>'Unable to find requested registration'));
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

            //
            // Update the full titles for the registration
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationFullTitlesUpdate');
            $rc = ciniki_musicfestivals_registrationFullTitlesUpdate($ciniki, $tnid, [
                'festival_id' => $festival['id'],
                'registration_id' => $registration['id'],
                ]);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                return $rc;
            }

        }

        header("Location: {$base_url}");
        return array('stat'=>'exit');
    }
    
    //
    // Load Competitor information
    //
    
    //
    // Format the class name
    //
    $rc = ciniki_musicfestivals_classNameFormat($ciniki, $tnid, [
        'code' => $class['code'],
        'name' => $class['name'],
        'category' => $category['name'],
        'section' => $section['name'],
        'format' => isset($festival['registrations-class-format']) ? $festival['registrations-class-format'] : 'code-class',
        ]);
    $class_name = $rc['name'];


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
            'description' => $class_name,
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
    $competitor_ids = [];
    for($i = 1; $i <= 5; $i++) {
        if( $registration["competitor{$i}_id"] > 0 ) {
            if( !in_array($registration["competitor{$i}_id"], $competitor_ids) ) {
                $competitor_ids[] = $registration["competitor{$i}_id"];
            }
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

    //
    // Lookup other registrations for competitors
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.display_name, "
        . "registrations.fulltitle1, "
        . "registrations.fulltitle2, "
        . "registrations.fulltitle3, "
        . "registrations.fulltitle4, "
        . "registrations.fulltitle5, "
        . "registrations.fulltitle6, "
        . "registrations.fulltitle7, "
        . "registrations.fulltitle8, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.name AS category_name, "
        . "sections.name AS section_name "
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
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND ("
            . "registrations.competitor1_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $competitor_ids) . ") "
            . "OR registrations.competitor2_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $competitor_ids) . ") "
            . "OR registrations.competitor3_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $competitor_ids) . ") "
            . "OR registrations.competitor4_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $competitor_ids) . ") "
            . "OR registrations.competitor5_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $competitor_ids) . ") "
            . ") "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND registrations.id <> '" . ciniki_core_dbQuote($ciniki, $registration['id']) . "' "
        . "ORDER BY display_name, class_code, fulltitle1 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'display_name', 'fulltitle1', 'fulltitle2', 'fulltitle3', 'fulltitle4', 
                'fulltitle5', 'fulltitle6', 'fulltitle7', 'fulltitle8', 'class_code', 'class_name', 'category_name', 'section_name',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.395', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $other_registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
    $other_txt = '';
    if( count($other_registrations) > 0 ) {
        foreach($other_registrations as $oreg) {
            $titles = '';
            for($i = 1; $i <= 8; $i++) {    
                if( $oreg["fulltitle{$i}"] != '' ) {
                    $titles .= ($titles != '' ? "\n" : '') . '&nbsp;&nbsp;&nbsp;- ' . $oreg["fulltitle{$i}"];
                }
            }
            $rc = ciniki_musicfestivals_classNameFormat($ciniki, $tnid, [
                'code' => $oreg['class_code'],
                'name' => $oreg['class_name'],
                'category' => $oreg['category_name'],
                'section' => $oreg['section_name'],
                'format' => isset($festival['registrations-class-format']) ? $festival['registrations-class-format'] : 'code-class',
                ]);
            $class_name = $rc['name'];
            
            $other_txt .= ($other_txt != '' ? "\n" : '') . "{$class_name} - {$oreg['display_name']}\n{$titles}\n";
        }
        if( $other_txt != '' ) {
            $fields["other_reg"] = [
                'id' => "other_reg",
                'label' => 'Other Registrations',
                'ftype' => 'content',
                'description' => $other_txt,
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
            if( ($class['titleflags']&0x0C00) > 0 ) {
                $fields["opus{$i}"] = [
                    'id' => "opus{$i}",
                    'ftype' => 'text',
                    'flex-basis' => '50%',
                    'editable' => $editable,
                    'size' => 'small',
                    'label' => "{$prefix} " . (isset($festival['registration-opus-label']) && $festival['registration-opus-label'] != '' ? $festival['registration-opus-label'] : "Opus"),
                    'value' => isset($_POST["f-opus{$i}"]) ? $_POST["f-opus{$i}"] : $registration["opus{$i}"],
                    ];
            }
            if( ($class['flags']&0x0C000000) > 0 ) {
                $fields["movements{$i}"] = [
                    'id' => "movements{$i}",
                    'ftype' => 'text',
                    'flex-basis' => '50%',
                    'editable' => $editable,
                    'size' => 'small',
                    'label' => "{$prefix} " . (isset($festival['registration-movements-label']) && $festival['registration-movements-label'] != '' ? $festival['registration-movements-label'] : "Movements"),
                    'value' => isset($_POST["f-movements{$i}"]) ? $_POST["f-movements{$i}"] : $registration["movements{$i}"],
                    ];
            }
            if( ($class['titleflags']&0xC000) > 0 ) {
                $fields["musical{$i}"] = [
                    'id' => "musical{$i}",
                    'ftype' => 'text',
                    'flex-basis' => '50%',
                    'editable' => $editable,
                    'size' => 'small',
                    'label' => "{$prefix} " . (isset($festival['registration-musical-label']) && $festival['registration-musical-label'] != '' ? $festival['registration-musical-label'] : "Musical"),
                    'value' => isset($_POST["f-musical{$i}"]) ? $_POST["f-musical{$i}"] : $registration["musical{$i}"],
                    ];
            }
            if( ($class['flags']&0x30000000) > 0 ) {
                $fields["composer{$i}"] = [
                    'id' => "composer{$i}",
                    'ftype' => 'text',
                    'flex-basis' => '50%',
                    'editable' => $editable,
                    'size' => 'small',
                    'label' => "{$prefix} " . (isset($festival['registration-composer-label']) && $festival['registration-composer-label'] != '' ? $festival['registration-composer-label'] : "Composer"),
                    'value' => isset($_POST["f-composer{$i}"]) ? $_POST["f-composer{$i}"] : $registration["composer{$i}"],
                    ];
            }
            if( ($class['titleflags']&0x0C0000) > 0 ) {
                $fields["arranger{$i}"] = [
                    'id' => "arranger{$i}",
                    'ftype' => 'text',
                    'flex-basis' => '50%',
                    'editable' => $editable,
                    'size' => 'small',
                    'label' => "{$prefix} " . (isset($festival['registration-arranger-label']) && $festival['registration-arranger-label'] != '' ? $festival['registration-arranger-label'] : "Arranger"),
                    'value' => isset($_POST["f-arranger{$i}"]) ? $_POST["f-arranger{$i}"] : $registration["arranger{$i}"],
                    ];
            }
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
                $fields["title{$i}"] = [
                    'id' => "title{$i}",
                    'ftype' => 'content',
                    'label' => 'Title',
                    'flex-basis' => '50%',
                    'description' => $registration["fulltitle{$i}"],
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
