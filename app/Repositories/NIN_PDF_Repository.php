<?php

namespace App\Repositories;

use App\Models\Verification;
use Illuminate\Support\Facades\Log;
use TCPDF;

class NIN_PDF_Repository
{
    /**
     * Helper to find a verification record by NIN, checking multiple possible columns
     */
    private function findRecord($nin_no)
    {
        return Verification::where('number_nin', $nin_no)
            ->orWhere('idno', $nin_no)
            ->orWhere('nin', $nin_no)
            ->latest()
            ->first();
    }

    /**
     * Helper to prepare NIN data from Verification model with new schema column names
     */
    private function prepareNinData($verifiedRecord)
    {
        return [
            "nin" => $verifiedRecord->number_nin ?? $verifiedRecord->idno ?? $verifiedRecord->nin,
            "fName" => $verifiedRecord->firstname,
            "sName" => $verifiedRecord->surname,
            "mName" => $verifiedRecord->middlename,
            "tId" => $verifiedRecord->trackingId,
            "address" => $verifiedRecord->residence_address ?? $verifiedRecord->address,
            "lga" => $verifiedRecord->residence_lga ?? $verifiedRecord->lga,
            "state" => $verifiedRecord->residence_state ?? $verifiedRecord->state,
            "gender" => (strtoupper($verifiedRecord->gender) === 'MALE' || strtoupper($verifiedRecord->gender) === 'M') ? "M" : "F",
            "dob" => $verifiedRecord->birthdate,
            "photo" => str_replace('data:image/jpg;base64,', '', $verifiedRecord->photo_path ?? $verifiedRecord->photo),
            "signature" => str_replace('data:image/png;base64,', '', $verifiedRecord->signature_path ?? $verifiedRecord->signature),
            "phoneno" => str_replace('+234', '0', $verifiedRecord->telephoneno ?? $verifiedRecord->phoneno),
            "created_at" => $verifiedRecord->created_at,
            "reference" => $verifiedRecord->reference,
            "agent_id" => $verifiedRecord->performed_by,
        ];
    }

    public function regularPDF($nin_no)
    {
        $verifiedRecord = $this->findRecord($nin_no);
        if ($verifiedRecord) {
            $ninData = $this->prepareNinData($verifiedRecord);
            $names = $ninData['fName'] . ' ' . $ninData['sName'];

            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->setPrintHeader(false);
            $pdf->SetCreator('Abu');
            $pdf->SetAuthor('Zulaiha');
            $pdf->SetTitle(html_entity_decode($names));
            $pdf->SetSubject('Regular');
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->AddPage();

            // Background
            $pdf->Image(public_path('assets/card_and_Slip/regular.png'), 15, 50, 178, 80, '', '', '', false, 300, '', false, false, 0);

            // Photo
            $imgdata = base64_decode($ninData['photo']);
            if ($imgdata !== false) {
                $pdf->Image('@' . $imgdata, 166.8, 69.3, 25, 31, '', '', '', false, 300, '', false, false, 0);
            }

            // Text
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Text(85, 71, html_entity_decode($ninData['sName']));
            $pdf->Text(85, 79.7, html_entity_decode($ninData['fName']));
            $pdf->Text(85, 86.8, html_entity_decode($ninData['mName']));

            $pdf->SetFont('helvetica', '', 8);
            $pdf->Text(85, 96, $ninData['gender']);

            $pdf->SetFont('helvetica', '', 7);
            $pdf->Text(32, 71.8, $ninData['tId']);

            $pdf->SetFont('helvetica', '', 8);
            $pdf->Text(25, 79.5, $ninData['nin']);

            $pdf->SetFont('helvetica', '', 9);
            $pdf->MultiCell(50, 20, html_entity_decode($ninData['address']), 0, 'L', false, 1, 116, 74, true);

            $pdf->SetFont('helvetica', '', 8);
            $pdf->Text(116, 93, $ninData['lga']);
            $pdf->Text(116, 97, $ninData['state']);

            $filename = 'Regular NIN Slip - ' . $nin_no . '.pdf';
            $pdfContent = $pdf->Output($filename, 'S');

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $filename);
        }
        return response()->json(["message" => "Error", "errors" => ["Not Found" => "Verification record not found!"]], 422);
    }

    public function standardPDF($nin_no)
    {
        $verifiedRecord = $this->findRecord($nin_no);
        if ($verifiedRecord) {
            $ninData = $this->prepareNinData($verifiedRecord);
            $names = $ninData['fName'] . ' ' . $ninData['sName'];

            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->setPrintHeader(false);
            $pdf->SetTitle(html_entity_decode($names));
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->AddPage();
            
            $pdf->SetFont('dejavuserifcondensedbi', '', 12);
            $txt = "Please find below your new High Resolution NIN Slip. You may cut it out of the paper, fold and laminate as desired. Please DO NOT allow others to make copies of your NIN Slip.\n";
            $pdf->MultiCell(150, 20, $txt, 0, 'C', false, 1, 35, 20, true, 0, false, true, 0, 'T', false);

            $pdf->Image(public_path('assets/card_and_Slip/standard.jpg'), 70, 50, 80, 50, '', '', '', false, 300, '', false, false, 0);
            $pdf->Image(public_path('assets/card_and_Slip/back.jpg'), 70, 101, 80, 50, '', '', '', false, 300, '', false, false, 0);

            $style = ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => [255, 255, 255]];
            $datas = '{NIN: ' . $ninData['nin'] . ', NAME:' . html_entity_decode($ninData['fName']) . ' ' . html_entity_decode($ninData['mName']) . ' ' . html_entity_decode($ninData['sName']) . ', DOB: ' . $ninData['dob'] . ', Status:Verified}';
            $pdf->write2DBarcode($datas, 'QRCODE,H', 131.2, 64.7, 14.2, 13.5, $style, 'H');
            
            $photo = base64_decode($ninData['photo']);
            if ($photo !== false) {
                $pdf->Image('@' . $photo, 72, 62, 18, 23, '', '', '', false, 300, '', false, false, 0);
            }

            $pdf->SetFont('helvetica', '', 8);
            $pdf->Text(91.5, 65, html_entity_decode($ninData['sName']));
            $pdf->Text(91.5, 72, html_entity_decode($ninData['fName']) . ' ' . html_entity_decode($ninData['mName']));
            
            $newD = strtotime($ninData['dob'] ?: 'today');
            $pdf->Text(91.5, 78.7, date("d M Y", $newD));
            $pdf->Text(128, 80, date("d M Y"));

            $pdf->SetFont('helvetica', '', 21);
            $pdf->Text(81, 89, substr($ninData['nin'], 0, 4) . " " . substr($ninData['nin'], 4, 3) . " " . substr($ninData['nin'], 7));

            $filename = 'Standard NIN Slip - ' . $nin_no . '.pdf';
            $pdfContent = $pdf->Output($filename, 'S');

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $filename);
        }
        return response()->json(["message" => "Error", "errors" => ["Not Found" => "Verification record not found!"]], 422);
    }

    public function premiumPDF($nin_no)
    {
        $verifiedRecord = $this->findRecord($nin_no);
        if ($verifiedRecord) {
            $ninData = $this->prepareNinData($verifiedRecord);
            $names = $ninData['fName'] . ' ' . $ninData['sName'];

            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->setPrintHeader(false);
            $pdf->SetTitle(html_entity_decode($names));
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->AddPage();

            $pdf->SetFont('dejavuserifcondensedbi', '', 12);
            $txt = "Please find below your new High Resolution NIN Slip...";
            $pdf->MultiCell(150, 20, $txt, 0, 'C', false, 1, 35, 20, true, 0, false, true, 0, 'T', false);

            $pdf->Image(public_path('assets/card_and_Slip/premium.jpg'), 70, 50, 80, 50, 'JPG', '', '', false, 300, '', false, false, 0);
            $pdf->Image(public_path('assets/card_and_Slip/back.jpg'), 70, 101, 80, 50, 'JPG', '', '', false, 300, '', false, false, 0);

            $style = ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => [255, 255, 255]];
            $datas = '{NIN: ' . $ninData['nin'] . ', NAME: ' . html_entity_decode($ninData['fName']) . ' ' . html_entity_decode($ninData['mName']) . ' ' . html_entity_decode($ninData['sName']) . ', DOB: ' . $ninData['dob'] . ', Status:Verified}';
            $pdf->write2DBarcode($datas, 'QRCODE,H', 128, 53, 20, 20, $style, 'H');

            $imgdata = base64_decode($ninData['photo']);
            if ($imgdata !== false) {
                $pdf->Image('@' . $imgdata, 71.5, 62, 20, 25, 'JPG', '', '', false, 300, '', false, false, 0);
            }

            $pdf->SetFont('helvetica', '', 9);
            $pdf->Text(93.3, 66.5, html_entity_decode($ninData['sName']));
            $pdf->Text(93.3, 73.5, html_entity_decode($ninData['fName']) . ' ' . html_entity_decode($ninData['mName']));

            $newD = strtotime($ninData['dob'] ?: 'today');
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Text(93.3, 80.5, date("d M Y", $newD));
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Text(114, 80.5, $ninData['gender']);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Text(128, 81.8, date("d M Y"));

            $pdf->SetFont('helvetica', '', 21);
            $pdf->Text(81, 91, substr($ninData['nin'], 0, 4) . " " . substr($ninData['nin'], 4, 3) . " " . substr($ninData['nin'], 7));

            $filename = 'Premium NIN Slip - ' . $nin_no . '.pdf';
            $pdfContent = $pdf->Output($filename, 'S');

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }
        return response()->json(["message" => "Error", "errors" => ["Not Found" => "Verification record not found!"]], 422);
    }

    public function vninPDF($nin_no)
    {
        $verifiedRecord = $this->findRecord($nin_no);
        if ($verifiedRecord) {
            $ninData = $this->prepareNinData($verifiedRecord);
            
            // vnin specific dimensions and coordinate system from user's code
            $slipW = 190;
            $slipH = 95;
            $marginX = (210 - $slipW) / 2;
            $marginY = 30;
            $scaleX = $slipW / 297;
            $scaleY = $slipH / 210;

            $mapX = function($x) use ($marginX, $scaleX) { return ($x * $scaleX) + $marginX; };
            $mapY = function($y) use ($marginY, $scaleY) { return ($y * $scaleY) + $marginY; };

            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->AddPage();

            $pdf->Image(public_path('assets/card_and_Slip/vnin.png'), $marginX, $marginY, $slipW, $slipH, 'PNG', '', '', false, 300, '', false, false, 0);

            if (!empty($ninData['photo'])) {
                $imgdata = base64_decode($ninData['photo']);
                if ($imgdata !== false) {
                    $pdf->Image('@' . $imgdata, $mapX(15), $mapY(110), 20 * $scaleX, 35 * $scaleY, 'JPG', '', '', false, 300, '', false, false, 0);
                }
            }

            $givenNames = trim($ninData['fName'] . ' ' . $ninData['mName']);
            $qrData = 'NIN: ' . $ninData['nin'] . ', Name: ' . $ninData['sName'] . ' ' . $givenNames . ', DOB: ' . $ninData['dob'];
            $style = ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => [255, 255, 255]];
            $pdf->write2DBarcode($qrData, 'QRCODE,M', $mapX(75), $mapY(110), 16, 16, $style, 'N');

            $pdf->SetFont('helvetica', '', 6);
            $pdf->Text($mapX(36), $mapY(112), strtoupper($ninData['sName']));
            $pdf->Text($mapX(36), $mapY(126), strtoupper($givenNames));
            if (!empty($ninData['dob'])) {
                $pdf->Text($mapX(36), $mapY(136), date('d M Y', strtotime($ninData['dob'])));
            }

            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Text($mapX(103), $mapY(106), strtoupper($ninData['sName']));
            $pdf->Text($mapX(103), $mapY(126), strtoupper($givenNames));

            if (!empty($ninData['tId'])) {
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Text($mapX(225), $mapY(115), "TOKEN");
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Text($mapX(225), $mapY(120), substr($ninData['tId'], 0, 15));
            }

            $filename = 'VNIN Slip - ' . $nin_no . '.pdf';
            $pdfContent = $pdf->Output($filename, 'S');
            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }
        return response()->json(["message" => "Verification record not found!"], 404);
    }

    public function freePDF($nin_no)
    {
        return $this->regularPDF($nin_no); 
    }
    public function individualSlip($Verification, $reference)
    {
        Log::info('Generating Individual TIN Slip PDF');
        Log::info('Verification Data: ', $Verification->toArray());

        $modificationData = $Verification->modification_data ?? [];
        $apiResponse = $modificationData['api_response'] ?? [];

        $tinData = [
            'nin' => $Verification->nin ?? '',
            'fName' => $Verification->firstname ?? '',
            'sName' => $Verification->surname ?? '',
            'mName' => $Verification->middlename ?? '',
            'dob' => $Verification->birthdate ?? '',
            'tax_id' => $apiResponse['tax_id'] ?? $apiResponse['tin'] ?? $modificationData['tin'] ?? '',
            'tax_residency' => $apiResponse['tax_residency'] ?? '',
        ];

        $names = html_entity_decode($tinData['fName']) . ' ' . html_entity_decode($tinData['sName']);

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->SetCreator('Abu');
        $pdf->SetAuthor('Zulaiha');
        $pdf->SetTitle($names);
        $pdf->SetSubject('Individual TIN Slip');
        $pdf->SetKeywords('individual tin slip, TCPDF, PHP');
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->AddPage();
        
        $pdf->SetFont('dejavuserifcondensedbi', '', 12);
        $txt = "Please find below your new Individual TIN Slip...";
        $pdf->MultiCell(150, 20, $txt, 0, 'C', false, 1, 35, 20, true, 0, false, true, 0, 'T', false);

        // Fixed image paths using public_path() for consistency
        $bgFront = public_path('assets/images/nrs_bg_front.png.png'); // Double check extension in next step if needed
        $bgBack = public_path('assets/images/nrs_bg_back.png');

        if(file_exists($bgFront)) {
             $pdf->Image($bgFront, 60, 30, 100, 100, 'PNG', '', '', false, 300, '', false, false, 0);
        }
        if(file_exists($bgBack)) {
             $pdf->Image($bgBack, 61.5, 87, 97, 97, 'PNG', '', '', false, 300, '', false, false, 0);
        }

        $style = [
            'border' => false,
            'padding' => 0,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => [255, 255, 255]
        ];

        // Format Given Names
        $givenNames = trim(html_entity_decode($tinData['fName']) . ' ' . html_entity_decode($tinData['mName']));
        $datas = '{TIN: ' . $tinData['tax_id'] . ', NAME: ' . $givenNames . ' ' . html_entity_decode($tinData['sName']) . ', dob: ' . $tinData['dob'] . ', Status:Verified}';
        
        $pdf->write2DBarcode($datas, 'QRCODE,H', 123.5, 67, 23, 18, $style, 'H');

        $sur = html_entity_decode($tinData['sName']);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Text(76.5, 73.5, $sur);

        // Add both First and Middle names
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Text(76.6, 80, $givenNames);

        $dob = $tinData['dob'];
        $newD = strtotime($dob);
        $cdate = date("d M Y", $newD);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Text(76.6, 87, $cdate);

        $tin = $tinData['tax_id'];
        $pdf->setTextColor(0, 0, 0);
        
        // Handle TIN formatting gracefully
        if(strlen($tin) >= 14) {
             $newTin = substr($tin, 0, 4) . " " . substr($tin, 4, 3) . " " . substr($tin, 7);
        } else {
             $newTin = $tin;
        }

        $pdf->SetFont('helvetica', '', 18);
        $pdf->Text(85, 93, $newTin);

        $filename = 'Individual TIN Slip - ' . $reference . '.pdf';
        $pdfContent = $pdf->Output($filename, 'S');

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdfContent));
    }
}
