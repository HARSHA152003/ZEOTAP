<?php
include 'db.php';

$result = $conn->query("SELECT id, rule_string FROM rules");
$rules = [];
while ($row = $result->fetch_assoc()) {
    $rules[] = $row;
}
echo json_encode($rules);
?>
