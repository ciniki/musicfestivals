<?php
//
// Description
// -----------
// This function will merge the title, composer and movements into 1 line
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_titleMerge(&$ciniki, $tnid, $registration, $i) {

    $line = $registration["title{$i}"];
    if( isset($registration["opus{$i}"]) 
        && $registration["opus{$i}"] != '' 
        && strtolower($registration["opus{$i}"]) != 'na'
        && strtolower($registration["opus{$i}"]) != 'tba'
        && strtolower($registration["opus{$i}"]) != 'tbd'
        && strtolower($registration["opus{$i}"]) != 'n/a'
        && strtolower($registration["opus{$i}"]) != 'not applicable'
        && strtolower($registration["opus{$i}"]) != 'none'
        ) {
        $line .= ', ' . $registration["opus{$i}"];
    }
    if( isset($registration["movements{$i}"]) 
        && $registration["movements{$i}"] != '' 
        && strtolower($registration["movements{$i}"]) != 'na'
        && strtolower($registration["movements{$i}"]) != 'tba'
        && strtolower($registration["movements{$i}"]) != 'tbd'
        && strtolower($registration["movements{$i}"]) != 'n/a'
        && strtolower($registration["movements{$i}"]) != 'not applicable'
        && strtolower($registration["movements{$i}"]) != 'none'
        ) {
        $line .= ', ' . $registration["movements{$i}"];
    }
    if( isset($registration["musical{$i}"]) 
        && $registration["musical{$i}"] != '' 
        && strtolower($registration["musical{$i}"]) != 'na'
        && strtolower($registration["musical{$i}"]) != 'tba'
        && strtolower($registration["musical{$i}"]) != 'tbd'
        && strtolower($registration["musical{$i}"]) != 'n/a'
        && strtolower($registration["musical{$i}"]) != 'not applicable'
        && strtolower($registration["musical{$i}"]) != 'none'
        ) {
        $line .= ', ' . $registration["musical{$i}"];
    }
    if( isset($registration["composer{$i}"]) 
        && $registration["composer{$i}"] != ''
        && strtolower($registration["composer{$i}"]) != 'na'
        && strtolower($registration["composer{$i}"]) != 'tba'
        && strtolower($registration["composer{$i}"]) != 'tbd'
        && strtolower($registration["composer{$i}"]) != 'n/a'
        && strtolower($registration["composer{$i}"]) != 'not applicable'
        && strtolower($registration["composer{$i}"]) != 'none'
        ) {
        // Code also in apiClassTitleSearch
        if( preg_match("/^\s*[Bb][Yy]\s+/", $registration["composer{$i}"]) ) {
            $line .= ' ' . $registration["composer{$i}"];
        } elseif( preg_match("/^\s*[Aa][Rr][Rr]\.\s+/", $registration["composer{$i}"]) ) {    // arr. OR arranged
            $line .= ' ' . $registration["composer{$i}"];
        } elseif( preg_match("/^\s*[Aa][Rr][Rr]\s+/", $registration["composer{$i}"]) ) {    // arr. OR arranged
            $line .= ' ' . $registration["composer{$i}"];
        } elseif( preg_match("/^\s*[Aa][Tt][Tt][Rr]\s+/", $registration["composer{$i}"]) ) {    // Attr or attributed
            $line .= ' ' . $registration["composer{$i}"];
        } elseif( preg_match("/^\s*[Aa][Dd][Aa][Pp]\s+/", $registration["composer{$i}"]) ) {     // Adapted
            $line .= ' ' . $registration["composer{$i}"];
        } else {
            $line .= ' by ' . $registration["composer{$i}"];
        }
    }
    if( isset($registration["arranger{$i}"]) 
        && $registration["arranger{$i}"] != ''
        && strtolower($registration["arranger{$i}"]) != 'na'
        && strtolower($registration["arranger{$i}"]) != 'tba'
        && strtolower($registration["arranger{$i}"]) != 'tbd'
        && strtolower($registration["arranger{$i}"]) != 'n/a'
        && strtolower($registration["arranger{$i}"]) != 'not applicable'
        && strtolower($registration["arranger{$i}"]) != 'none'
        ) {
        if( preg_match("/^\s*[Bb][Yy]\s+/", $registration["arranger{$i}"]) ) {
            $line .= ' ' . $registration["arranger{$i}"];
        } elseif( preg_match("/^\s*[Aa][Rr][Rr]\.\s+/", $registration["arranger{$i}"]) ) {    // arr. OR arranged
            $line .= ' ' . $registration["arranger{$i}"];
        } elseif( preg_match("/^\s*[Aa][Rr][Rr]\s+/", $registration["arranger{$i}"]) ) {    // arr. OR arranged
            $line .= ' ' . $registration["arranger{$i}"];
        } elseif( preg_match("/^\s*[Aa][Tt][Tt][Rr]\s+/", $registration["arranger{$i}"]) ) {    // Attr or attributed
            $line .= ' ' . $registration["arranger{$i}"];
        } elseif( preg_match("/^\s*[Aa][Dd][Aa][Pp]\s+/", $registration["arranger{$i}"]) ) {     // Adapted
            $line .= ' ' . $registration["arranger{$i}"];
        } else {
            $line .= ', arr. ' . $registration["arranger{$i}"];
        }
    } 

    return array('stat'=>'ok', 'title'=>$line);
}
?>
