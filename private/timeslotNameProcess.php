<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_timeslotNameProcess(&$ciniki, $tnid, $timeslot, $args) {

    if( $timeslot['name'] == '' ) {
        if( $args['format'] == 'code-section-category-class' ) {
            $name = $timeslot['class_code'] . ' - ' . $timeslot['syllabus_section_name'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
        } elseif( $args['format'] == 'code-category-class' ) {
            $name = $timeslot['class_code'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
        } else {
            $name = $timeslot['class_code'] . ' - ' . $timeslot['class_name']; 
        }
    } elseif( (!isset($timeslot['registrations']) || count($timeslot['registrations']) == 0)
        && ($args['format'] == 'code-section-category-class' || $args['format'] == 'code-category-class') 
        ) {
        $name = $division['name'] . ' - ' . $timeslot['name'];
    } else {
        $name = $timeslot['name'];
    }

    if( isset($args['separate-classes']) && $args['separate-classes'] == 'yes' && $timeslot['class_code'] != '' ) {
        if( $args['format'] == 'code-section-category-class' ) {
            $name = $timeslot['class_code'] . ' - ' . $timeslot['syllabus_section_name'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
        } elseif( $args['format'] == 'code-category-class' ) {
            $name = $timeslot['class_code'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
        } else {
            $name = $timeslot['class_code'] . ' - ' . $timeslot['class_name']; 
        }
    }

    //
    // Check if groupname should be added
    //
    if( isset($timeslot['groupname']) && $timeslot['groupname'] != '' ) {
        $name .= ' - ' . $timeslot['groupname'];
    }
    
    return array('stat'=>'ok', 'name'=>$name);
}
?>
