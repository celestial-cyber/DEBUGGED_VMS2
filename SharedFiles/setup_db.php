<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/connection.php';

$sqlFile = __DIR__ . '/setup.sql';
if (!file_exists($sqlFile)) {
	http_response_code(500);
	die('setup.sql not found');
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
	http_response_code(500);
	die('Unable to read setup.sql');
}

$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

$errors = [];
foreach ($statements as $statement) {
	if ($statement === '') continue;
	if (!mysqli_query($conn, $statement . ';')) {
		$errors[] = mysqli_error($conn);
	}
}

if ($errors) {
	echo "Completed with errors (some statements may have succeeded):\n\n";
	echo implode("\n", $errors);
	exit(1);
}

echo "Database setup completed successfully.";
?>

