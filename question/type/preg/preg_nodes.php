<?php
/**
 * Defines generic node classes, generated by parser. 
 * The will be usually aggregated in engine-specific classes.
 * These classes are used primarily to store data, so their variable memebers are public
 *
 * @copyright &copy; 2010 Sychev Oleg, Kolesov Dmitriy
 * @author Sychev Oleg, Volgograd State Technical University
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questions
 */

/*
* Class for plain lexems (that are not complete nodes), so they could contain position information too
*/
class preg_lexem {
    //Subtype of lexem
    public $subtype;
    //Indexes of first and last characters for the lexem, they are equal if it's one-character lexem
    public $indfirst = -1;
    public $indlast = -1;

    public function __construct($subtype, $indfirst, $indlast) {
        $this->subtype = $subtype;
        $this->indfirst = $indfirst;
        $this->indlast = $indlast;
    }
}

/*
* Class for plain subpattern lexems
*/
class preg_lexem_subpatt extends preg_lexem {
	//Number of subpattern
    public $number;

    public function __construct($subtype, $indfirst, $indlast, $number) {
        parent::__construct($subtype, $indfirst, $indlast);
        $this->number = $number;
    }
}

/**
* Generic node class
*/
abstract class preg_node {

    //////Class constants used to define type
    //Abstract node class, not representing real things
    const TYPE_ABSTRACT = "abstract";
    //Character or character class
    const TYPE_LEAF_CHARSET = "leaf_charset";
    //Meta-character or escape sequence matching with a set of characters that couldn't be enumerated
    const TYPE_LEAF_META = "leaf_meta";
    //Simple assert: ^ $ or escape-sequence
    const TYPE_LEAF_ASSERT = "leaf_assert";
    //Back reference to subpattern
    const TYPE_LEAF_BACKREF = "leaf_backref";
    //Recursive match
    const TYPE_LEAF_RECURSION = "leaf_recursion";
    //Option set
    const TYPE_LEAF_OPTIONS = "leaf_options";
    //Combination of few leaf
    const TYPE_LEAF_COMBO = "leaf_combo";

    //Finite quantifier
    const TYPE_NODE_FINITE_QUANT = "node_finite_quant";
    //Infinite quantifier
    const TYPE_NODE_INFINITE_QUANT = "node_infinite_quant";
    //Concatenation
    const TYPE_NODE_CONCAT = "node_concat";
    //Alternative
    const TYPE_NODE_ALT = "node_alt";
    //Assert with expression within
    const TYPE_NODE_ASSERT = "node_assert";
    //Subpattern
    const TYPE_NODE_SUBPATT = "node_subpatt";
    //Conditional subpattern
    const TYPE_NODE_COND_SUBPATT = "node_cond_subpatt";
    //error node
    const TYPE_NODE_ERROR = "node_error";

    //Member variables, common to all subclasses
    //Type, one of the class  - must return constants defined in this class
    public $type;
    //Subtype, defined by child class
    public $subtype;
    //Error data for the subtype
    public $error = false;
    //Indexes of first and last characters for the node, they are equal if it's one-character node
    public $indfirst = -1;
    public $indlast = -1;

    public function __construct() {
        $this->type = self::TYPE_ABSTRACT;
    }

    /**
    * Return class name without 'preg_' prefix
    * Interface string for the node name should be exactly same (and start from upper-case character)
    * if class not overloading ui_nodename function
    */
    abstract public function name();


    //May be overloaded by childs to change name using data from $this->pregnode
    public function ui_nodename() {
        return get_string($this->name(), 'qtype_preg');
    }

}

/**
* Generic leaf node class
* 
*/
abstract class preg_leaf extends preg_node {

    //Is matching case insensitive?
    public $caseinsensitive = false;
    //Is leaf negative?
    public $negative = false;
    //Assertions, merged into this node (preg_leaf_assert objects)
    public $mergedassertions = array();

    /*
    * Returns number of characters consumed by this leaf: 0 in case of an assertion or eps-leaf, 1 in case of a single character, n in case of a backreferense
    */
    public function consumes() {
        return 1;
    }
    
    /*
    * Returns a clone of the leaf including merged assertions
    */
    public function &get_clone() {
        $res = clone $this;
        $res->mergedassertions = array();
        foreach ($this->mergedassertions as $assert)
            $res->mergedassertions[] = $assert->get_clone();
        return $res;
    }

    /*
    * Returns true if character(s) starting from $str[$pos] matches with leaf, false otherwise
    * Contains universal code to deal with merged assertions. Overload match_inner to define you leaf type matching
    * @param str string with which matching is supporting
    * @param pos position of character in the string, if leaf is no-consuming than position before this character analyzed
    * @param length the length of match (for backreference or recursion), can be 0 for asserts
    * @param cs case sensitivity of the match
    */
    public function match($str, $pos, &$length, $cs)
    {
        $result = true;
        //Check merged assertions
        foreach($this->mergedassertions as $assert) {
            $result = $result && $assert->match($str, $pos, $length, $cs);
        }
        //Now check this leaf
        if ($result) {
            $result = $this->match_inner($str, $pos, $length, $cs);
        }

        return $result;
    }

    /*
    * Returns true if character(s) starting from $str[$pos] matches with leaf, false otherwise
    * Implement details of particular leaf matching
    * @param str string with which matching is supporting
    * @param pos position of character in the string, if leaf is no-consuming than position before this character analyzed
    * @param length the length of match (for backreference or recursion), can be 0 for asserts
    * @param cs case sensitivity of the match
    */
    abstract protected function match_inner($str, $pos, &$length, $cs);
    
    /*
    * Returns a character suitable for both this leaf and merged assertions and previous character
    * @param str string already matched
    * @param pos position of the last matched character in the string
    * @param length number of characters matched in case of partial backreference match
    */
    abstract public function next_character($str, $pos, $length = 0);
    
    /**
    * function gives leaf in human readable form
    * @return human readable form of leaf
    */
    abstract public function tohr();
}

/**
* Character or character class
* Escape-sequence scanning will lead to this class only if characters it represents could be enumerated
* I.e. \n, \s, \v, \h and \d and their negative counterparts since they are not support unicode by default and so can be enumerated 
* \w is too large to be handled by full character set
*/
class preg_leaf_charset extends preg_leaf {

    //Character set, any of which could (not) match with this node
    public $charset = '';
    
    //Flags, if character class contain \w and \W which need to convert to other type of leaf
    public $w;
    public $W;

    public function __construct() {
        $this->type = preg_node::TYPE_LEAF_CHARSET;
        $this->w = false;
        $this->W = false;
    }

    public function name() {
        return 'leaf_charset';
    }

    //TODO - ui_nodename()
    protected function match_inner($str, $pos, &$length, $cs) {
        $textlib = textlib_get_instance();//use textlib to avoid unicode problems
        if ($pos>=$textlib->strlen($str)) {
            $length = 0;
            return false;
        }
        $charsetcopy = $this->charset;
        $strcopy = $str;

        if (!$cs) {
            $charsetcopy = $textlib->strtolower($charsetcopy);
            $strcopy = $textlib->strtolower($strcopy);
        }

        $result = ($textlib->strpos($charsetcopy, $strcopy[$pos]) !== false);

        if ($this->negative) {
            $result = ! $result;
        }
        if ($result) {
            $length = 1;
        } else {
            $length = 0;
        }
        return $result;
    }
    
    public function next_character($str, $pos, $length = 0) {
        if ($this->negative) {
            $i = ord(' ');
            while (strchr(chr($i), $this->charset) !== false) {
                $i++;
            }
            $res = chr($i);
            return $res;
        } else {
            return $this->charset[0];
        }
    }

    public function tohr() {
        if ($this->negative) {
            $direction = '^';
        } else {
            $direction = '';
        }
        $result = "[$direction$this->charset]";
        return $result;
    }
}

/**
* Meta-character or escape sequence defining character set that couldn't be enumerated
*/
class preg_leaf_meta extends preg_leaf {

    //. - any character except \n
    const SUBTYPE_DOT = "dot_leaf_meta";
    //\p{L} or \pL
    const SUBTYPE_UNICODE_PROP = "unicode_prop_leaf_meta";
    // \w 
    //Should be locale-aware, but not Unicode for PCRE-compatibility
    const SUBTYPE_WORD_CHAR = "word_char_leaf_meta";
    //Leaf with empty in alternative (something|)
    const SUBTYPE_EMPTY = "empty_leaf_meta";
    //Service subtype - end of regex, but not end of string
    const SUBTYPE_ENDREG = "endreg_leaf_meta";
    //Unicode property name, used in case of SUBTYPE_UNICODE_PROP
    public $propname = '';

    public function __construct() {
        $this->type = preg_node::TYPE_LEAF_META;
    }
    public function name() {
        return 'leaf_meta';
    }

    //TODO - ui_nodename()
    
    public function consumes() {
        if ($this->subtype = preg_leaf_meta::SUBTYPE_EMPTY) {
            return 0;
        }
        return 1;
    }

    public function next_character($str, $pos, $length = 0) {
        switch ($this->subtype) {
            case preg_leaf_meta::SUBTYPE_DOT:
                $result = 'D';
                break;
            //TODO: unicode property
            case preg_leaf_meta::SUBTYPE_WORD_CHAR:
                if ($this->negative) {
                    $result = '#';
                } else {
                    $result = 'W';
                }
                break;
        }
        return $result;
    }
    protected function match_inner($str, $pos, &$length, $cs) {
        $textlib = textlib_get_instance();
        if ($pos >= $textlib->strlen($str) && $this->subtype != preg_leaf_meta::SUBTYPE_EMPTY) {
            $length = 0;
            return false;
        }
        switch ($this->subtype) {
            case preg_leaf_meta::SUBTYPE_DOT:
                if ($pos < $textlib->strlen($str) && $str[$pos] != "\n") {
                    $length = 1;
                    return true;
                } else {
                    $length = 0;
                    return false;
                }
                break;
            //TODO: unicode property
            case preg_leaf_meta::SUBTYPE_WORD_CHAR:
                if (ctype_alnum($str[$pos]) || $str[$pos] === '_') {
                    $result =  true;
                } else {
                    $result =  false;
                }
                break;
            case preg_leaf_meta::SUBTYPE_EMPTY:
                $length = 0;
                return true;
                break;
        }
        if ($this->negative) {
            $result = !$result;
        }
        if ($result) {
            $length = 1;
        } else {
            $length = 0;
        }
        return $result;
    }
    public function tohr() {
        if ($this->negative) {
            $direction = '!';
        } else {
            $direction = '';
        }
        switch ($this->subtype) {
            case preg_leaf_meta::SUBTYPE_WORD_CHAR:
                $type = '\\w';
                break;
            case preg_leaf_meta::SUBTYPE_DOT:
                $type = 'dot';
                break;
            case preg_leaf_meta::SUBTYPE_ENDREG:
                $type = 'ENDREG';
                break;
            case preg_leaf_meta::SUBTYPE_EMPTY:
                $type = 'eps';
                break;
        };
        $result = "$direction"."meta$type";
        return $result;
    }
}

/**
* Meta-character or escape sequence defining character set that couldn't be enumerated
*/
class preg_leaf_assert extends preg_leaf {

    //^
    const SUBTYPE_CIRCUMFLEX = "circumflex_leaf_assert";
    //$
    const SUBTYPE_DOLLAR = "dollar_leaf_assert";
    // \b
    const SUBTYPE_WORDBREAK = "wordbreak_leaf_assert";
    // \A
    const SUBTYPE_ESC_A = "esc_a_leaf_assert";
    // \z
    const SUBTYPE_ESC_Z = "esc_z_leaf_assert";
    // \G
    const SUBTYPE_ESC_G = "esc_g_leaf_assert";

    //Reference to the matcher object to be able to query it for captured subpattern
    //Filled only to ESC_G subtype if it would be implemented in the future
    public $matcher;

    public function __construct() {
        $this->type = preg_node::TYPE_LEAF_ASSERT;
    }

    public function consumes() {
        return 0;
    }

    public function name() {
        return 'leaf_assert';
    }

    //TODO - ui_nodename()
    protected function match_inner($str, $pos, &$length, $cs) {
        $textlib = textlib_get_instance();
        $length = 0;
        switch ($this->subtype) {
            case preg_leaf_assert::SUBTYPE_ESC_A://because may be one line only is response
            case preg_leaf_assert::SUBTYPE_ESC_G://there are no repetitive matching for now, so \G is equvivalent to \A
            case preg_leaf_assert::SUBTYPE_CIRCUMFLEX:
                if($pos == 0) {
                    $result = true;
                } else {
                    $result = false;
                }
                break;
            case preg_leaf_assert::SUBTYPE_ESC_Z://because may be one line only is response
            case preg_leaf_assert::SUBTYPE_DOLLAR:
                if ($pos == $textlib->strlen($str)) {
                    $result = true;
                } else {
                    $result = false;
                }
                break;
            case preg_leaf_assert::SUBTYPE_WORDBREAK:
                $start = $pos==0 && ($str[0]=='_' || ctype_alnum($str[0]));
                $end = $pos == $textlib->strlen($str) && ($str[$pos-1]=='_' || ctype_alnum($str[$pos-1]));
                if (!$end) {
                    $wW = ($str[$pos-1]=='_' || ctype_alnum($str[$pos-1])) && !($str[$pos]=='_' || ctype_alnum($str[$pos]));
                    $Ww = !($str[$pos-1]=='_' || ctype_alnum($str[$pos-1])) && ($str[$pos]=='_' || ctype_alnum($str[$pos]));
                }
                if ($start||$end||$wW||$Ww) {
                    $result = true;
                } else {
                    $result = false;
                }
                break;
        }
        if ($this->negative) {
            $result = !$result;
        }
        return $result;
    }
    public function next_character($str, $pos, $length = 0) {
        switch ($this->subtype) {
            case preg_leaf_assert::SUBTYPE_ESC_A://because may be one line only is response
            case preg_leaf_assert::SUBTYPE_CIRCUMFLEX:
                if ($this->negative) {
                    return 'notstringstart';
                } else {
                    return 'stringstart';
                }
                break;
            case preg_leaf_assert::SUBTYPE_ESC_Z://because may be one line only is response
            case preg_leaf_assert::SUBTYPE_DOLLAR:
                if ($this->negative) {
                    return ' notstringend';
                } else {
                    return '';
                }
                break;
            case preg_leaf_assert::SUBTYPE_WORDBREAK:
                if ($this->negative) {
                    return 'notwordchar';
                } else {
                    return 'wordchar';
                }
                break;
        }
    }
    public function tohr() {
        if ($this->negative) {
            $direction = '!';
        } else {
            $direction = '';
        }
        switch ($this->subtype) {
            case preg_leaf_assert::SUBTYPE_ESC_A://because may be one line only is response
            case preg_leaf_assert::SUBTYPE_CIRCUMFLEX:
                $type = '^';
                break;
            case preg_leaf_assert::SUBTYPE_ESC_Z://because may be one line only is response
            case preg_leaf_assert::SUBTYPE_DOLLAR:
                $type = '$';
                break;
            case preg_leaf_assert::SUBTYPE_WORDBREAK:
                $type = '\\b';
                break;
        };
        $result = "$direction"."assert$type";
        return $result;
    }
}
class preg_leaf_combo extends preg_leaf {

    //Unite of leafs
    const SUBTYPE_UNITE = "unite_leaf_combo";
    //Cross of leafs
    const SUBTYPE_CROSS = "cross_leaf_combo";
    
    var $childs;
    var $subtype;

    public function __construct() {
        $this->type = preg_node::TYPE_LEAF_COMBO;
    }

    public function consumes() {//TODO: fix it!
        if (is_array($this->childs)) {
            return $this->childs[0]->consumes() + $this->childs[0]->consumes();
        } else {
            return true;
        }
    }

    public function name() {
        return 'leaf_combo';
    }

    protected function match_inner($str, $pos, &$length, $cs) {
        $match0 = $this->childs[0]->match($str, $pos, &$length0, $cs);
        $match1 = $this->childs[1]->match($str, $pos, &$length1, $cs);
        if ($this->subtype == preg_leaf_combo::SUBTYPE_UNITE) {
            if ($match0 && $match1) {
                $length = max($length0, $length1);
            } elseif ($match0) {
                $length = $length0;
            } elseif ($match1) {
                $length = $length1;
            } else {
                $length = 0;
            }
            $result = $match0 || $match1;
        } elseif ($this->subtype == preg_leaf_combo::SUBTYPE_CROSS) {
            $result = $match0 && $match1;
            if ($result) {
                $length = max($length0, $length1);
            } else {
                $length = 0;
            }
        }
        return $result;
    }
    public function next_character($str, $pos, $length = 0) {
        if ($this->subtype == preg_leaf_combo::SUBTYPE_UNITE) {
            if (is_array($this->childs)) {
                return $this->childs[0]->next_character($str, $pos, $length);
        } else {
            return 'ERROR: combo of nothing!';
        }
        } elseif ($this->subtype == preg_leaf_combo::SUBTYPE_CROSS) {
            die('Implement preg_leaf_combo::next_character($str, $pos, $length) for crossing of leaf, before use it!');
        }
    }
    public function tohr() {
        if (is_array($this->childs)) {
            return $this->childs[0]->tohr().$this->childs[1]->tohr();
        } else {
            return 'ERROR: combo of nothing!';
        }
    }
    static public function get_unite($leaf0, $leaf1) {
        if ($leaf0->type == preg_node::TYPE_LEAF_CHARSET && $leaf1->type == preg_node::TYPE_LEAF_CHARSET) {
            $result = new preg_leaf_charset;
            if ($leaf0->negative && $leaf1->negative) {
                $result->negative = true;
                $result->charset = self::cross_charsets($leaf0->charset, $leaf1->charset);
            } elseif ($leaf0->negative) {
                $result->negative = true;
                $result->charset = self::sub_charsets($leaf0->charset, $leaf1->charset);
            } elseif ($leaf1->negative) {
                $result->negative = true;
                $result->charset = self::sub_charsets($leaf1->charset, $leaf0->charset);
            } else {
                $result->negative = false;
                $result->charset =  self::unite_charsets($leaf0->charset, $leaf1->charset);
            }
        } else if ($leaf0->type == preg_node::TYPE_LEAF_META && $leaf0->subtype == preg_leaf_meta::SUBTYPE_DOT) {
            $result = $leaf1;
        } else if ($leaf1->type == preg_node::TYPE_LEAF_META && $leaf1->subtype == preg_leaf_meta::SUBTYPE_DOT) {
            $result = $leaf1;
        } else {
            $result = new preg_leaf_combo;
            $result->subtype = preg_leaf_combo::SUBTYPE_UNITE;
            $result->childs[0] = $leaf0;
            $result->childs[1] = $leaf1;
        }
        return $result;
    }
    static public function get_cross($leaf0, $leaf1) {
        if ($leaf0->type == preg_node::TYPE_LEAF_CHARSET && $leaf1->type == preg_node::TYPE_LEAF_CHARSET) {
            $result = new preg_leaf_charset;
            if ($leaf0->negative && $leaf1->negative) {
                $result->negative = true;
                $result->charset = self::unite_charsets($leaf0->charset, $leaf1->charset);
            } elseif ($leaf0->negative) {
                $result->negative = false;
                $result->charset = self::sub_charsets($leaf1->charset, $leaf0->charset);
            } elseif ($leaf1->negative) {
                $result->negative = false;
                $result->charset = self::sub_charsets($leaf0->charset, $leaf1->charset);
            } else {
                $result->negative = false;
                $result->charset = self::cross_charsets($leaf0->charset, $leaf1->charset);
            }
        } else if ($leaf0->type == preg_node::TYPE_LEAF_META && $leaf0->subtype == preg_leaf_meta::SUBTYPE_DOT) {
            $result = $leaf1;
        } else if ($leaf1->type == preg_node::TYPE_LEAF_META && $leaf1->subtype == preg_leaf_meta::SUBTYPE_DOT) {
            $result = $leaf0;
        } else {
            $result = new preg_leaf_combo;
            $result->subtype = preg_leaf_combo::SUBTYPE_CROSS;
            $result->childs[0] = $leaf0;
            $result->childs[1] = $leaf1;
        }
        return $result;
    }
    static public function cross_charsets($charset0, $charset1) {
        $result = '';
        $textlib = textlib_get_instance();
        for ($i=0; $i < $textlib->strlen($charset0); $i++) {
            if ($textlib->strpos($charset1, $charset0[$i])!==false) {
                $result.=$charset0[$i];
            }
        }
        return $result;
    }
    static public function sub_charsets($charset0, $charset1) {
        $result = '';
        $textlib = textlib_get_instance();
        for ($i=0; $i < $textlib->strlen($charset0); $i++) {
            if ($textlib->strpos($charset1, $charset0[$i])===false) {
                $result.=$charset0[$i];
            }
        }
        return $result;
    }
    static public function unite_charsets($charset0, $charset1) {
        $result = $charset1;
        $textlib = textlib_get_instance();
        for ($i=0; $i < $textlib->strlen($charset0); $i++) {
            if ($textlib->strpos($charset1, $charset0[$i])===false) {
                $result.=$charset0[$i];
            }
        }
        return $result;
    }
}

class preg_leaf_backref extends preg_leaf {
    public $number;
    //Reference to the matcher object to be able to query it for captured subpattern
    public $matcher;

    public function __construct() {
        $this->type = preg_node::TYPE_LEAF_BACKREF;
    }

    public function consumes() {
        if (!$this->matcher->is_subpattern_captured($this->number)) {
            return 0;
        }
        return $this->matcher->last_correct_character_index($this->number) - $this->matcher->first_correct_character_index($this->number) + 1;
    }

    protected function match_inner($str, $pos, &$length, $cs) {
        $textlib = textlib_get_instance();
        $len = $textlib->strlen($str);
        if (!$this->matcher->is_subpattern_captured($this->number) || $pos >= $len) {
            $length = 0;
            return false;
        }
        $strcopy = $str;
        $textlib = textlib_get_instance();//use textlib to avoid unicode problems

        if (!$cs) {
            $strcopy = $textlib->strtolower($strcopy);
        }
        $start = $this->matcher->first_correct_character_index($this->number);
        $end = $this->matcher->last_correct_character_index($this->number);
        $matchlen = 0;
        $result = true;
        // check char by char
        for ($i = $start; $result && $i <= $end && $i + $pos < $len; $i++) {
            $result = $result && ($strcopy[$i] == $strcopy[$i + $pos]);
            if ($result) {
                $matchlen++;
            }
        }
        // if the string has not enough characters
        if ($end + $pos >= $len) {
            $result = false;
        }
        $length = $matchlen;        
        return $result;
    }
    public function name() {
        return 'leaf_backref';
    }
    public function next_character($str, $pos, $length = 0) {
        die ('TODO: implements abstract function character for preg_leaf_backref class before use it!');
    }
    public function tohr() {
        return 'backref #'.$this->number;
    }
}

class preg_leaf_option extends preg_leaf {
    public $posopt;
    public $negopt;
    
    public function __construct() {
        $this->type = preg_node::TYPE_LEAF_OPTIONS;
    }
    protected function match_inner($str, $pos, &$length, $cs) {
        die ('TODO: implements abstract function match for preg_leaf_option class before use it!');
    }
    public function name() {
        return 'leaf_option';
    }
    public function next_character($str, $pos, $length = 0) {
        die ('TODO: implements abstract function character for preg_leaf_option class before use it!');
    }
    public function tohr() {
        return '(?'.$this->posopt.'-'.$this->negopt;
    }
}

    //TODO - ui_nodename()
class preg_leaf_recursion extends preg_leaf {

    public $number;

    public function __construct() {
        $this->type = preg_node::TYPE_LEAF_RECURSION;
    }
    protected function match_inner($str, $pos, &$length, $cs) {
        die ('TODO: implements abstract function match for preg_leaf_recursion class before use it!');
    }
    public function name() {
        return 'leaf_recursion';
    }
    public function next_character($str, $pos, $length = 0){
        die ('TODO: implements abstract function character for preg_leaf_recursion class before use it!');
    }
    public function tohr() {
        return 'recursion';
    }
}


/**
* Operator node
*/
abstract class preg_operator extends preg_node {

    //An array of operands
    public $operands = array();

}


/**
* Finite quantifier node with left and right border
* Unary
* Possible errors: left border is greater than right one
*/
class preg_node_finite_quant extends preg_operator {

    //Is quantifier greed?
    public $greed;
    //Is quantifier posessive?
    public $posessive;
    //Smallest possible repetition number
    public $leftborder;
    //Biggest possible repetition number
    public $rightborder;

    public function __construct() {
        $this->type = preg_node::TYPE_NODE_FINITE_QUANT;
    }

    public function name() {
        return 'node_finite_quant';
    }

    //TODO - ui_nodename()
}

/**
* Infinite quantifier node with left border only
* Unary
*/
class preg_node_infinite_quant extends preg_operator {

    //Is quantifier greed?
    public $greed;
    //Is quantifier posessive?
    public $posessive;
    //Smallest possible repetition number
    public $leftborder;

    public function __construct() {
        $this->type = preg_node::TYPE_NODE_INFINITE_QUANT;
    }

    public function name() {
        return 'node_infinite_quant';
    }

    //TODO - ui_nodename()
}

/**
* Concatenation operator
* Binary
*/
class preg_node_concat extends preg_operator {
    public function __construct() {
        $this->type = preg_node::TYPE_NODE_CONCAT;
    }

    public function name() {
        return 'node_concat';
    }

}

/**
* Alternative operator
* Binary
*/
class preg_node_alt extends preg_operator {

    public function __construct() {
        $this->type = preg_node::TYPE_NODE_ALT;
    }

    public function name() {
        return 'node_alt';
    }

}

/**
* Assert with expression within
* Unary
*/
class preg_node_assert extends preg_operator {

    //Positive lookahead assert
    const SUBTYPE_PLA = "pla_node_assert";
    //Negative lookahead assert
    const SUBTYPE_NLA = "nla_node_assert";
    //Positive lookbehind assert
    const SUBTYPE_PLB = "plb_node_assert";
    //Negative lookbehind assert
    const SUBTYPE_NLB = "nlb_node_assert";

    public function __construct() {
        $this->type = preg_node::TYPE_NODE_ASSERT;
    }

    public function name() {
        return 'node_assert';
    }
    public function tohr() {
        return 'node assert';
    }
    
    //TODO - ui_nodename()
}

/**
* Subpattern
* Unary
*/
class preg_node_subpatt extends preg_operator {

    //Subpattern
    const SUBTYPE_SUBPATT = "subpatt_node_subpatt";
    //Once-only subpattern
    const SUBTYPE_ONCEONLY = "onceonly_node_subpatt";

    //Subpattern number
    public $number = 0;
    //Subpattern match (if supported)
    public $match = null;

    public function __construct() {
        $this->type = preg_node::TYPE_NODE_SUBPATT;
    }

    public function name() {
        return 'node_subpatt';
    }

    //TODO - ui_nodename()
}

/**
* Conditional subpattern
* Unary, binary or ternary, first operand is assert expression (if any),  second - yes-pattern, third - no-pattern
* Possible errors: there is no backreference with such number in expression
*/
class preg_node_cond_subpatt extends preg_operator {

    //Subtypes define a type of condition for subpatern
    //Positive lookahead assert
    const SUBTYPE_PLA = "pla_node_cond_subpatt";
    //Negative lookahead assert
    const SUBTYPE_NLA = "nla_node_cond_subpatt";
    //Positive lookbehind assert
    const SUBTYPE_PLB = "plb_node_cond_subpatt";
    //Negative lookbehind assert
    const SUBTYPE_NLB = "nlb_node_cond_subpatt";
    //Backreference 
    const SUBTYPE_BACKREF = "backref_node_cond_subpatt";
    //Recursive
    const SUBTYPE_RECURSIVE = "recursive_node_cond_subpatt";

    //Subpattern number
    public $number = 0;
    //Subpattern match (if supported)
    public $match = null;
    //Is condition satisfied?
    public $condbranch = null;
    //Backreference number
    public $backrefnumber = -1;

    public function __construct() {
        $this->type = preg_node::TYPE_NODE_COND_SUBPATT;
    }

    public function name() {
        return 'node_cond_subpatt';
    }

    //TODO - ui_nodename()
}
class preg_node_error extends preg_node {

    //Subtypes define a type of error
    //Unknown parse error
    const SUBTYPE_UNKNOWN_ERROR = "unknown_error_node_error";
    //Too much top-level alternatives in conditional subpattern
    const SUBTYPE_CONDSUBPATT_TOO_MUCH_ALTER = "consubpatt_too_much_alter_node_error";
    //Close paren without opening  xxx)
    const SUBTYPE_WRONG_CLOSE_PAREN = "wrong_close_paren_node_error";
    //Open paren without closing  (xxx
    const SUBTYPE_WRONG_OPEN_PAREN = "wrong_open_paren_node_error";
    //Empty parens
    const SUBTYPE_EMPTY_PARENS = "empty_parens_node_error";
    //Quantifier at start of expression  - NOTE - currently incompatible with PCRE which treat it as character
    const SUBTYPE_QUANTIFIER_WITHOUT_PARAMETER = "quantifier_without_parameter_node_error";
    //Unclosed square brackets in character class
    const SUBTYPE_UNCLOSED_CHARCLASS = "unclosed_charclass_node_error";

    //Error strings name in qtype_preg.php lang file
    public static $errstrs = array( preg_node_error::SUBTYPE_UNKNOWN_ERROR => 'incorrectregex', preg_node_error::SUBTYPE_CONDSUBPATT_TOO_MUCH_ALTER => 'threealtincondsubpatt', 
                                    preg_node_error::SUBTYPE_WRONG_CLOSE_PAREN => 'unopenedparen', preg_node_error::SUBTYPE_WRONG_OPEN_PAREN => 'unclosedparen', 
                                    preg_node_error::SUBTYPE_EMPTY_PARENS => 'emptyparens', preg_node_error::SUBTYPE_QUANTIFIER_WITHOUT_PARAMETER => 'quantifieratstart',
                                    preg_node_error::SUBTYPE_UNCLOSED_CHARCLASS => 'unclosedsqbrackets');

    //Arrays of indexes in regex string describing error to highlight to the user (and include in message) - first and last
    public $firstindxs;
    public $lastindxs;
    //Additional info
    public $addinfo;

    public function name() {
        return 'node_error';
    }

    public function __construct() {
        $this->type = preg_node::TYPE_NODE_ERROR;
        $this->firstindxs = array();
        $this->lastindxs = array();
        $this->addinfo = null;
    }

    /*
    * Returns an user interface error string for the error, represented by node
    */
    public function error_string() {
        $a = new stdClass;
        $a->indfirst = $this->firstindxs[0];
        $a->indlast = $this->lastindxs[0];
        $a->addinfo = $this->addinfo;
        return get_string(preg_node_error::$errstrs[$this->subtype], 'qtype_preg', $a);
    }
}


?>