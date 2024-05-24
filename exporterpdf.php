<?php

require 'vendor/autoload.php'; 

$fichier = 'taches.txt';
$taches = file_get_contents($fichier);

$pdf = new TCPDF();
$pdf->SetMargins(10, 10, 10); 
$pdf->AddPage();

$pdf->SetFont('helvetica', '', 12);
$pdf->Write(0, $taches, '', 0, 'L', true, 0, false, false, 0);

$pdf->Output('taches.pdf', 'D'); 
?>
