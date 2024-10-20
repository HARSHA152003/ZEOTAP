<?php
include 'db.php';
include 'Node.php';

function combine_rules($rule_ids) {
    global $conn;

    $combined_ast = null;
    $previous_ast = null;

    foreach ($rule_ids as $id) {
        $stmt = $conn->prepare("SELECT ast, rule_string FROM rules WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $ast = unserialize($row['ast']);
        $rule_string = $row['rule_string'];

        if ($combined_ast === null) {
            // Initialize the combined AST with the first rule
            $combined_ast = $ast;
        } else {
            // Combine previous AST with the new one using AND
            $combined_ast = new Node("operator", $combined_ast, $ast, "AND");
        }
        $stmt->close();
    }

    // Prepare the new rule string as a representation of the combined rules
    $new_rule_string = "(";
    $new_rule_string .= implode(" AND ", array_map(function($id) {
        global $conn;
        $stmt = $conn->prepare("SELECT rule_string FROM rules WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['rule_string'];
    }, $rule_ids));
    $new_rule_string .= ")";

    return ['ast' => $combined_ast, 'rule_string' => $new_rule_string];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rule_ids = json_decode($_POST['rule_ids']); // Expecting JSON array of rule IDs

    // Combine the rules into a single AST
    $result = combine_rules($rule_ids);

    // Insert the combined rule into the database
    $combined_ast_string = serialize($result['ast']);
    $stmt = $conn->prepare("INSERT INTO rules (rule_string, ast) VALUES (?, ?)");
    $stmt->bind_param("ss", $result['rule_string'], $combined_ast_string);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Rules combined successfully", "rule_id" => $stmt->insert_id]);
    } else {
        echo json_encode(["success" => false, "message" => "Error combining rules"]);
    }
    $stmt->close();
}
?>
