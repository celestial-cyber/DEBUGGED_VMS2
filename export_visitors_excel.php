<?php
session_start();
include 'connection.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ================= Get Filters =================
$department = $_GET['department'] ?? '';
$year       = $_GET['year'] ?? '';
$gender     = $_GET['gender'] ?? '';
$eventName  = $_GET['event'] ?? '';

// ================= Build Query =================
$where = "WHERE 1=1";
if ($department != '') $where .= " AND department='" . mysqli_real_escape_string($conn, $department) . "'";
if ($year != '')       $where .= " AND year_of_graduation='" . mysqli_real_escape_string($conn, $year) . "'";
if ($gender != '')     $where .= " AND gender='" . mysqli_real_escape_string($conn, $gender) . "'";
if ($eventName != '')  {
    $ename = mysqli_real_escape_string($conn, $eventName);
    $erow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT event_id FROM tbl_events WHERE event_name='$ename' LIMIT 1"));
    if ($erow) { $where .= " AND event_id=" . (int)$erow['event_id']; }
}

$query = "SELECT full_name, roll_number, department, year_of_graduation, gender, in_time, out_time 
          FROM tbl_visitors $where ORDER BY in_time DESC";
$result = mysqli_query($conn, $query);

// ================= Spreadsheet =================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$sheet->setCellValue('A1', 'Full Name');
$sheet->setCellValue('B1', 'Roll Number');
$sheet->setCellValue('C1', 'Department');
$sheet->setCellValue('D1', 'Year of Graduation');
$sheet->setCellValue('E1', 'Gender');
$sheet->setCellValue('F1', 'In Time');
$sheet->setCellValue('G1', 'Out Time');

// Data
$rowCount = 2;
while ($row = mysqli_fetch_assoc($result)) {
    $sheet->setCellValue("A$rowCount", $row['full_name']);
    $sheet->setCellValue("B$rowCount", $row['roll_number']);
    $sheet->setCellValue("C$rowCount", $row['department']);
    $sheet->setCellValue("D$rowCount", $row['year_of_graduation']);
    $sheet->setCellValue("E$rowCount", $row['gender']);
    $sheet->setCellValue("F$rowCount", $row['in_time']);
    $sheet->setCellValue("G$rowCount", $row['out_time']);
    $rowCount++;
}

// ================= Output Excel =================
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="visitors_report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
