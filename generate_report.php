<?php
require_once 'tcpdf/tcpdf.php'; // correct path to classic TCPDF
include 'db.php';

$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM event_applications WHERE id = ? AND date < CURDATE()");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($event = $result->fetch_assoc()) {
    $pdf = new TCPDF();
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $event['title'], 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 12);
    $pdf->MultiCell(0, 10, $event['description']);

    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Custom Summary:', 0, 1);
    $pdf->MultiCell(0, 10, 'This event was held on ' . $event['date'] . ' and organized by our verified team.');

    foreach (['image1', 'image2', 'image3'] as $img) {
        if (!empty($event[$img]) && file_exists('../uploads/' . $event[$img])) {
            $pdf->AddPage();
            $pdf->Image('../uploads/' . $event[$img], 15, 40, 180);
        }
    }

    $pdf->Output('event_report.pdf', 'I');
} else {
    echo "Event not found or not a past event.";
}
