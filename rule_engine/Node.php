<?php
// Node.php
class Node {
    public $type;  // "operator" (AND/OR) or "operand" (condition)
    public $left;
    public $right;
    public $value;

    public function __construct($type, $left = null, $right = null, $value = null) {
        $this->type = $type;
        $this->left = $left;
        $this->right = $right;
        $this->value = $value;
    }
}
?>
