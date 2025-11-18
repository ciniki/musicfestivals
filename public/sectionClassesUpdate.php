<?php
//
// Description
// ===========
// This method will return all the information about an section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the section is attached to.
// section_id:          The ID of the section to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_sectionClassesUpdate($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'syllabus_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Syllabus'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'earlybird_fee_update'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Earlybird Fee Update'),
        'fee_update'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Fee Update'),
        'virtual_fee_update'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Virtual Fee Update'),
        'earlybird_plus_fee_update'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Earlybird Plus Fee Update'),
        'plus_fee_update'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Plus Fee Update'),
        'instrument'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Instrument Setting'),
        'accompanist'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accompanist Setting'),
        'movements'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Movements Setting'),
        'composer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Composer Setting'),
        'backtrack'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Backtrack Setting'),
        'artwork'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Artwork Setting'),
        'video'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Video Setting'),
        'music'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Music Setting'),
        'marking'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Marking Flags Setting'),
        'multireg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Multi Registration Option'),
        'find_replace_fields'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Fields'),
        'find'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Find String'),
        'replace'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Replace String'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.sectionClasses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Get the list of classes in the section
    //
    $strsql = "SELECT classes.id, "
        . "classes.flags, "
        . "classes.feeflags, "
        . "classes.titleflags, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee, "
        . "classes.earlybird_plus_fee, "
        . "classes.plus_fee, "
        . "classes.synopsis "
        . "FROM ciniki_musicfestival_categories AS categories "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.syllabus_id = '" . ciniki_core_dbQuote($ciniki, $args['syllabus_id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( !isset($args['section_id']) || $args['section_id'] == 0 ) {
        // Apply to all classes in festival when section_id is zero
        $strsql .= "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
    } else {
        $strsql .= "AND categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
    }
    if( isset($args['category_id']) && $args['category_id'] > 0 ) {
        $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
    }
    $strsql .= "GROUP BY classes.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'flags', 'feeflags', 'titleflags', 
                'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee',
                'synopsis',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.797', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
    }
    $classes = isset($rc['classes']) ? $rc['classes'] : array();

    foreach($classes as $class) {
        $update_args = array();
        if( isset($args['fee_update']) && $args['fee_update'] != '' && $args['fee_update'] != 0 ) {
            $update_args['fee'] = $class['fee'] + $args['fee_update'];
        }

        //
        // Update virtual fees
        //
        if( ($festival['flags']&0x04) == 0x04 
            && isset($args['virtual_fee_update']) && $args['virtual_fee_update'] != '' && $args['virtual_fee_update'] != 0 
            ) {
            $update_args['virtual_fee'] = $class['virtual_fee'] + $args['virtual_fee_update'];
        }

        //
        // Update earlybird fees
        //
        if( ($festival['flags']&0x20) == 0x20 
            && isset($args['earlybird_fee_update']) && $args['earlybird_fee_update'] != '' && $args['earlybird_fee_update'] != 0 
            ) {
            $update_args['earlybird_fee'] = $class['earlybird_fee'] + $args['earlybird_fee_update'];
        }

        //
        // Update plus fees
        //
        if( ($festival['flags']&0x10) == 0x10 
            && isset($args['plus_fee_update']) && $args['plus_fee_update'] != '' 
            && $args['plus_fee_update'] != 0 
            ) {
            $update_args['plus_fee'] = $class['plus_fee'] + $args['plus_fee_update'];
        }
        if( ($festival['flags']&0x30) == 0x30 
            && isset($args['earlybird_plus_fee_update']) && $args['earlybird_plus_fee_update'] != '' 
            && $args['earlybird_plus_fee_update'] != 0 
            ) {
            $update_args['earlybird_plus_fee'] = $class['earlybird_plus_fee'] + $args['earlybird_plus_fee_update'];
        }

        //
        // Set the flags
        //
        $flags = $class['flags'];
        $feeflags = $class['feeflags'];
        $titleflags = $class['titleflags'];

        //
        // Update the instrument
        //
        if( isset($args['instrument']) && $args['instrument'] == 'yes' ) {
            $flags |= 0x04;
        } elseif( isset($args['instrument']) && $args['instrument'] == 'no' ) {
            $flags = $flags&0xFFFFFFFB;
        }
        
        //
        // Update Accompanist
        //
        if( isset($args['accompanist']) && strtolower($args['accompanist']) == 'none' && ($class['flags']&0x3000) > 0 ) {
            $flags = ($flags&0xFFFFCFFF);
        } elseif( isset($args['accompanist']) && strtolower($args['accompanist']) == 'required' && ($class['flags']&0x1000) == 0 ) {
            $flags = ($flags&0xFFFFCFFF) | 0x1000;
        } elseif( isset($args['accompanist']) && strtolower($args['accompanist']) == 'optional' && ($class['flags']&0x2000) == 0 ) {
            $flags = ($flags&0xFFFFCFFF) | 0x2000;
        }

        //
        // Update movements
        //
        if( isset($args['movements']) && strtolower($args['movements']) == 'none' && ($class['flags']&0x0C000000) > 0 ) {
            $flags = ($flags&0xF3FFFFFF);
        } elseif( isset($args['movements']) && strtolower($args['movements']) == 'required' && ($class['flags']&0x04000000) == 0 ) {
            $flags = ($flags&0xF3FFFFFF) | 0x04000000;
        } elseif( isset($args['movements']) && strtolower($args['movements']) == 'optional' && ($class['flags']&0x08000000) == 0 ) {
            $flags = ($flags&0xF3FFFFFF) | 0x08000000;
        }

        //
        // Update composer
        //
        if( isset($args['composer']) && strtolower($args['composer']) == 'none' && ($class['flags']&0x30000000) > 0 ) {
            $flags = ($flags&0xCFFFFFFF);
        } elseif( isset($args['composer']) && strtolower($args['composer']) == 'required' && ($class['flags']&0x10000000) == 0 ) {
            $flags = ($flags&0xCFFFFFFF) | 0x10000000;
        } elseif( isset($args['composer']) && strtolower($args['composer']) == 'optional' && ($class['flags']&0x20000000) == 0 ) {
            $flags = ($flags&0xCFFFFFFF) | 0x20000000;
        }

        //
        // Update backtrack
        //
        if( isset($args['backtrack']) && strtolower($args['backtrack']) == 'none' && ($class['flags']&0x03000000) > 0 ) {
            $flags = ($flags&0xFCFFFFFF);
        } elseif( isset($args['backtrack']) && strtolower($args['backtrack']) == 'required' && ($class['flags']&0x01000000) == 0 ) {
            $flags = ($flags&0xFCFFFFFF) | 0x01000000;
        } elseif( isset($args['backtrack']) && strtolower($args['backtrack']) == 'optional' && ($class['flags']&0x02000000) == 0 ) {
            $flags = ($flags&0xFCFFFFFF) | 0x02000000;
        }

        //
        // Update artwork
        //
        if( isset($args['artwork']) && strtolower($args['artwork']) == 'none' && ($class['titleflags']&0x0300) > 0 ) {
            $titleflags = ($titleflags&0xFFFFFCFF);
        } elseif( isset($args['artwork']) && strtolower($args['artwork']) == 'required' && ($class['titleflags']&0x0100) == 0 ) {
            $titleflags = ($titleflags&0xFFFFFCFF) | 0x0100;
        } elseif( isset($args['artwork']) && strtolower($args['artwork']) == 'optional' && ($class['titleflags']&0x0200) == 0 ) {
            $titleflags = ($titleflags&0xFFFFFCFF) | 0x0200;
        }

        //
        // Update video
        //
        if( isset($args['video']) && strtolower($args['video']) == 'optional' && ($class['flags']&0x030000) > 0 ) {
            $flags = ($flags&0xFFFCFFFF);
        } elseif( isset($args['video']) && strtolower($args['video']) == 'required' && ($class['flags']&0x010000) == 0 ) {
            $flags = ($flags&0xFFFCFFFF) | 0x010000;
        } elseif( isset($args['video']) && strtolower($args['video']) == 'none' && ($class['flags']&0x020000) == 0 ) {
            $flags = ($flags&0xFFFCFFFF) | 0x020000;
        }

        //
        // Update music
        //
        if( isset($args['music']) && strtolower($args['music']) == 'optional' && ($class['flags']&0x300000) > 0 ) {
            $flags = ($flags&0xFFCFFFFF);
        } elseif( isset($args['music']) && strtolower($args['music']) == 'required' && ($class['flags']&0x100000) == 0 ) {
            $flags = ($flags&0xFFCFFFFF) | 0x100000;
        } elseif( isset($args['music']) && strtolower($args['music']) == 'none' && ($class['flags']&0x200000) == 0 ) {
            $flags = ($flags&0xFFCFFFFF) | 0x200000;
        }

        //
        // Update the marking
        //
        if( isset($args['marking']) && $args['marking'] != '' && is_numeric($args['marking']) ) {
            $flags = ($flags&0xFFFFF0FF) | ($args['marking']&0x00000F00);
        }

        //
        // Check if multiple/registrant is changed
        //
        if( isset($args['multireg']) && $args['multireg'] == 'yes' && ($flags&0x02) == 0 ) {
            $flags |= 0x02;
        } elseif( isset($args['multireg']) && $args['multireg'] == 'no' && ($flags&0x02) == 0x02 ) {
            $flags = $flags&0xFFFFFFFD;
        }

        if( isset($args['find_replace_fields']) && is_array($args['find_replace_fields']) && count($args['find_replace_fields']) > 0 ) {
            foreach($args['find_replace_fields'] as $field) {
                if( $field == 'synopsis' ) {
                    $synopsis = str_replace($args['find'], $args['replace'], $class['synopsis']);
                    if( $synopsis != $class['synopsis'] ) {
                        $update_args['synopsis'] = $synopsis;
                    }
                }
            }
        }
        //
        // Check if anything changed flags
        //
        if( $flags != $class['flags'] ) {
            $update_args['flags'] = $flags;
        }
        if( $feeflags != $class['feeflags'] ) {
            $update_args['feeflags'] = $feeflags;
        }
        if( $titleflags != $class['titleflags'] ) {
            $update_args['titleflags'] = $titleflags;
        }

        if( count($update_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.class', $class['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.772', 'msg'=>'Unable to update the class', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
