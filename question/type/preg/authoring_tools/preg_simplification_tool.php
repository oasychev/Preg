<?php
/**
 * Defines simplification tool.
 *
 * @copyright &copy; 2012 Oleg Sychev, Volgograd State Technical University
 * @author Terechov Grigory <grvlter@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package qtype_preg
 */

require_once($CFG->dirroot . '/question/type/preg/authoring_tools/preg_authoring_tool.php');

class qtype_preg_simplification_tool_options extends qtype_preg_handling_options {
    public $is_check_equivalences = true;
    public $is_check_errors = true;
    public $is_check_tips = true;
    public $problem_ids = array();
    public $problem_type = -2;
    public $indfirst = -2;
    public $indlast = -2;
}

class qtype_preg_simplification_tool extends qtype_preg_authoring_tool {

    /** Array with root ids of problem subtree */
    private $problem_ids = array();
    /** Number type of problem */
    private $problem_type = -2;
    /** First index of something in regex string (absolute positioning). */
    private $indfirst = -2;
    /** Last index of something in regex string (absolute positioning). */
    private $indlast = -2;

    public function __construct($regex = null, $options = null) {
        parent::__construct($regex, $options);
    }

    /**
     * Overloaded from qtype_preg_regex_handler.
     */
    public function name() {
        return 'simplification_tool';
    }

    /**
     * Overloaded from qtype_preg_regex_handler.
     */
    protected function node_infix() {
        return 'simplification';
    }

    /**
     * Overloaded from qtype_preg_regex_handler.
     */
    protected function get_engine_node_name($nodetype, $nodesubtype) {
        return parent::get_engine_node_name($nodetype, $nodesubtype);
    }

    /**
     * Overloaded from qtype_preg_regex_handler.
     */
    protected function is_preg_node_acceptable($pregnode) {
        return true;
    }

    /**
     * Overloaded from qtype_preg_authoring_tool.
     */
    public function json_key() {
        return 'simplification';
    }

    /**
     * Overloaded from qtype_preg_authoring_tool.
     */
    public function generate_html() {
        if ($this->regex->string() == '') {
            return $this->data_for_empty_regex();
        } else if ($this->errors_exist() || $this->get_ast_root() == null) {
            return $this->data_for_unaccepted_regex();
        }
        return $this->data_for_accepted_regex();
    }

    /**
     * Overloaded from qtype_preg_authoring_tool.
     */
    public function data_for_accepted_regex() {
        $data = array();
        $data['errors'] = $this->get_errors_description();
        $data['tips'] = $this->get_tips_description();
        $data['equivalences'] = $this->get_equivalences_description();

        return $data;
    }



    /**
     * Get array of errors in regex.
     */
    protected function get_errors_description() {
        $errors = array();
//        if ($this->options->is_check_errors == true) {
//            //to do something
//        }
        return $errors;

//        return array(array('problem' => 'Описание ошибки', 'solve' => 'Подробное описание информации'));
    }

    /**
     * Get array of tips in regex.
     */
    protected function get_tips_description() {
        $tips = array();

        if ($this->options->is_check_tips == true) {
            $i = 0;
            $result = $this->space_charset();
            if ($result != array()) {
                $tips[$i] = array();
                $tips[$i] += $result;
                ++$i;
                $this->problem_ids = array();
            }

            $result = $this->subpattern_without_backref();
            if ($result != array()) {
                $tips[$i] = array();
                $tips[$i] += $result;
                ++$i;
                $this->problem_ids = array();
            }

            $result = $this->exact_match();
            if ($result != array()) {
                $tips[$i] = array();
                $tips[$i] += $result;
                ++$i;
                $this->problem_ids = array();
            }
        }
        return $tips;
    }

    /**
     * Get array of equivalences in regex.
     */
    protected function get_equivalences_description() {
        $equivalences = array();

        if ($this->options->is_check_equivalences == true) {
            $i = 0;
            $result = $this->repeated_assertions();
            if ($result != array()) {
                $equivalences[$i] = array();
                $equivalences[$i] += $result;
                ++$i;
                $this->problem_ids = array();
            }

            $result = $this->grouping_node();
            if ($result != array()) {
                $equivalences[$i] = array();
                $equivalences[$i] += $result;
                ++$i;
                $this->problem_ids = array();
            }

            $result = $this->subpattern_node();
            if ($result != array()) {
                $equivalences[$i] = array();
                $equivalences[$i] += $result;
                ++$i;
                $this->problem_ids = array();
            }

            $result = $this->single_charset_node();
            if ($result != array()) {
                $equivalences[$i] = array();
                $equivalences[$i] += $result;
                ++$i;
                $this->problem_ids = array();
            }

            $result = $this->single_alternative_node();
            if ($result != array()) {
                $equivalences[$i] = array();
                $equivalences[$i] += $result;
                ++$i;
                $this->problem_ids = array();
            }

            $result = $this->quant_node();
            if ($result != array()) {
                $equivalences[$i] = array();
                $equivalences[$i] += $result;
                ++$i;
                $this->problem_ids = array();
            }

            $result = $this->common_subexpressions();
            if ($result != array()) {
                $equivalences[$i] = array();
                $equivalences[$i] += $result;
                ++$i;
                $this->problem_ids = array();
            }
        }

        return $equivalences;
    }



    /**
     * Check repeated assertions.
     */
    protected function repeated_assertions() {
        $equivalences = array();

        if ($this->search_repeated_assertions($this->get_dst_root())) {
            $equivalences['problem'] = htmlspecialchars(get_string('simplification_equivalences_short_1', 'qtype_preg'));
            $equivalences['solve'] = htmlspecialchars(get_string('simplification_equivalences_full_1', 'qtype_preg'));
            $equivalences['problem_ids'] = $this->problem_ids;
            $equivalences['problem_type'] = $this->problem_type;
            $equivalences['problem_indfirst'] = $this->indfirst;
            $equivalences['problem_indlast'] = $this->indlast;
        }

        return $equivalences;
    }

    /**
     * Search repeated assertions in tree.
     */
    private function search_repeated_assertions($node) {
        if ($node->type == qtype_preg_node::TYPE_LEAF_ASSERT
            && ($node->subtype == qtype_preg_leaf_assert::SUBTYPE_CIRCUMFLEX || $node->subtype == qtype_preg_leaf_assert::SUBTYPE_DOLLAR)) {
            if ($this->is_find_assert == true) {
                $this->problem_ids[] = $node->id;
                $this->problem_type = 1;
                $this->indfirst = $node->position->indfirst;
                $this->indlast = $node->position->indlast;
                return true;
            } else {
                $this->is_find_assert = true;
            }
        } else if ($node->type == qtype_preg_node::TYPE_LEAF_CHARSET
                   || $node->type == qtype_preg_node::TYPE_LEAF_META
                   || $node->type == qtype_preg_node::TYPE_LEAF_BACKREF
                   || $node->type == qtype_preg_node::TYPE_LEAF_SUBEXPR_CALL
                   || $node->type == qtype_preg_node::TYPE_LEAF_TEMPLATE
                   || $node->type == qtype_preg_node::TYPE_LEAF_CONTROL
                   || $node->type == qtype_preg_node::TYPE_LEAF_OPTIONS
                   || $node->type == qtype_preg_node::TYPE_LEAF_COMPLEX_ASSERT) {
            $this->is_find_assert = false;
        }
        if ($this->is_operator($node)) {
            foreach ($node->operands as $operand) {
                if ($this->search_repeated_assertions($operand)) {
                    return true;
                }
            }
        }

        $this->problem_type = -2;
        $this->indfirst = -2;
        $this->indlast = -2;
        return false;
    }



    /**
     * Check empty grouping node.
     */
    public function grouping_node() {
        $equivalences = array();

        if ($this->search_grouping_node($this->get_dst_root())) {
            $equivalences['problem'] = htmlspecialchars(get_string('simplification_equivalences_short_2', 'qtype_preg'));
            $equivalences['solve'] = htmlspecialchars(get_string('simplification_equivalences_full_2', 'qtype_preg'));
            $equivalences['problem_ids'] = $this->problem_ids;
            $equivalences['problem_type'] = $this->problem_type;
            $equivalences['problem_indfirst'] = $this->indfirst;
            $equivalences['problem_indlast'] = $this->indlast;
        }

        return $equivalences;
    }

    /**
     * Search repeated assertions assertions in tree.
     */
    private function search_grouping_node($node) {
        if ($node->type == qtype_preg_node::TYPE_NODE_SUBEXPR
            && $node->subtype == qtype_preg_node_subexpr::SUBTYPE_GROUPING) {
            if ($node->operands[0]->type == qtype_preg_node::TYPE_LEAF_META
                && $node->operands[0]->subtype == qtype_preg_leaf_meta::SUBTYPE_EMPTY) {
                $this->problem_ids[] = $node->id;
                $this->problem_type = 2;
                $this->indfirst = $node->position->indfirst;
                $this->indlast = $node->position->indlast;
                return true;
            } else {
                if ($this->check_other_grouping_node($node->operands[0])) {
                    $this->problem_ids[] = $node->id;
                    $this->problem_type = 2;
                    $this->indfirst = $node->position->indfirst;
                    $this->indlast = $node->position->indlast;
                    return true;
                }
            }
        }
        if ($this->is_operator($node)) {
            foreach ($node->operands as $operand) {
                if ($this->search_grouping_node($operand)) {
                    return true;
                }
            }
        }

        $this->problem_type = -2;
        $this->indfirst = -2;
        $this->indlast = -2;
        return false;
    }

    /**
     * Check included empty grouping node in empty grouping node.
     */
    private function check_other_grouping_node($node) {
        if ($node->type == qtype_preg_node::TYPE_NODE_SUBEXPR
            && $node->subtype == qtype_preg_node_subexpr::SUBTYPE_GROUPING) {
            if ($node->operands[0]->type == qtype_preg_node::TYPE_LEAF_META
                && $node->operands[0]->subtype == qtype_preg_leaf_meta::SUBTYPE_EMPTY) {
                return true;
            } else {
                return $this->check_other_grouping_node($node->operands[0]);
            }
        }
        return false;
    }



    /**
     * Check empty subpattern node.
     */
    public function subpattern_node() {
        $equivalences = array();

        if ($this->search_subpattern_node($this->get_dst_root())) {
            $equivalences['problem'] = htmlspecialchars(get_string('simplification_equivalences_short_3', 'qtype_preg'));
            $equivalences['solve'] = htmlspecialchars(get_string('simplification_equivalences_full_3', 'qtype_preg'));
            $equivalences['problem_ids'] = $this->problem_ids;
            $equivalences['problem_type'] = $this->problem_type;
            $equivalences['problem_indfirst'] = $this->indfirst;
            $equivalences['problem_indlast'] = $this->indlast;
        }

        return $equivalences;
    }

    /**
     * Search empty subpattern node.
     */
    private function search_subpattern_node($node) {
        if ($node->type == qtype_preg_node::TYPE_NODE_SUBEXPR && $node->subtype == qtype_preg_node_subexpr::SUBTYPE_SUBEXPR) {
            if (!$this->check_backref_to_subexpr($this->get_dst_root(), $node->number)) {
                if ($node->operands[0]->type == qtype_preg_node::TYPE_LEAF_META && $node->operands[0]->subtype == qtype_preg_leaf_meta::SUBTYPE_EMPTY) {
                    $this->problem_ids[] = $node->id;
                    $this->problem_type = 3;
                    $this->indfirst = $node->position->indfirst;
                    $this->indlast = $node->position->indlast;
                    return true;
                } else {
                    if ($this->check_other_subpattern_node($node->operands[0])) {
                        $this->problem_ids[] = $node->id;
                        $this->problem_type = 2;
                        $this->indfirst = $node->position->indfirst;
                        $this->indlast = $node->position->indlast;
                        return true;
                    }
                }
            }
        }
        if ($this->is_operator($node)) {
            foreach ($node->operands as $operand) {
                if ($this->search_subpattern_node($operand)) {
                    return true;
                }
            }
        }

        $this->problem_type = -2;
        $this->indfirst = -2;
        $this->indlast = -2;
        return false;
    }

    /**
     * Check backreference to subexpression.
     */
    private function check_backref_to_subexpr($node, $number) {
        if ($node->type == qtype_preg_node::TYPE_LEAF_BACKREF && $node->subtype == qtype_preg_node::TYPE_LEAF_BACKREF && $node->number == $number) {
            return true;
        }
        if ($this->is_operator($node)) {
            foreach ($node->operands as $operand) {
                if ($this->check_backref_to_subexpr($operand, $number)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check included empty subpattern node in empty subpattern node.
     */
    private function check_other_subpattern_node($node) {
        if ($node->type == qtype_preg_node::TYPE_NODE_SUBEXPR
            && $node->subtype == qtype_preg_node_subexpr::SUBTYPE_SUBEXPR) {
            if ($node->operands[0]->type == qtype_preg_node::TYPE_LEAF_META
                && $node->operands[0]->subtype == qtype_preg_leaf_meta::SUBTYPE_EMPTY) {
                if (!$this->check_backref_to_subexpr($this->get_dst_root(), $node->number)) {
                    return true;
                }
            } else {
                return $this->check_other_grouping_node($node->operands[0]);
            }
        }
        return false;
    }



    /**
     * Check common subexpressions in tree.
     */
    public function common_subexpressions() {
        $equivalences = array();

        if ($this->search_common_subexpressions($this->get_dst_root())) {
            $equivalences['problem'] = htmlspecialchars(get_string('simplification_equivalences_short_4', 'qtype_preg'));
            $equivalences['solve'] = htmlspecialchars(get_string('simplification_equivalences_full_4', 'qtype_preg'));
            $equivalences['problem_ids'] = $this->problem_ids;
            $equivalences['problem_type'] = $this->problem_type;
            $equivalences['problem_indfirst'] = $this->indfirst;
            $equivalences['problem_indlast'] = $this->indlast;
        }

        return $equivalences;
    }

    /**
     * Search common subexpressions in tree.
     */
    private function search_common_subexpressions($tree_root, &$leafs = null) {
        if ($leafs == NULL) {
            $leafs = array();
            $leafs[0] = array();
            array_push($leafs[0], $tree_root);

            $this->normalization($tree_root);
        }

        if ($this->is_operator($tree_root)) {
            foreach ($tree_root->operands as $operand) {
                if ($this->search_subexpr($leafs, $operand, $tree_root)) {
                    return true;
                }
                $leafs[count($leafs)] = array();
                for ($i = 0; $i < count($leafs); $i++) {
                    array_push($leafs[$i], $operand);
                }
                if ($this->search_common_subexpressions($operand, $leafs)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Search suitable common subexpressions in tree.
     */
    private function search_subexpr($leafs, $current_leaf, $tree_root) {
        foreach ($leafs as $leaf) {
            $tmp_root = $this->get_parent_node($this->get_dst_root(), $leaf[0]->id);
            if ($leaf[0]->is_equal($current_leaf, null) && $this->compare_parent_nodes($tmp_root, $tree_root)) {
                if($this->get_right_set_of_leafs($leaf, $current_leaf, $tree_root)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Trying to get a set of equivalent $leafs nodes from $current_leaf.
     */
    private function get_right_set_of_leafs($leafs, $current_leaf, $tree_root) {
        $is_fount = false;
        $right_leafs = $this->get_right_leafs($tree_root, $current_leaf, count($leafs), $is_fount);
        $right_leafs_tmp = $right_leafs;
        if ($this->leafs_compare($leafs, $right_leafs)) {
            $this->problem_ids[] = count($leafs);//length
            $this->problem_ids[] = $leafs[0]->id;
            $is_found = true;
            while ($is_found) {
                $this->problem_ids[] = $right_leafs_tmp[0]->id;
                $right_leafs_tmp = $right_leafs;
                $is_fount1 = false;
                $next_leafs = $this->get_right_leafs($tree_root, $right_leafs_tmp[count($right_leafs_tmp) - 1], 2, $is_fount1);
                $next_leaf = null;
                if (count($next_leafs) > 1) {
                    $next_leaf = $next_leafs[1];
                }
                $is_fount2 = false;
                $right_leafs = $this->get_right_leafs($tree_root, $next_leaf, count($leafs), $is_fount2);
                $is_found = $this->leafs_compare($leafs, $right_leafs);
            }
            $this->problem_type = 4;
            $this->get_subexpression_regex_position_for_nodes($leafs, $right_leafs_tmp);
            return true;
        }

        return false;
    }

    /**
     * Get parent of node.
     * TODO: delete
     */
    private function get_parent_node($tree_root, $node_id) {
        $local_root = null;
        if ($this->is_operator($tree_root)) {
            foreach ($tree_root->operands as $operand) {
                if ($operand->id == $node_id) {
                    return $tree_root;
                }
                $local_root = $this->get_parent_node($operand, $node_id);
                if ($local_root !== null) {
                    return $local_root;
                }
            }
        }
        return $local_root;
    }

    /**
     * Trying to get a set of equivalent $leafs nodes from $current_leaf.
     * TODO: get N nodes from subtree where $current_leaf is root
     */
    private function get_right_leafs($tree_root, $current_leaf, $size, &$is_found, &$leafs = null) {
        if ($current_leaf == NULL) {
            return array();
        }
        if ($leafs == NULL) {
            $leafs = array();
        }

        if ($this->is_operator($tree_root)) {
            foreach ($tree_root->operands as $operand) {
                if ($current_leaf->id == $operand->id) {
                    $is_found = true;
                }

                if ($is_found && count($leafs) < $size) {
                    array_push($leafs, $operand);
                    if (count($leafs) >= $size) {
                        return $leafs;
                    }
                }

                $this->get_right_leafs($operand, $current_leaf, $size, $is_found, $leafs);
            }
        }

        return $leafs;
    }

    /**
     * Compare two arrays with nodes
     */
    private function leafs_compare($leafs1, $leafs2) {
        if (count($leafs1) != count($leafs2)) {
            return false;
        }

        for ($i = 0; $i < count($leafs1); $i++) {
            if (!$leafs1[$i]->is_equal($leafs2[$i], null)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Compare two nodes who are parents
     */
    private function compare_parent_nodes($local_root1, $local_root2) {
        if ($local_root1 != null && $local_root2 != null && $local_root1->is_equal($local_root2, null)) {
            return $this->is_can_local_root($local_root1) && $this->is_can_local_root($local_root2);
        }
        return false;
    }

    /**
     * Whether the node is a parent for common sybexpression
     */
    private function is_can_parent_node($local_root) {
        return !($local_root->type == qtype_preg_node::TYPE_NODE_ALT);
    }

    /**
     * Whether the node is operator
     */
    private function is_operator($node) {
        return !($node->type == qtype_preg_node::TYPE_LEAF_CHARSET
            || $node->type == qtype_preg_node::TYPE_LEAF_ASSERT
            || $node->type == qtype_preg_node::TYPE_LEAF_META
            || $node->type == qtype_preg_node::TYPE_LEAF_BACKREF
            || $node->type == qtype_preg_node::TYPE_LEAF_SUBEXPR_CALL
            || $node->type == qtype_preg_node::TYPE_LEAF_TEMPLATE
            || $node->type == qtype_preg_node::TYPE_LEAF_CONTROL
            || $node->type == qtype_preg_node::TYPE_LEAF_OPTIONS
            || $node->type == qtype_preg_node::TYPE_LEAF_COMPLEX_ASSERT);
    }

    /**
     * Find and sort leafs for associative-commutative operators
     */
    private function associative_commutative_operator_sort($tree_root){
        if ($this->is_operator($tree_root)) {
            if ($this->is_associative_commutative_operator($tree_root)) {
                $this->sort_associative_commutative_operator_childs($tree_root);
            }
            foreach ($tree_root->operands as $operand) {
                if ($this->is_associative_commutative_operator($operand)) {
                    $this->sort_associative_commutative_operator_childs($operand);
                }

                $this->associative_commutative_operator_sort($operand);
            }
        }
    }

    /**
     * Sort leafs for associative-commutative operators
     */
    private function sort_associative_commutative_operator_childs(&$operator) {
        for ($j = 0; $j < count($operator->operands) - 1; $j++) {
            for ($i = 0; $i < count($operator->operands) - $j - 1; $i++) {
                if ($operator->operands[$i]->get_regex_string() > $operator->operands[$i+1]->get_regex_string()) {
                    $b = $operator->operands[$i];
                    $operator->operands[$i] = $operator->operands[$i + 1];
                    $operator->operands[$i + 1] = $b;
                }
            }
        }
    }

    /**
     * Whether the node is associative commutative operator
     */
    private function is_associative_commutative_operator($node) {
        return $node->type == qtype_preg_node::TYPE_NODE_ALT;
    }

    /**
     * Tree normalization
     */
    protected function normalization($tree_root) {
        $problem_exist = true;
//        while($problem_exist) {
            if ($this->search_single_charset_node($this->get_dst_root())) {
                $this->remove_square_brackets_from_charset($this->get_dst_root(), $this->problem_ids[0]);
            } else {
                $problem_exist = false;
            }
//        }
        $problem_exist = true;
        while($problem_exist) {
            if ($this->search_grouping_node($this->get_dst_root())) {
                $this->remove_subtree($this->get_dst_root(), $this->problem_ids[0]);
            } else {
                $problem_exist = false;
            }
        }

        $this->associative_commutative_operator_sort($this->get_dst_root());

        $this->problem_ids = array();
    }


    /**
     * Elimination of common subexpressions
     */
    private function fold_common_subexpressions($tree_root) {
        if ($tree_root->id != $this->options->problem_ids[1]) {
            if ($this->is_operator($tree_root)) {
                foreach ($tree_root->operands as $operand) {
                    if ($operand->id == $this->options->problem_ids[1]) {
                        $this->tree_folding($operand, $tree_root);
                        return true;
                    }
                    if ($this->fold_common_subexpressions($operand)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Generate new fixed regex
     */
    private function tree_folding($current_leaf, $parent_node) {
        // Old regex string
        $regex_string = $this->get_regex_string();

        // Calculate quantifier borders
        $counts = $this->subexpressions_repeats($parent_node, $current_leaf);

        // Create new quantifier with needed borders
        $qu = new qtype_preg_node_finite_quant($counts[0], $counts[1]);
        $text = $this->get_quant_text_from_borders($counts[0], $counts[1]);
        $qu->set_user_info(null, array(new qtype_preg_userinscription($text)));

        // Current operand is operand of quantifier node
        if ($this->options->problem_ids[0] > 1 && $current_leaf->type != qtype_preg_node::TYPE_NODE_SUBEXPR) {
            $se = new qtype_preg_node_subexpr(qtype_preg_node_subexpr::SUBTYPE_GROUPING, -1, '', false);
            $se->set_user_info(null, array(new qtype_preg_userinscription('(?:...)')));

            // Add needed nodes to grouping node
            if (!$this->is_operator($current_leaf)) {
                // Get right leafs from current node
                $right_leafs = $this->get_right_leafs($this->get_dst_root(), $current_leaf, $this->options->problem_ids[0]);
                // Add this nodes while node in not operator
                foreach ($right_leafs as $rleaf) {
                    if (!$this->is_operator($rleaf)) {
                        $se->operands[] = $rleaf;
                    } else {
                        break;
                    }
                }
            } else {
                $se->operands[] = $current_leaf;
            }

            $qu->operands[] = $se;
        } else {
            $qu->operands[] = $current_leaf;
        }

        // New part of regex string
        $new_regex_string_part = $qu->get_regex_string();

        // New fixed regex
        return $this->regex_from_tree = substr_replace($regex_string, $new_regex_string_part, $this->options->indfirst,
                                                       $this->options->indlast - $this->options->indfirst + 1);
    }

    /**
     * Calculate repeats of subexpression
     */
    private function subexpressions_repeats($current_root, $nodes) {
        $counts = array(0,0);
//        foreach ($nodes as $node) {
//            $tmp_counts = $this->subexpression_repeats($current_root, $node);
//            $counts[0] += $tmp_counts[0];
//            $counts[1] += $tmp_counts[1];
//        }
        $tmp_count = count($this->options->problem_ids) -1;
        $counts[0] += $tmp_count;
        $counts[1] += $tmp_count;
        return $counts;
    }

    private function subexpression_repeats($current_root, $node) {
        return array(1,1);
    }

    /**
     * Get subexpression position in regex string
     * TODO: in these two subexpressions should always be one parent node with positions
     */
    private function get_subexpression_regex_position_for_node($leaf1, $leaf2) {
        $this->indfirst = (($leaf1->position->indfirst < $leaf2->position->indfirst) ? $leaf1->position->indfirst : $leaf2->position->indfirst);
        $this->indlast = (($leaf1->position->indlast > $leaf2->position->indlast) ? $leaf1->position->indlast : $leaf2->position->indlast);
    }

    /**
     * Get subexpression position in regex string
     */
    private function get_subexpression_regex_position_for_nodes($leafs1, $leafs2) {
        $this->indfirst = $leafs1[0]->position->indfirst;
        $this->indlast = $leafs2[count($leafs2)-1]->position->indlast;
        foreach($leafs1 as $leaf) {
            if ($leaf->position->indfirst < $this->indfirst){
                $this->indfirst = $leaf->position->indfirst;
            }
            if ($leaf->position->indlast > $this->indlast){
                $this->indlast = $leaf->position->indlast;
            }
        }

        foreach($leafs2 as $leaf) {
            if ($leaf->position->indfirst < $this->indfirst){
                $this->indfirst = $leaf->position->indfirst;
            }
            if ($leaf->position->indlast > $this->indlast){
                $this->indlast = $leaf->position->indlast;
            }
        }
    }

    /**
     * Get quantifier regex text from borders
     */
    private function get_quant_text_from_borders($left_border, $right_border) {
        return '{' . $left_border . ($left_border == $right_border ? '' : ',' . $right_border) . '}';
    }




    /**
     * Check charset node with one character.
     */
    public function single_charset_node() {
        $equivalences = array();

        if ($this->search_single_charset_node($this->get_dst_root())) {
            $equivalences['problem'] = htmlspecialchars(get_string('simplification_equivalences_short_5', 'qtype_preg'));
            $equivalences['solve'] = htmlspecialchars(get_string('simplification_equivalences_full_5', 'qtype_preg'));
            $equivalences['problem_ids'] = $this->problem_ids;
            $equivalences['problem_type'] = $this->problem_type;
            $equivalences['problem_indfirst'] = $this->indfirst;
            $equivalences['problem_indlast'] = $this->indlast;
        }

        return $equivalences;
    }

    /**
     * Search charset node with one character.
     */
    private function search_single_charset_node($node) {
        if ($node->type == qtype_preg_node::TYPE_LEAF_CHARSET && $node->subtype == NULL) {
            if (($node->is_single_character() || $this->check_many_charset_node($node)) && !$node->negative) {
                $this->problem_ids[] = $node->id;
                $this->problem_type = 5;
                $this->indfirst = $node->position->indfirst;
                $this->indlast = $node->position->indlast;
                return true;
            }
        }
        if ($this->is_operator($node)) {
            foreach ($node->operands as $operand) {
                if ($this->search_single_charset_node($operand)) {
                    return true;
                }
            }
        }

        $this->problem_type = -2;
        $this->indfirst = -2;
        $this->indlast = -2;
        return false;
    }

    /**
     * Check charset node with two and more same characters
     */
    private function check_many_charset_node($node) {
        if (count($node->userinscription) > 1) {
            $symbol = $node->userinscription[1]->data;
            for ($i = 2; $i < count($node->userinscription) - 1; ++$i) {
                if ($node->userinscription[$i]->data !== $symbol) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }



    /**
     * Check alternative node with only charsets operands with one character
     */
    public function single_alternative_node() {
        $equivalences = array();

        if ($this->search_single_alternative_node($this->get_dst_root())) {
            $equivalences['problem'] = htmlspecialchars(get_string('simplification_equivalences_short_6', 'qtype_preg'));
            $equivalences['solve'] = htmlspecialchars(get_string('simplification_equivalences_full_6', 'qtype_preg'));
            $equivalences['problem_ids'] = $this->problem_ids;
            $equivalences['problem_type'] = $this->problem_type;
            $equivalences['problem_indfirst'] = $this->indfirst;
            $equivalences['problem_indlast'] = $this->indlast;
        }

        return $equivalences;
    }

    /**
     * Search alternative node with only charsets operands with one character
     */
    private function search_single_alternative_node($node) {
        if ($node->type == qtype_preg_node::TYPE_NODE_ALT) {
            if ($this->is_single_alternative($node)) {
                $this->problem_ids[] = $node->id;
                $this->problem_type = 6;
                $this->indfirst = $node->position->indfirst;
                $this->indlast = $node->position->indlast;
                return true;
            }
        }
        if ($this->is_operator($node)) {
            foreach ($node->operands as $operand) {
                if ($this->search_single_alternative_node($operand)) {
                    return true;
                }
            }
        }

        $this->problem_type = -2;
        $this->indfirst = -2;
        $this->indlast = -2;
        return false;
    }

    /**
     * Check found alternative node with only charsets operands with one character
     */
    private function is_single_alternative($node) {
        foreach ($node->operands as $operand) {
            if ($operand->type == qtype_preg_node::TYPE_LEAF_CHARSET) {
                if (!(($operand->is_single_character() || $this->check_many_charset_node($operand)
                      || $operand->subtype == qtype_preg_charset_flag::TYPE_FLAG) && !$operand->negative)) {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }


    /**
     * Check quantifier node who can convert to short quantifier
     */
    public function quant_node() {
        $equivalences = array();

        if ($this->search_quant_node($this->get_dst_root())) {
            $equivalences['problem'] = htmlspecialchars(get_string('simplification_equivalences_short_11', 'qtype_preg'));
            $equivalences['solve'] = htmlspecialchars(get_string('simplification_equivalences_full_11', 'qtype_preg'));
            $equivalences['problem_ids'] = $this->problem_ids;
            $equivalences['problem_type'] = $this->problem_type;
            $equivalences['problem_indfirst'] = $this->indfirst;
            $equivalences['problem_indlast'] = $this->indlast;
        }

        return $equivalences;
    }

    /**
     * Search quantifier node who can convert to short quantifier
     */
    private function search_quant_node($node) {
        if ($node->type == qtype_preg_node::TYPE_NODE_FINITE_QUANT || $node->type == qtype_preg_node::TYPE_NODE_INFINITE_QUANT) {
            if ($this->is_simple_quant_node($node)) {
                $this->problem_ids[] = $node->id;
                $this->problem_type = 11;
                $this->indfirst = $node->position->indfirst;
                $this->indlast = $node->position->indlast;
                return true;
            }
        }
        if ($this->is_operator($node)) {
            foreach ($node->operands as $operand) {
                if ($this->search_quant_node($operand)) {
                    return true;
                }
            }
        }

        $this->problem_type = -2;
        $this->indfirst = -2;
        $this->indlast = -2;
        return false;
    }

    /**
     * Check found quantifier node who can convert to short quantifier
     */
    private function is_simple_quant_node($node) {
        if ($node->greedy) {
            if ($node->type == qtype_preg_node::TYPE_NODE_INFINITE_QUANT) {
                return (($node->leftborder === 0 && $node->userinscription[0]->data !== '*')
                        || ($node->leftborder === 1 && $node->userinscription[0]->data !== '+'));
            } else if ($node->type == qtype_preg_node::TYPE_NODE_FINITE_QUANT) {
                return ($node->leftborder === 0 && $node->rightborder === 1 && $node->userinscription[0]->data !== '?');
            }
        }
        return false;
    }



    //--- tips ---
    /* The 1st rule */
    public function space_charset() {
        $equivalences = array();

        if ($this->search_space_charset($this->get_dst_root())) {
            $equivalences['problem'] = htmlspecialchars(get_string('simplification_tips_short_1', 'qtype_preg'));
            $equivalences['solve'] = htmlspecialchars(get_string('simplification_tips_full_1', 'qtype_preg'));
            $equivalences['problem_ids'] = $this->problem_ids;
            $equivalences['problem_type'] = $this->problem_type;
            $equivalences['problem_indfirst'] = $this->indfirst;
            $equivalences['problem_indlast'] = $this->indlast;
        }

        return $equivalences;
    }

    private function search_space_charset($node) {
        if ($node->type == qtype_preg_node::TYPE_LEAF_CHARSET) {
            if (($node->is_single_character() && $node->userinscription[0]->data === ' ')
                || ($this->check_many_charset_node($node) && $node->userinscription[1]->data === ' ')
                && !$node->negative) {
                $this->problem_ids[] = $node->id;
                $this->problem_type = 101;
                $this->indfirst = $node->position->indfirst;
                $this->indlast = $node->position->indlast;
                return true;
            }
        }
        if ($this->is_operator($node)) {
            foreach ($node->operands as $operand) {
                if ($this->search_space_charset($operand)) {
                    return true;
                }
            }
        }

        $this->problem_type = -2;
        $this->indfirst = -2;
        $this->indlast = -2;
        return false;
    }



    /* The 3rd rule */
    public function subpattern_without_backref() {
        $equivalences = array();

        if ($this->search_subpattern_without_backref($this->get_dst_root())) {
            $equivalences['problem'] = htmlspecialchars(get_string('simplification_tips_short_3', 'qtype_preg'));
            $equivalences['solve'] = htmlspecialchars(get_string('simplification_tips_full_3', 'qtype_preg'));
            $equivalences['problem_ids'] = $this->problem_ids;
            $equivalences['problem_type'] = $this->problem_type;
            $equivalences['problem_indfirst'] = $this->indfirst;
            $equivalences['problem_indlast'] = $this->indlast;
        }

        return $equivalences;
    }

    private function search_subpattern_without_backref($node) {
        if ($node->type == qtype_preg_node::TYPE_NODE_SUBEXPR && $node->subtype == qtype_preg_node_subexpr::SUBTYPE_SUBEXPR) {
            if (!$this->check_backref_to_subexpr($this->get_dst_root(), $node->number)) {
                $this->problem_ids[] = $node->id;
                $this->problem_type = 103;
                $this->indfirst = $node->position->indfirst;
                $this->indlast = $node->position->indlast;
                return true;
            }
        }
        if ($this->is_operator($node)) {
            foreach ($node->operands as $operand) {
                if ($this->search_subpattern_without_backref($operand)) {
                    return true;
                }
            }
        }

        $this->problem_type = -2;
        $this->indfirst = -2;
        $this->indlast = -2;
        return false;
    }



    /* The 8rd rule */
    public function exact_match() {
        $equivalences = array();

        if ($this->search_exact_match($this->get_regex_string())) {
            if ($this->options->exactmatch) {
                $equivalences['problem'] = htmlspecialchars(get_string('simplification_tips_short_8', 'qtype_preg'));
                $equivalences['solve'] = htmlspecialchars(get_string('simplification_tips_full_8_alt', 'qtype_preg'));
            } else {
                $equivalences['problem'] = htmlspecialchars(get_string('simplification_tips_short_8', 'qtype_preg'));
                $equivalences['solve'] = htmlspecialchars(get_string('simplification_tips_full_8', 'qtype_preg'));
            }
            $equivalences['problem_ids'] = $this->problem_ids;
            $equivalences['problem_type'] = $this->problem_type;
            $equivalences['problem_indfirst'] = $this->indfirst;
            $equivalences['problem_indlast'] = $this->indlast;
        }

        return $equivalences;
    }

    private function search_exact_match($regex_string) {
        $regex_length = strlen($regex_string);
        if ($regex_length > 0) {
            if ($regex_string[0] === '^' && $regex_string[$regex_length - 1] === '$') {
                //$this->problem_ids[] = $node->id;
                $this->problem_type = 108;
                $this->indfirst = -2;
                $this->indlast = -2;
                return true;
            }
        }

        $this->problem_type = -2;
        $this->indfirst = -2;
        $this->indlast = -2;
        return false;
    }



    //--- OPTIMIZATION ---
    public function optimization() {
        if (count($this->options->problem_ids) > 0 && $this->options->problem_type != -2) {
            if ($this->options->problem_type == 4) {
                if ($this->fold_common_subexpressions($this->get_dst_root())) {
                    return $this->regex_from_tree;
                } else {
                    return '';
                }
            }
            $function_name = ('optimize_' . $this->options->problem_type);
            $this->$function_name($this->get_dst_root());
        }
        return $this->get_regex_string();
    }


    // The 1st rule
    protected function optimize_1($node) {
        return $this->remove_subtree($node, $this->options->problem_ids[0]);
    }

    // The 2nd rule
    protected function optimize_2($node) {
        return $this->remove_subtree($node, $this->options->problem_ids[0]);
    }

    // The 3rd rule
    protected function optimize_3($node) {
        return $this->remove_subtree($node, $this->options->problem_ids[0]);
    }

    // The 4rd rule
//    protected function optimize_4($node) {
//        return $this->fold_common_subexpressions($node/*, $this->options->problem_ids[0]*/);
//    }

    // The 5th rule
    protected function optimize_5($node) {
        return $this->remove_square_brackets_from_charset($node, $this->options->problem_ids[0]);
    }

    // The 6th rule
    protected function optimize_6($node) {
        return $this->change_alternative_to_charset($node, $this->options->problem_ids[0]);
    }

    // The 11th rule
    protected function optimize_11($node) {
        return $this->change_quant_to_equivalent($node, $this->options->problem_ids[0]);
    }

    protected function optimize_101($node) {
        return $this->change_space_to_charset_s($node, $this->options->problem_ids[0]);
    }

    protected function optimize_103($node) {
        return $this->change_subpattern_to_group($node, $this->options->problem_ids[0]);
    }

    private function remove_subtree($node, $remove_node_id) {
        if ($node->id == $remove_node_id) {
            if ($node->id == $this->get_dst_root()->id) {
                $this->dstroot = null;
            }
            return true;
        }

        if ($this->is_operator($node)) {
            foreach ($node->operands as $i => $operand) {
                if ($this->remove_subtree($operand, $remove_node_id)) {
                    if (count($node->operands) === 1) {
                        return $this->remove_subtree($this->get_dst_root(), $node->id);
                    }

                    array_splice($node->operands, $i, 1);
                    if ($this->is_associative_commutative_operator($node) && count($node->operands) < 2) {
                        $node->operands[] = new qtype_preg_leaf_meta(qtype_preg_leaf_meta::SUBTYPE_EMPTY);
                    }

                    return false;
                }
            }
        }

        return false;
    }

    private function remove_square_brackets_from_charset($tree_root, $remove_node_id) {
        if ($tree_root->id == $remove_node_id) {
            $tmp = $tree_root->userinscription[1];
            $tree_root->userinscription = array($tmp);
            $tree_root->flags[0][0]->data = new qtype_poasquestion\string($tmp->data);
            $tree_root->subtype = "enumerable_characters";
            return true;
        }

        if ($this->is_operator($tree_root)) {
            foreach ($tree_root->operands as $operand) {
                if ($this->remove_square_brackets_from_charset($operand, $remove_node_id)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function change_quant_to_equivalent($tree_root, $remove_node_id) {
        if ($tree_root->id == $remove_node_id) {
            if ($tree_root->type == qtype_preg_node::TYPE_NODE_INFINITE_QUANT) {
                if ($tree_root->leftborder === 0 && $tree_root->userinscription[0]->data !== '*') {
                    $tmp = $tree_root->userinscription[0];
                    $tree_root->userinscription = array($tmp);
                    $tree_root->userinscription[0]->data = new qtype_poasquestion\string('*');
                    return true;
                } else if ($tree_root->leftborder === 1 && $tree_root->userinscription[0]->data !== '+') {
                    $tmp = $tree_root->userinscription[0];
                    $tree_root->userinscription = array($tmp);
                    $tree_root->userinscription[0]->data = new qtype_poasquestion\string('+');
                    return true;
                }
            } else if ($tree_root->type == qtype_preg_node::TYPE_NODE_FINITE_QUANT) {
                if ($tree_root->leftborder === 0 && $tree_root->rightborder === 1 && $tree_root->userinscription[0]->data !== '?') {
                    $tmp = $tree_root->userinscription[0];
                    $tree_root->userinscription = array($tmp);
                    $tree_root->userinscription[0]->data = new qtype_poasquestion\string('?');
                    return true;
                }
            }
            return false;
        }

        if ($this->is_operator($tree_root)) {
            foreach ($tree_root->operands as $operand) {
                if ($this->change_quant_to_equivalent($operand, $remove_node_id)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function change_space_to_charset_s($node, $remove_node_id) {
        if ($node->id == $remove_node_id) {
            $node->userinscription[0]->data = '\s';
            return true;
        }

        if ($this->is_operator($node)) {
            foreach ($node->operands as $i => $operand) {
                if ($this->change_space_to_charset_s($operand, $remove_node_id)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function change_alternative_to_charset($node, $remove_node_id) {
        if ($node->id == $remove_node_id) {
            $characters = '[';
            foreach ($node->operands as $operand) {
                if (count($operand->userinscription) === 1) {
                    $characters .= $operand->userinscription[0]->data;
                } else {
                    $characters .= $operand->userinscription[1]->data;
                }
            }
            $characters .= ']';

            $new_node = new qtype_preg_leaf_charset();
            $new_node->set_user_info(null, array(new qtype_preg_userinscription($characters, null)));

            if ($node->id == $this->get_dst_root()->id) {
                $this->dstroot = $new_node;
            } else {
                $local_root = $this->get_parent_node($this->get_dst_root(), $node->id);

                foreach ($local_root->operands as $i => $operand) {
                    if ($operand->id == $node->id) {
                        $local_root->operands[$i] = $new_node;
                    }
                }
            }

            return true;
        }

        if ($this->is_operator($node)) {
            foreach ($node->operands as $i => $operand) {
                if ($this->change_alternative_to_charset($operand, $remove_node_id)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function change_subpattern_to_group($node, $remove_node_id) {
        if ($node->id == $remove_node_id) {
            $node->subtype = qtype_preg_node_subexpr::SUBTYPE_GROUPING;
            return true;
        }

        if ($this->is_operator($node)) {
            foreach ($node->operands as $i => $operand) {
                if ($this->change_subpattern_to_group($operand, $remove_node_id)) {
                    return true;
                }
            }
        }

        return false;
    }
}