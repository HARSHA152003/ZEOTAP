<?php
include 'db.php';
include 'Node.php';

function parse_rule($rule_string) {
    $tokens = tokenize($rule_string);
    return parse_expression($tokens);
}

function tokenize($rule_string) {
    // Regular expression to match operators, parentheses, and operands
    $pattern = '/\s*(=>|<=|==|!=|>|<|\(|\)|AND|OR|[A-Za-z0-9_]+|\'[^\']*\')\s*/';
    preg_match_all($pattern, $rule_string, $matches);
    return $matches[0];
}

function parse_expression(&$tokens) {
    $node = parse_term($tokens);
    while (!empty($tokens) && ($tokens[0] == "OR")) {
        $operator = array_shift($tokens);
        $right = parse_term($tokens);
        $node = new Node("operator", $node, $right, $operator);
    }
    return $node;
}

function parse_term(&$tokens) {
    $node = parse_factor($tokens);
    while (!empty($tokens) && ($tokens[0] == "AND")) {
        $operator = array_shift($tokens);
        $right = parse_factor($tokens);
        $node = new Node("operator", $node, $right, $operator);
    }
    return $node;
}

function parse_factor(&$tokens) {
    $token = array_shift($tokens);
    if ($token == '(') {
        $node = parse_expression($tokens);
        array_shift($tokens); // Remove closing ')'
        return $node;
    }
    return parse_condition($token, $tokens);
}

function parse_condition($left_operand, &$tokens) {
    $operator = array_shift($tokens);
    $right_operand = array_shift($tokens);
    return new Node("operand", null, null, "$left_operand $operator $right_operand");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rule_id = $_POST['rule_id'];
    $rule_string = $_POST['rule'];

    // Create AST from rule string
    $ast = parse_rule($rule_string);
    $ast_string = serialize($ast);

    // Update rule and AST in database
    $stmt = $conn->prepare("UPDATE rules SET rule_string = ?, ast = ? WHERE id = ?");
    $stmt->bind_param("ssi", $rule_string, $ast_string, $rule_id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Rule modified successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error modifying rule"]);
    }
    $stmt->close();
}

// Function to retrieve the rule by ID
function get_rule($rule_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT rule_string FROM rules WHERE id = ?");
    $stmt->bind_param("i", $rule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['rule_string'];
    }
    return null;
}

// Handle GET request to fetch rule
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['rule_id'])) {
    $rule_id = $_GET['rule_id'];
    $rule_string = get_rule($rule_id);
    if ($rule_string !== null) {
        echo json_encode(["success" => true, "rule_string" => $rule_string]);
    } else {
        echo json_encode(["success" => false, "message" => "Rule not found"]);
    }
}
?>
