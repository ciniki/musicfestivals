<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_flags(&$ciniki) {
    //
    // The flags for the object
    //
    $flags = array(
        // 0x01
//        array('flag'=>array('bit'=>'1', 'name'=>'Earlybird **deprecated**')), // now festival flag
//        array('flag'=>array('bit'=>'2', 'name'=>'Online Registrations **deprecated**')),
        array('flag'=>array('bit'=>'3', 'name'=>'Timeslot Photos')),
        array('flag'=>array('bit'=>'4', 'name'=>'Results By Date')),   // Temp feature to be better integrated in future
        // 0x10
        array('flag'=>array('bit'=>'5', 'name'=>'Sponsors')),
        array('flag'=>array('bit'=>'6', 'name'=>'Lists')),
        array('flag'=>array('bit'=>'7', 'name'=>'Trophies')),
        array('flag'=>array('bit'=>'8', 'name'=>'Pronouns')),
        // 0x0100
        array('flag'=>array('bit'=>'9', 'name'=>'Main Menu Festivals')),
        array('flag'=>array('bit'=>'10', 'name'=>'Email Lists')),
        array('flag'=>array('bit'=>'11', 'name'=>'Messages')),
        array('flag'=>array('bit'=>'12', 'name'=>'Schedule Division Adjudicators')), 
        // 0x1000
        array('flag'=>array('bit'=>'13', 'name'=>'Class Levels')), // May be changed to tags in the future
        array('flag'=>array('bit'=>'14', 'name'=>'Experimental')), // Experimental settings
        array('flag'=>array('bit'=>'15', 'name'=>'Advanced Scheduler')),  
        array('flag'=>array('bit'=>'16', 'name'=>'Accompanists')),
        // 0x010000
        array('flag'=>array('bit'=>'17', 'name'=>'Provincials')), // Name may change
        array('flag'=>array('bit'=>'18', 'name'=>'Split Virtual/Live Syllabus')),
//        array('flag'=>array('bit'=>'19', 'name'=>'Separate Composer/Movements **deprecated**')), // **deprecated** now in class flags
        array('flag'=>array('bit'=>'20', 'name'=>'Schedule Individual Times')),   // each registration has a scheduled time
        // 0x100000
        array('flag'=>array('bit'=>'21', 'name'=>'Social Content')), 
        array('flag'=>array('bit'=>'22', 'name'=>'Files')), // This will be deprecated, only there for older tenants
//        array('flag'=>array('bit'=>'23', 'name'=>'')),
//        array('flag'=>array('bit'=>'24', 'name'=>'')),
        );
    //
    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
