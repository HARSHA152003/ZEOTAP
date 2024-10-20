<?php
include 'db.php';
include 'Node.php';

function evaluate_rule($json_data, $ast) {
    if ($ast->type == "operator") {
        if ($ast->value == "AND") {
            return evaluate_rule($json_data, $ast->left) && evaluate_rule($json_data, $ast->right);
        } elseif ($ast->value == "OR") {
            return evaluate_rule($json_data, $ast->left) || evaluate_rule($json_data, $ast->right);
        }
    } else {
        // Evaluate condition like "age > 30"
        $condition = str_replace(array_keys($json_data), array_values($json_data), $ast->value);
        return eval("return $condition;");
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rule_id = $_POST['rule_id'];
    $json_data = json_decode($_POST['json_data'], true);

    $stmt = $conn->prepare("SELECT ast FROM rules WHERE id = ?");
    $stmt->bind_param("i", $rule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $ast = unserialize($row['ast']);
    $stmt->close();

    // Evaluate rule
    $result = evaluate_rule($json_data, $ast);
    echo json_encode(["success" => true, "result" => $result]);
}
?>
