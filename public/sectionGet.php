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
function ciniki_musicfestivals_sectionGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
        'festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Festival'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.sectionGet');
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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    
    //
    // Setup the arrays for the lists of next/prev ids
    //
    $nplists = array(
        'categories'=>array(),
        );

    //
    // Return default for new Section
    //
    if( $args['section_id'] == 0 ) {
        $seq = 1;
        if( $args['festival_id'] && $args['festival_id'] > 0 ) {
            $strsql = "SELECT MAX(sequence) AS max_sequence "
                . "FROM ciniki_musicfestival_sections "
                . "WHERE ciniki_musicfestival_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_musicfestival_sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'max');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['max']['max_sequence']) ) {
                $seq = $rc['max']['max_sequence'] + 1;
            }
        }
        $section = array('id'=>0,
            'festival_id'=>(isset($args['festival_id']) ? $args['festival_id'] : 0),
            'name'=>'',
            'permalink'=>'',
            'sequence'=>$seq,
            'primary_image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Section
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_sections.id, "
            . "ciniki_musicfestival_sections.festival_id, "
            . "ciniki_musicfestival_sections.name, "
            . "ciniki_musicfestival_sections.permalink, "
            . "ciniki_musicfestival_sections.sequence, "
            . "ciniki_musicfestival_sections.flags, "
            . "ciniki_musicfestival_sections.primary_image_id, "
            . "ciniki_musicfestival_sections.synopsis, "
            . "ciniki_musicfestival_sections.description, "
            . "ciniki_musicfestival_sections.live_end_dt, "
            . "ciniki_musicfestival_sections.virtual_end_dt "
            . "FROM ciniki_musicfestival_sections "
            . "WHERE ciniki_musicfestival_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
            . "ORDER BY sequence, name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('festival_id', 'name', 'permalink', 'sequence', 'flags', 
                    'primary_image_id', 'synopsis', 'description',
                    'live_end_dt', 'virtual_end_dt', 
                    ),
                'utctotz'=>array(
                    'live_end_dt'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'virtual_end_dt'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.21', 'msg'=>'Section not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['sections'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.22', 'msg'=>'Unable to find Section'));
        }
        $section = $rc['sections'][0];

        //
        // Check if to include categories
        //
        if( isset($args['categories']) && $args['categories'] == 'yes' ) {
            $strsql = "SELECT ciniki_musicfestival_categories.id, "
                . "ciniki_musicfestival_categories.festival_id, "
                . "ciniki_musicfestival_categories.section_id, "
                . "ciniki_musicfestival_categories.name, "
                . "ciniki_musicfestival_categories.permalink, "
                . "ciniki_musicfestival_categories.sequence "
                . "FROM ciniki_musicfestival_categories "
                . "WHERE ciniki_musicfestival_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_musicfestival_categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                . "ORDER BY ciniki_musicfestival_categories.sequence, ciniki_musicfestival_categories.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'categories', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'section_id', 'name', 'permalink', 'sequence')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['categories']) ) {
                $section['categories'] = $rc['categories'];
                $nplists['categories'] = array();
                foreach($section['categories'] as $iid => $category) {
                    $nplists['categories'][] = $category['id'];
                }
            } else {
                $section['categories'] = array();
                $nplists['categories'] = array();
            }
        }
    }

    return array('stat'=>'ok', 'section'=>$section, 'nplists'=>$nplists);
}
?>
