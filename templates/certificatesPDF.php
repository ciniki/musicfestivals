<?php
//
// Description
// ===========
// This method will produce a PDF of the class.
//
// NOTE: the background required should be opened in Preview on Mac, and Exported to PNG 300 dpi (NO ALPHA)!!!
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_musicfestivals_templates_certificatesPDF(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');

    //
    // Load the tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Check for certificates passed
    //
    if( !isset($args['certificates']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.301', 'msg'=>'No certificates specified', 'err'=>$rc['err']));
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Setup the PDF
    //
    require_once($ciniki['config']['core']['lib_dir'] . '/tcpdf/tcpdf.php');
    class MYPDF extends TCPDF {
        public function Header() { }
        public function Footer() { }
    }
    $pdf = new TCPDF('L', PDF_UNIT, 'LETTER', true, 'ISO-8859-1', false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Certificates');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    $filename = 'certificates';

    $border = (isset($args['testmode']) && $args['testmode'] == 'yes' ? 1 : 0);
    $pdf->setDrawColor(225);
    $pdf->setLineWidth(0.1);

    //
    // Go through the certificates to print
    //
    foreach($args['certificates'] as $certificate) {
        if( isset($certificate['orientation']) && $certificate['orientation'] == 'P' ) {
            $pdf->AddPage('P');
        } else {
            $pdf->AddPage();
        }
        $pdf->SetCellPaddings(1, 0, 1, 0);

        if( isset($certificate['image_id']) && $certificate['image_id'] > 0 ) {
            //
            // Load the image storage filename, adding as blob is REALLY slow!
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'loadOriginalStorageFilename');
            $rc = ciniki_images_hooks_loadOriginalStorageFilename($ciniki, $tnid, array('image_id'=>$certificate['image_id']));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.302', 'msg'=>'No image specified', 'err'=>$rc['err']));
            }
            if( isset($certificate['orientation']) && $certificate['orientation'] == 'P' ) {
                $pdf->Image($rc['filename'], 0, 0, 216, 279, '', '', '', false, 300, '', false, false, 0);
            } else {
                $pdf->Image($rc['filename'], 0, 0, 280, 216, '', '', '', false, 300, '', false, false, 0);
            }
            // Don't use blob for same image!!!!
            //$pdf->Image('@'.$rc['image']->getImageBlob(), 0, 0, 279, 216, '', '', '', false, 300, '', false, false, 0);
        }

        $pdf->setPageMark();
       
        if( isset($certificate['fields']) ) {
            foreach($certificate['fields'] as $field) {
                if( $field['field'] == 'adjudicatorsig' 
                    || ($field['field'] == 'adjudicatorsigorname' && isset($field['image_id']) && $field['image_id'] > 0) 
                    ) {
                    if( isset($field['image_id']) && $field['image_id'] > 0 ) {
                        $rc = ciniki_images_loadImage($ciniki, $tnid, $field['image_id'], 'original');
                        if( $rc['stat'] == 'ok' ) {
                            $height = $rc['image']->getImageHeight();
                            $width = $rc['image']->getImageWidth();
                            if( $width > 600 ) {
                                $this->header_image->scaleImage(600, 0);
                            }
                            $image_ratio = $width/$height;
                            $available_ratio = $field['width']/$field['height'];
                            if( $available_ratio < $image_ratio ) {
                                $pdf->Image('@'.$rc['image']->getImageBlob(), $field['xpos'], $field['ypos'], $field['width'], 0, '', '', 'C', 2, '150', '', false, false, 0, 'CM');
                            } else {
                                $pdf->Image('@'.$rc['image']->getImageBlob(), $field['xpos'], $field['ypos'], $field['width'], $field['height'], '', '', 'C', 2, '150', '', false, false, 0, 'CM');
                            }
                        }
                    } elseif( $border == 1 ) {
                        $pdf->setXY($field['xpos'], $field['ypos']);
                        $pdf->MultiCell($field['width'], $field['height'], $field['text'], $border, $field['align'], 0, 0, '', '', true, 1, false, true, 0, $field['valign'], true);
                    }
                } else {
                    $pdf->setFont($field['font'], $field['style'], $field['size']);
                    $pdf->setXY($field['xpos'], $field['ypos']);
                    $pdf->MultiCell($field['width'], $field['height'], $field['text'], $border, $field['align'], 0, 0, '', '', true, 1, false, true, 0, $field['valign'], true);
                }
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
