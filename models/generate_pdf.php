<?php
require_once 'dompdf/autoload.inc.php';

// reference the Dompdf namespace
use Dompdf\Dompdf;

$user_name = isset($_POST['username']) ? $_POST['username'] : 'username';
$obj = array('USER_NAME' => $user_name);
$html = "";
// $group_array = array_reverse($group_array);
$html = file_get_contents("dompdf/certificate.html");	// need to set absolute url in the server
foreach ($obj as $key => $val)
{
    $html = str_replace("[" . $key . "]", $val, $html);
}
// instantiate and use the dompdf class
$dompdf = new Dompdf();
$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
$dompdf->render();

$output = $dompdf->output();
$milliseconds = round(microtime(true) * 1000);
$file_name = "certificate_" . $milliseconds . ".pdf";
file_put_contents('../models/certificates/'.$file_name, $output);

echo json_encode(array('file_name' => $file_name));
// Output the generated PDF to Browser
// $dompdf->stream();
?>