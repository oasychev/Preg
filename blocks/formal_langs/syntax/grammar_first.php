<?
/**
 * Describes a FIRST(X) function, as defined in
 * A.V. Aho, R. Sethi, J. D. Ullman. Compilers: Principles, Techniques, and Tools
 * p. 195-196.
 *
 * @package    blocks
 * @subpackage formal_langs
 * @copyright &copy; 2011 Oleg Sychev, Volgograd State Technical University
 * @author     Oleg Sychev, Mamontov Dmitriy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
require_once($CFG->dirroot.'/blocks/formal_langs/syntax/grammar.php');
/**
 * Describes a FIRST(X) function, as defined in
 * A.V. Aho, R. Sethi, J. D. Ullman. Compilers: Principles, Techniques, and Tools
 * p. 195-196.
 */
class block_formal_langs_grammar_first {
    /**
     * A grammar for computing a FIRST function
     * @var block_formal_langs_grammar a grammar
     */
    protected $g;

    /**
     * Constructs a class for computing first function from grammar
     * @param block_formal_langs_grammar $g a grammar
     */
    public function __construct($g) {
        $this->g = $g;
    }

    /** Computes a FIRST(X) function
     *  @param array|block_formal_langs_grammar_production_symbol $x an array or symbol for which prefix must be determined
     *  @return array of block_formal_langs_grammar_production_symbol array of active prefixes
     */
    public function first($x) {
        if (is_array($x)) {
            if (count($x) == 0) {
                die('Invalid array supplied for FIRST(X)');
            }
            if (count($x) == 1) {
                return $this->first_for_element($x[0]);
            }
            return $this->first_for_array($x);
        }
        return $this->first_for_element($x);
    }

    /** Computes a FIRST(X) function for symbol
     *  @param block_formal_langs_grammar_production_symbol $x a symbol
     *  @return array of block_formal_langs_grammar_production_symbol array of active prefixes
     */
    private function first_for_element($x) {
        if ($this->g->is_terminal($x->type())) {
            return array( clone $x );
        }
        // Get definitions for symbol from grammar
        $defs = $this->g->get_definitions_for($x->type());
        $result = array();
        // Add epsilon if epsilon production is available
        $has_epsilon = false;
        for($i = 0; $i < count($defs); $i++) {
            /** @var block_formal_langs_grammar_production_rule $def  */
            $def = $defs[$i];
            if ($def->rightcount() == 1 && $def->right(0)->is_epsilon())
                $has_epsilon = true;
        }
        if ($has_epsilon) {
            $result[] = new block_formal_langs_grammar_epsilon_symbol();
        }
        // Merge all starting definitions
        for($i = 0; $i < count($defs); $i++) {
            $this->merge_if($result, $this->first_for_definition($x, $result, $defs[$i]), true, true);
        }

        return $result;
    }

    /** Computes FIRST(X) function for definition of element
     *  @param block_formal_langs_grammar_production_symbol $x X argument for FIRST(X) function
     *  @param array $parentresult a partially computed FIRST(X) for element X
     *  @param block_formal_langs_grammar_production_rule $definition definition of element
     *  @return array of block_formal_langs_grammar_production_symbol - array of active prefixes
     */
    private function first_for_definition($x, $parentresult, $definition) {
        $result = array();
        $a = array();
        for($i = 0 ; $i < $definition->rightcount(); $i++) {
            if ($definition->right($i)->type() == $x->type()) {
                $a[] = $parentresult;
            } else {
                $a[] = $this->first_for_element($definition->right($i));
            }
        }

        $this->merge_if($result, $a[0], true);
        for($i = 1 ; $i < count($a); $i++) {
            $this->merge_if($result, $a[$i], $this->epsilon_is_in_all_sets($a, 0, $i));
        }
        if ($this->epsilon_is_in_all_sets($a)) {
            $result[] = new block_formal_langs_grammar_epsilon_symbol();
        }

        return $result;
    }

    /** Returns function FIRST(X) for sequence of symbols
     *  @param array $x of block_formal_langs_grammar_production_symbol sequence, FIRST(X) are defined for
     *  @return array of block_formal_langs_grammar_production_symbol - array of active prefixes
     */
    private function first_for_array($x) {
        $a = array();
        $result = array();
        for($i = 0; $i < count($x) ; $i++) {
            $a[] = $this->first_for_element($x[$i]);
        }
        $this->merge_if($result, $a[0], true);
        for($i = 1 ; $i < count($a); $i++) {
            $this->merge_if($result, $a[$i], $this->epsilon_is_in_all_sets($a, 0, $i));
        }
        if ($this->epsilon_is_in_all_sets($a)) {
            $result[] = new block_formal_langs_grammar_epsilon_symbol();
        }
        return $result;
    }


    /** Merges sets of symbols, if condition is true are met.
     *  Also skips epsilon symbol if $addepsilon is not supplied
     *  @param array $result of block_formal_langs_grammar_production_symbol resulting set, where all elements are stored
     *  @param array $set of block_formal_langs_grammar_production_symbol  setm whose elements will be merged with $result
     *  @param  bool $condition a condition flag, which must be supplied
     *  @param  bool $addepsilon whether epsilon symbols must be merged
     */
    protected function merge_if(&$result, $set, $condition, $addepsilon = false) {
        if ($condition == false)
            return;

        for($i = 0; $i < count($set); $i++ ) {
            /** @var block_formal_langs_grammar_production_symbol $el  */
            $el = $set[$i];

            $contains = false;
            for($j = 0 ; $j < count($result); $j++) {
                /** @var block_formal_langs_grammar_production_symbol $rel  */
                $rel = $result[$j];
                if ($el->is_same($rel))
                    $contains = true;
            }
            if ($contains == false  && ($addepsilon == true || $el->is_epsilon() == false))
                $result[] = clone $el;
        }
    }


    /** Whether epsilon symbol is in all sets in a range
     *   @param array $array of array of  block_formal_langs_grammar_production_symbol multiple sets to check with
     *   @param int|null   $from element, from which we must check sets. If null - all sets must be checked
     *   @param int|null   $to   the number of last set, we must check
     *   @return bool true, if epsilon is all sets in range
     */
    private function epsilon_is_in_all_sets($array, $from = null, $to = null) {
        if ($from === null || $to === null) {
            $from = 0;
            $to = count($array);
        }
        $ok = true;
        for($i = $from; $i < $to; $i++) {
            $contains = false;
            for ($j = 0; $j < count($array[$i]); $j++) {
                /** @var block_formal_langs_grammar_production_symbol $s  */
                $s = $array[$i][$j];
                if ($s->is_epsilon()) {
                    $contains = true;
                }
            }
            $ok = $ok && $contains;
        }
        return $ok;
    }
}