<?php

/**
 * Contains source info for generation of lexer.
 *
 * If there are named subexpressions or backreferences, the returning nodes will contain not names but
 * their automatically-assigned numbers. To deal with names, the lexer saves a map name => number.
 *
 * As for error tokens, an error may be returned in 3 ways:
 *   a) as a regular node, but with non-null error field of it;
 *   b) as leafs, if it's not possible to act using the error field.
 *
 * The error field is usually filled if the node contains semantic errors but the syntax is correct:
 * for example, wrong quantifier borders {4,3}, wrong charset range z-a, etc.
 *
 * Additionally, all the errors found are also stored in the lexer's errors array and can be retrieved
 * by using the get_error_nodes() method.
 *
 * All nodes returned from the lexer should have valid userinscription and indexes.
 *
 * @package    qtype_preg
 * @copyright  2012 Oleg Sychev, Volgograd State Technical University
 * @author     Valeriy Streltsov <vostreltsov@gmail.com>, Dmitriy Kolesov <xapuyc7@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/poasquestion/poasquestion_string.php');
require_once($CFG->dirroot . '/question/type/poasquestion/jlex.php');
require_once($CFG->dirroot . '/question/type/preg/preg_parser.php');
require_once($CFG->dirroot . '/question/type/preg/preg_nodes.php');
require_once($CFG->dirroot . '/question/type/preg/preg_unicode.php');

class qtype_preg_opt_stack_item {
    public $modifiers;
    public $subexpr_num;
    public $subexpr_name;
    public $parennum;

    public function __construct($modifiers, $subexpr_num, $subexpr_name, $parennum) {
        $this->modifiers = $modifiers;
        $this->subexpr_num = $subexpr_num;
        $this->subexpr_name = $subexpr_name;
        $this->parennum = $parennum;
    }
}

%%
%class qtype_preg_lexer
%function nextToken
%char
%unicode
%state YYCOMMENT
%state YYQEOUT
%state YYQEIN
%state YYCHARSET
NOTSPECIALO = [^"\^$.[|()?*+{"]                          // Special characters outside character sets.
NOTSPECIALI = [^"\^-[]"]                                 // Special characters inside character sets.
MODIFIER    = [^"(|)<>#':=!PCR"0-9]                      // Excluding reserved (?... sequences, returning error if there is something weird.
ALNUM       = [^"!\"#$%&'()*+,-./:;<=>?[\]^`{|}~" \t\n]  // Used in subexpression\backreference names.
%init{
    $this->handlingoptions = new qtype_preg_handling_options();
    $this->opt_stack[0] = new qtype_preg_opt_stack_item(array('i' => false), -1, null, -1);
%init}
%eof{
    // End of the expression inside a character class.
    if ($this->yy_lexical_state == self::YYCHARSET) {
        $this->create_error_node(qtype_preg_node_error::SUBTYPE_UNCLOSED_CHARSET, '', $this->charset->indfirst, $this->yychar - 1, '');
    }

    // End of the expression inside a comment.
    if ($this->yy_lexical_state == self::YYCOMMENT) {
        $this->create_error_node(qtype_preg_node_error::SUBTYPE_MISSING_COMMENT_ENDING, '', $this->yychar - $this->comment_length, $this->yychar - 1, '');  // TODO indexes
    }

    // Check for backreferences to unexisting subexpressions.
    foreach ($this->backrefs as $leaf) {
        $number = $leaf->number;
        if (!is_int($number) || $number > $this->max_subexpr) {
            $this->create_error_node(qtype_preg_node_error::SUBTYPE_UNEXISTING_SUBEXPR, $number, $leaf->indfirst, $leaf->indlast, '');
        }
    }
%eof}
%{
    // Regex handling options set from the outside.
    public $handlingoptions = null;

    // Array of lexical errors found.
    protected $errors = array();

    // Number of the last lexed subexpression, used to deal with (?| ... ) constructions.
    protected $last_subexpr = 0;

    // Max subexpression number.
    protected $max_subexpr = 0;

    // Map of subexpression names => numbers.
    protected $subexpr_map = array();

    // Array of backreference leafs. Used ad the end of lexical analysis to check for backrefs to unexisting subexpressions.
    protected $backrefs = array();

    // Stack containing additional information about subexpressions (modifiers, current subexpression name, etc).
    protected $opt_stack = array();

    // Number of items in the above stack.
    protected $opt_count = 1;

    // Comment string.
    protected $comment = '';

    // Comment length.
    protected $comment_length = 0;

    // \Q...\E sequence.
    protected $qe_sequence = '';

    // \Q...\E sequence length.
    protected $qe_sequence_length = 0;

    // An instance of qtype_preg_leaf_charset, used when in YYCHARSET state.
    protected $charset = null;

    // Number of characters in the charset excluding flags.
    protected $charset_count = 0;

    // Characters of the charset.
    protected $charset_set = '';
    protected static $upropflags = array('C'                      => qtype_preg_charset_flag::UPROPC,
                                         'Cc'                     => qtype_preg_charset_flag::UPROPCC,
                                         'Cf'                     => qtype_preg_charset_flag::UPROPCF,
                                         'Cn'                     => qtype_preg_charset_flag::UPROPCN,
                                         'Co'                     => qtype_preg_charset_flag::UPROPCO,
                                         'Cs'                     => qtype_preg_charset_flag::UPROPCS,
                                         'L'                      => qtype_preg_charset_flag::UPROPL,
                                         'Ll'                     => qtype_preg_charset_flag::UPROPLL,
                                         'Lm'                     => qtype_preg_charset_flag::UPROPLM,
                                         'Lo'                     => qtype_preg_charset_flag::UPROPLO,
                                         'Lt'                     => qtype_preg_charset_flag::UPROPLT,
                                         'Lu'                     => qtype_preg_charset_flag::UPROPLU,
                                         'M'                      => qtype_preg_charset_flag::UPROPM,
                                         'Mc'                     => qtype_preg_charset_flag::UPROPMC,
                                         'Me'                     => qtype_preg_charset_flag::UPROPME,
                                         'Mn'                     => qtype_preg_charset_flag::UPROPMN,
                                         'N'                      => qtype_preg_charset_flag::UPROPN,
                                         'Nd'                     => qtype_preg_charset_flag::UPROPND,
                                         'Nl'                     => qtype_preg_charset_flag::UPROPNL,
                                         'No'                     => qtype_preg_charset_flag::UPROPNO,
                                         'P'                      => qtype_preg_charset_flag::UPROPP,
                                         'Pc'                     => qtype_preg_charset_flag::UPROPPC,
                                         'Pd'                     => qtype_preg_charset_flag::UPROPPD,
                                         'Pe'                     => qtype_preg_charset_flag::UPROPPE,
                                         'Pf'                     => qtype_preg_charset_flag::UPROPPF,
                                         'Pi'                     => qtype_preg_charset_flag::UPROPPI,
                                         'Po'                     => qtype_preg_charset_flag::UPROPPO,
                                         'Ps'                     => qtype_preg_charset_flag::UPROPPS,
                                         'S'                      => qtype_preg_charset_flag::UPROPS,
                                         'Sc'                     => qtype_preg_charset_flag::UPROPSC,
                                         'Sk'                     => qtype_preg_charset_flag::UPROPSK,
                                         'Sm'                     => qtype_preg_charset_flag::UPROPSM,
                                         'So'                     => qtype_preg_charset_flag::UPROPSO,
                                         'Z'                      => qtype_preg_charset_flag::UPROPZ,
                                         'Zl'                     => qtype_preg_charset_flag::UPROPZL,
                                         'Zp'                     => qtype_preg_charset_flag::UPROPZP,
                                         'Zs'                     => qtype_preg_charset_flag::UPROPZS,
                                         'Xan'                    => qtype_preg_charset_flag::UPROPXAN,
                                         'Xps'                    => qtype_preg_charset_flag::UPROPXPS,
                                         'Xsp'                    => qtype_preg_charset_flag::UPROPXSP,
                                         'Xwd'                    => qtype_preg_charset_flag::UPROPXWD,
                                         'Arabic'                 => qtype_preg_charset_flag::ARABIC,
                                         'Armenian'               => qtype_preg_charset_flag::ARMENIAN,
                                         'Avestan'                => qtype_preg_charset_flag::AVESTAN,
                                         'Balinese'               => qtype_preg_charset_flag::BALINESE,
                                         'Bamum'                  => qtype_preg_charset_flag::BAMUM,
                                         'Bengali'                => qtype_preg_charset_flag::BENGALI,
                                         'Bopomofo'               => qtype_preg_charset_flag::BOPOMOFO,
                                         'Braille'                => qtype_preg_charset_flag::BRAILLE,
                                         'Buginese'               => qtype_preg_charset_flag::BUGINESE,
                                         'Buhid'                  => qtype_preg_charset_flag::BUHID,
                                         'Canadian_Aboriginal'    => qtype_preg_charset_flag::CANADIAN_ABORIGINAL,
                                         'Carian'                 => qtype_preg_charset_flag::CARIAN,
                                         'Cham'                   => qtype_preg_charset_flag::CHAM,
                                         'Cherokee'               => qtype_preg_charset_flag::CHEROKEE,
                                         'Common'                 => qtype_preg_charset_flag::COMMON,
                                         'Coptic'                 => qtype_preg_charset_flag::COPTIC,
                                         'Cuneiform'              => qtype_preg_charset_flag::CUNEIFORM,
                                         'Cypriot'                => qtype_preg_charset_flag::CYPRIOT,
                                         'Cyrillic'               => qtype_preg_charset_flag::CYRILLIC,
                                         'Deseret'                => qtype_preg_charset_flag::DESERET,
                                         'Devanagari'             => qtype_preg_charset_flag::DEVANAGARI,
                                         'Egyptian_Hieroglyphs'   => qtype_preg_charset_flag::EGYPTIAN_HIEROGLYPHS,
                                         'Ethiopic'               => qtype_preg_charset_flag::ETHIOPIC,
                                         'Georgian'               => qtype_preg_charset_flag::GEORGIAN,
                                         'Glagolitic'             => qtype_preg_charset_flag::GLAGOLITIC,
                                         'Gothic'                 => qtype_preg_charset_flag::GOTHIC,
                                         'Greek'                  => qtype_preg_charset_flag::GREEK,
                                         'Gujarati'               => qtype_preg_charset_flag::GUJARATI,
                                         'Gurmukhi'               => qtype_preg_charset_flag::GURMUKHI,
                                         'Han'                    => qtype_preg_charset_flag::HAN,
                                         'Hangul'                 => qtype_preg_charset_flag::HANGUL,
                                         'Hanunoo'                => qtype_preg_charset_flag::HANUNOO,
                                         'Hebrew'                 => qtype_preg_charset_flag::HEBREW,
                                         'Hiragana'               => qtype_preg_charset_flag::HIRAGANA,
                                         'Imperial_Aramaic'       => qtype_preg_charset_flag::IMPERIAL_ARAMAIC,
                                         'Inherited'              => qtype_preg_charset_flag::INHERITED,
                                         'Inscriptional_Pahlavi'  => qtype_preg_charset_flag::INSCRIPTIONAL_PAHLAVI,
                                         'Inscriptional_Parthian' => qtype_preg_charset_flag::INSCRIPTIONAL_PARTHIAN,
                                         'Javanese'               => qtype_preg_charset_flag::JAVANESE,
                                         'Kaithi'                 => qtype_preg_charset_flag::KAITHI,
                                         'Kannada'                => qtype_preg_charset_flag::KANNADA,
                                         'Katakana'               => qtype_preg_charset_flag::KATAKANA,
                                         'Kayah_Li'               => qtype_preg_charset_flag::KAYAH_LI,
                                         'Kharoshthi'             => qtype_preg_charset_flag::KHAROSHTHI,
                                         'Khmer'                  => qtype_preg_charset_flag::KHMER,
                                         'Lao'                    => qtype_preg_charset_flag::LAO,
                                         'Latin'                  => qtype_preg_charset_flag::LATIN,
                                         'Lepcha'                 => qtype_preg_charset_flag::LEPCHA,
                                         'Limbu'                  => qtype_preg_charset_flag::LIMBU,
                                         'Linear_B'               => qtype_preg_charset_flag::LINEAR_B,
                                         'Lisu'                   => qtype_preg_charset_flag::LISU,
                                         'Lycian'                 => qtype_preg_charset_flag::LYCIAN,
                                         'Lydian'                 => qtype_preg_charset_flag::LYDIAN,
                                         'Malayalam'              => qtype_preg_charset_flag::MALAYALAM,
                                         'Meetei_Mayek'           => qtype_preg_charset_flag::MEETEI_MAYEK,
                                         'Mongolian'              => qtype_preg_charset_flag::MONGOLIAN,
                                         'Myanmar'                => qtype_preg_charset_flag::MYANMAR,
                                         'New_Tai_Lue'            => qtype_preg_charset_flag::NEW_TAI_LUE,
                                         'Nko'                    => qtype_preg_charset_flag::NKO,
                                         'Ogham'                  => qtype_preg_charset_flag::OGHAM,
                                         'Old_Italic'             => qtype_preg_charset_flag::OLD_ITALIC,
                                         'Old_Persian'            => qtype_preg_charset_flag::OLD_PERSIAN,
                                         'Old_South_Arabian'      => qtype_preg_charset_flag::OLD_SOUTH_ARABIAN,
                                         'Old_Turkic'             => qtype_preg_charset_flag::OLD_TURKIC,
                                         'Ol_Chiki'               => qtype_preg_charset_flag::OL_CHIKI,
                                         'Oriya'                  => qtype_preg_charset_flag::ORIYA,
                                         'Osmanya'                => qtype_preg_charset_flag::OSMANYA,
                                         'Phags_Pa'               => qtype_preg_charset_flag::PHAGS_PA,
                                         'Phoenician'             => qtype_preg_charset_flag::PHOENICIAN,
                                         'Rejang'                 => qtype_preg_charset_flag::REJANG,
                                         'Runic'                  => qtype_preg_charset_flag::RUNIC,
                                         'Samaritan'              => qtype_preg_charset_flag::SAMARITAN,
                                         'Saurashtra'             => qtype_preg_charset_flag::SAURASHTRA,
                                         'Shavian'                => qtype_preg_charset_flag::SHAVIAN,
                                         'Sinhala'                => qtype_preg_charset_flag::SINHALA,
                                         'Sundanese'              => qtype_preg_charset_flag::SUNDANESE,
                                         'Syloti_Nagri'           => qtype_preg_charset_flag::SYLOTI_NAGRI,
                                         'Syriac'                 => qtype_preg_charset_flag::SYRIAC,
                                         'Tagalog'                => qtype_preg_charset_flag::TAGALOG,
                                         'Tagbanwa'               => qtype_preg_charset_flag::TAGBANWA,
                                         'Tai_Le'                 => qtype_preg_charset_flag::TAI_LE,
                                         'Tai_Tham'               => qtype_preg_charset_flag::TAI_THAM,
                                         'Tai_Viet'               => qtype_preg_charset_flag::TAI_VIET,
                                         'Tamil'                  => qtype_preg_charset_flag::TAMIL,
                                         'Telugu'                 => qtype_preg_charset_flag::TELUGU,
                                         'Thaana'                 => qtype_preg_charset_flag::THAANA,
                                         'Thai'                   => qtype_preg_charset_flag::THAI,
                                         'Tibetan'                => qtype_preg_charset_flag::TIBETAN,
                                         'Tifinagh'               => qtype_preg_charset_flag::TIFINAGH,
                                         'Ugaritic'               => qtype_preg_charset_flag::UGARITIC,
                                         'Vai'                    => qtype_preg_charset_flag::VAI,
                                         'Yi'                     => qtype_preg_charset_flag::YI
                                  );


    public function get_error_nodes() {
        return $this->errors;
    }

    public function get_max_subexpr() {
        return $this->max_subexpr;
    }

    public function get_subexpr_map() {
        return $this->subexpr_map;
    }

    public function get_backrefs() {
        return $this->backrefs;
    }

    /**
     * Returns array of error nodes.
     */
    public function mod_top_opt($set, $unset) {
        $allowed = new qtype_poasquestion_string('i');  // TODO: actual modifiers
        $setunset = new qtype_poasquestion_string($set . $unset);
        $wrongfound = '';
        $errors = array();
        $text = $this->yytext();

        // Are there unknown/unsupported modifiers?
        for ($i = 0; $i < $setunset->length(); $i++) {
            $modname = $setunset[$i];
            if ($allowed->contains($modname) === false) {
                $wrongfound .= $modname;
            }
        }
        if ($wrongfound != '') {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_UNKNOWN_MODIFIER, $wrongfound, $this->yychar, $this->yychar + $this->yylength() - 1, '');
            $errors[] = $error;
        }

        for ($i = 0; $i < $set->length(); $i++) {
            $modname = $set[$i];
            if ($unset->contains($modname) !== false && $allowed->contains($modname) !== false) {
                // Setting and unsetting modifier at the same time is error.
                $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_SET_UNSET_MODIFIER, $modname, $this->yychar, $this->yychar + $this->yylength() - 1, '');
                $errors[] = $error;
            }
        }

        // If errors don't exist, set and unset local modifiers.
        if (count($errors) == 0) {
            for ($i = 0; $i < $set->length(); $i++) {
                $modname = $set[$i];
                if (qtype_preg_unicode::strpos($allowed, $modname) !== false) {
                    $this->opt_stack[$this->opt_count - 1]->modifiers[$modname] = true;
                }
            }
            for ($i = 0; $i < $unset->length(); $i++) {
                $modname = $unset[$i];
                if (qtype_preg_unicode::strpos($allowed, $modname) !== false) {
                    $this->opt_stack[$this->opt_count - 1]->modifiers[$modname] = false;
                }
            }
        }

        return $errors;
    }

    protected function create_error_node($subtype, $addinfo, $indfirst, $indlast, $rawuserinscription) {
        // Create the error node itself.
        $error = new qtype_preg_node_error($subtype, htmlspecialchars($addinfo));
        $error->set_user_info($indfirst, $indlast, new qtype_preg_userinscription($rawuserinscription));

        // Add the node to the lexer's errors array.
        // Also add it to the charset's errors array if charset is not null.
        $this->errors[] = $error;
        if ($this->charset !== null) {
            $this->charset->errors[] = $error;
        }
        return $error;
    }

    /**
     * Returns a quantifier token.
     */
    protected function form_quant($text, $pos, $length, $infinite, $leftborder, $rightborder, $lazy, $greed, $possessive) {
        if ($infinite) {
            $node = new qtype_preg_node_infinite_quant($leftborder, $lazy, $greed, $possessive);
        } else {
            $node = new qtype_preg_node_finite_quant($leftborder, $rightborder, $lazy, $greed, $possessive);
        }
        $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
        if (!$infinite && $leftborder > $rightborder) {
            $rightoffset = 0;
            $greed || $rightoffset++;
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_INCORRECT_QUANT_RANGE, $leftborder . ',' . $rightborder, $pos + 1, $pos + $length - 2 - $rightoffset, '');
            $node->errors[] = $error;
        }
        return new JLexToken(qtype_preg_yyParser::QUANT, $node);
    }

    /**
     * Returns a control sequence token.
     */
    protected function form_control($text, $pos, $length) {
        // Error: missing ) at end.
        if (qtype_preg_unicode::substr($text, $length - 1, 1) !== ')') {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_MISSING_CONTROL_ENDING, $text, $pos, $pos + $length - 1, '');
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
        }

        switch ($text) {
        case '(*ACCEPT)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_ACCEPT);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*FAIL)':
        case '(*F)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_FAIL);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*COMMIT)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_COMMIT);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*THEN)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_THEN);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*SKIP)':
        case '(*SKIP:)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_SKIP);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*PRUNE)':
        case '(*PRUNE:)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_PRUNE);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*CR)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_CR);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*LF)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_LF);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*CRLF)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_CRLF);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*ANYCRLF)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_ANYCRLF);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*ANY)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_ANY);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*BSR_ANYCRLF)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_BSR_ANYCRLF);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*BSR_UNICODE)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_BSR_UNICODE);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*NO_START_OPT)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_NO_START_OPT);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*UTF8)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_UTF8);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*UTF16)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_UTF16);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        case '(*UCP)':
            $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_UCP);
            $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        default:
            $delimpos = qtype_preg_unicode::strpos($text, ':');

            // Error: unknown control sequence.
            if ($delimpos === false) {
                $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_UNKNOWN_CONTROL_SEQUENCE, $text, $pos, $pos + $length - 1, '');
                return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
            }

            // There is a parameter separated by ":"
            $subtype = qtype_preg_unicode::substr($text, 2, $delimpos - 2);
            $name = qtype_preg_unicode::substr($text, $delimpos + 1, $length - $delimpos - 2);

            // Error: empty name.
            if ($name === '') {
                $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_SUBEXPR_NAME_EXPECTED, $text, $pos, $pos + $length - 1, '');
                return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
            }

            if ($subtype === 'MARK' || $delimpos === 2) {
                $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_MARK_NAME, $name);
                $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
                return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
            } else if ($subtype === 'PRUNE') {
                $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_MARK_NAME, $name);
                $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
                $node2 = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_PRUNE);
                $node2->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
                return array(new JLexToken(qtype_preg_yyParser::PARSLEAF, $node),
                             new JLexToken(qtype_preg_yyParser::PARSLEAF, $node2));
            } else if ($subtype === 'SKIP') {
                $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_SKIP_NAME, $name);
                $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
                return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
            } else if ($subtype === 'THEN') {
                $node = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_MARK_NAME, $name);
                $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
                $node2 = new qtype_preg_leaf_control(qtype_preg_leaf_control::SUBTYPE_THEN);
                $node2->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
                return array(new JLexToken(qtype_preg_yyParser::PARSLEAF, $node),
                             new JLexToken(qtype_preg_yyParser::PARSLEAF, $node2));
            }

            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_UNKNOWN_CONTROL_SEQUENCE, $text, $pos, $pos + $length - 1, '');
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
        }
    }

    /**
     * Returns a named subexpression token.
     */
    protected function form_named_subexpr($text, $pos, $length, $namestartpos, $closetype) {
        $this->push_opt_lvl();

        // Error: missing closing characters.
        if (qtype_preg_unicode::substr($text, $length - 1, 1) !== $closetype) {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_MISSING_SUBEXPR_ENDING, $text, $pos, $pos + $length - 1, '');
            return new JLexToken(qtype_preg_yyParser::OPENBRACK, $error);
        }

        $name = qtype_preg_unicode::substr($text, $namestartpos, $length - $namestartpos - 1);

        // Error: empty name.
        if ($name === '') {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_SUBEXPR_NAME_EXPECTED, $text, $pos, $pos + $length - 1, '');
            return new JLexToken(qtype_preg_yyParser::OPENBRACK, $error);
        }

        $num = $this->map_subexpr($name);

        // Error: subexpressions with same names should have different numbers.
        if ($num === null) {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_DUPLICATE_SUBEXPR_NAMES, $name, $pos, $pos + $length - 1, '');
            return new JLexToken(qtype_preg_yyParser::OPENBRACK, $error);
        }

        // Are we inside a (?| group?
        $insidedup = ($this->opt_count > 1 && $this->opt_stack[$this->opt_count - 2]->subexpr_num !== -1);

        // First occurence of a named subexpression inside a (?| group.
        if ($insidedup && $this->opt_stack[$this->opt_count - 2]->subexpr_name === null) {
            $this->opt_stack[$this->opt_count - 2]->subexpr_name = $name;
        }

        // Error: different names for subexpressions of the same number.
        if ($insidedup && $this->opt_stack[$this->opt_count - 2]->subexpr_name !== $name) {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_DIFFERENT_SUBEXPR_NAMES, $text, $pos, $pos + $length - 1, '');
            return new JLexToken(qtype_preg_yyParser::OPENBRACK, $error);
        }

        return new JLexToken(qtype_preg_yyParser::OPENBRACK, new qtype_preg_lexem_subexpr(qtype_preg_node_subexpr::SUBTYPE_SUBEXPR, $pos, $pos + $length - 1, new qtype_preg_userinscription($text), $num));
    }

    /**
     * Returns a conditional subexpression token.
     */
    protected function form_cond_subexpr($text, $pos, $length, $subtype, $ending = '', $numeric = true, $namestartpos = 0) {
        $this->push_opt_lvl();

        // Conditional subexpressions with assertions is a separate story.
        if ($subtype === qtype_preg_node_cond_subexpr::SUBTYPE_PLA || $subtype === qtype_preg_node_cond_subexpr::SUBTYPE_NLA ||
            $subtype === qtype_preg_node_cond_subexpr::SUBTYPE_PLB || $subtype === qtype_preg_node_cond_subexpr::SUBTYPE_NLB) {
            $this->push_opt_lvl();
            return new JLexToken(qtype_preg_yyParser::CONDSUBEXPR, new qtype_preg_lexem($subtype, $pos, $pos + $length - 1, new qtype_preg_userinscription($text)));
        }

        $endlength = strlen($ending);

        // Error: unclosed condition.
        if (qtype_preg_unicode::substr($text, $length - $endlength) !== $ending) {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_MISSING_CONDSUBEXPR_ENDING, $text, $pos, $pos + $length - 1, '');
            return new JLexToken(qtype_preg_yyParser::OPENBRACK, $error);
        }

        $data = null;
        $secondnode = new qtype_preg_lexem(null, -1, -1, null);

        // Recursion.
        if ($subtype === qtype_preg_node_cond_subexpr::SUBTYPE_RECURSION) {
            if (qtype_preg_unicode::substr($text, 4, 1) === '&') {   // (?(R&
                $data = qtype_preg_unicode::substr($text, 5, $length - 6);
                // Error: empty name.
                if ($data === '') {
                    $secondnode = $this->create_error_node(qtype_preg_node_error::SUBTYPE_SUBEXPR_NAME_EXPECTED, $text, $pos, $pos + $length - 1, '');
                }
            } else {                                                        // (?(Rnumber)
                $tmp = qtype_preg_unicode::substr($text, 4, $length - 5);
                // Error: digits expected.
                if ($tmp !== '' && !ctype_digit($tmp)) {
                    $secondnode = $this->create_error_node(qtype_preg_node_error::SUBTYPE_WRONG_CONDSUBEXPR_NUMBER, $tmp, $pos, $pos + $length - 1, '');
                    $data = 0;
                }
                $data = (int)$tmp;
            }
        }

        // Subexpression.
        if ($subtype === qtype_preg_node_cond_subexpr::SUBTYPE_SUBEXPR && $numeric) {
            $str = qtype_preg_unicode::substr($text, 3, $length - 4);
            $tmp = qtype_preg_unicode::substr($str, 0, 1);
            $sign = 0;
            $tmp === '+' && $sign++;
            $tmp === '-' && $sign--;
            if ($sign !== 0) {
                $str = qtype_preg_unicode::substr($str, 1);
            }
            if ($str !== '' && !ctype_digit($str)) {
                // Error: digits expected.
                $secondnode = $this->create_error_node(qtype_preg_node_error::SUBTYPE_WRONG_CONDSUBEXPR_NUMBER, $str, $pos, $pos + $length - 1, '');
            } else {
                if ($sign !== 0) {
                    $data = $sign * (int)$str + $this->last_subexpr;
                    if ($sign < 0) {
                        $data++;
                    }
                } else {
                    $data = (int)$str;
                }
                // Error: reference to the whole expression.
                if ($data === 0) {
                    $secondnode = $this->create_error_node(qtype_preg_node_error::SUBTYPE_CONSUBEXPR_ZERO_CONDITION, $data, $pos, $pos + $length - 1, '');
                }
            }
        }

        if ($subtype === qtype_preg_node_cond_subexpr::SUBTYPE_SUBEXPR && !$numeric) {
            $data = qtype_preg_unicode::substr($text, $namestartpos, $length - $namestartpos - $endlength);
            // Error: empty name.
            if ($data === '') {
                $secondnode = $this->create_error_node(qtype_preg_node_error::SUBTYPE_SUBEXPR_NAME_EXPECTED, $text, $pos, $pos + $length - 1, '');
            }
        }

        // Subtype "DEFINE" has no need to be processed.

        return array(new JLexToken(qtype_preg_yyParser::CONDSUBEXPR, new qtype_preg_lexem_subexpr($subtype, $pos, $pos + $length - 1, new qtype_preg_userinscription($text), $data)),
                     new JLexToken(qtype_preg_yyParser::PARSLEAF, $secondnode),
                     new JLexToken(qtype_preg_yyParser::CLOSEBRACK, new qtype_preg_lexem(null, -1, -1, null)));
    }

    /**
     * Returns a named backreference token.
     */
    protected function form_named_backref($text, $pos, $length, $namestartpos, $opentype, $closetype) {
        // Error: missing opening characters.
        if (qtype_preg_unicode::substr($text, $namestartpos - 1, 1) !== $opentype) {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_MISSING_BACKREF_BEGINNING, $opentype, $pos, $pos + $length - 1, '');
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
        }

        // Error: missing closing characters.
        if (qtype_preg_unicode::substr($text, $length - 1, 1) !== $closetype) {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_MISSING_BACKREF_ENDING, $closetype, $pos, $pos + $length - 1, '');
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
        }

        $name = qtype_preg_unicode::substr($text, $namestartpos, $length - $namestartpos - 1);

        // Error: empty name.
        if ($name === '') {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_SUBEXPR_NAME_EXPECTED, $text, $pos, $pos + $length - 1, '');
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
        }

        $number = array_key_exists($name, $this->subexpr_map) ? $this->subexpr_map[$name] : null;
        return $this->form_backref($text, $pos, $length, $number);
    }

    /**
     * Returns a backreference token.
     */
    protected function form_backref($text, $pos, $length, $number) {
        // Error: backreference to the whole expression.
        if ($number === 0) {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_BACKREF_TO_ZERO, $text, $pos, $pos + $length - 1, '');
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
        }

        $node = new qtype_preg_leaf_backref($number);
        $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
        if (is_a($node, 'qtype_preg_leaf') && $this->opt_count > 0 && $this->opt_stack[$this->opt_count - 1]->modifiers['i']) {
            $node->caseinsensitive = true;
        }
        $this->backrefs[] = $node;
        return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
    }

    /**
     * Returns a simple assertion token.
     */
    protected function form_simple_assertion($text, $pos, $length, $subtype, $negative = false) {
        $node = new qtype_preg_leaf_assert();
        $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
        $node->subtype = $subtype;
        $node->negative = $negative;
        return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
    }

    /**
     * Returns a character set token.
     */
    protected function form_charset($text, $pos, $length, $subtype, $data, $negative = false) {
        $node = new qtype_preg_leaf_charset();
        $uitype = ($subtype === qtype_preg_charset_flag::SET) ? qtype_preg_userinscription::TYPE_GENERAL : qtype_preg_userinscription::TYPE_CHARSET_FLAG;
        $node->set_user_info($pos, $pos + $length - 1, array(new qtype_preg_userinscription($text, $uitype)));
        if (is_a($node, 'qtype_preg_leaf') && $this->opt_count > 0 && $this->opt_stack[$this->opt_count - 1]->modifiers['i']) {
            $node->caseinsensitive = true;
        }
        $node->subtype = $subtype;
        $node->israngecalculated = false;
        if ($data !== null) {
            $flag = new qtype_preg_charset_flag;
            $flag->negative = $negative;
            if ($subtype === qtype_preg_charset_flag::SET) {
                $data = new qtype_poasquestion_string($data);
            }
            $flag->set_data($subtype, $data);
            $node->flags = array(array($flag));
        }
        return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
    }

    /**
     * Returns a recursion token.
     */
    protected function form_recursion($text, $pos, $length, $number) {
        $node = new qtype_preg_leaf_recursion();
        $node->set_user_info($pos, $pos + $length - 1, new qtype_preg_userinscription($text));
        if (is_a($node, 'qtype_preg_leaf') && $this->opt_count > 0 && $this->opt_stack[$this->opt_count - 1]->modifiers['i']) {
            $node->caseinsensitive = true;
        }
        if ($number[2] === 'R') {
            $node->number = 0;
        } else {
            $node->number = (int)qtype_preg_unicode::substr($number, 2, qtype_preg_unicode::strlen($number) - 3);
        }
        return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
    }

    /**
     * Forms an interval from sequences like a-z, 0-9, etc. If a string contains
     * something like "x-z" in the end, it will be converted to "xyz".
     */
    protected function expand_charset_range() {
        // Don't expand anything if inside a \Q...\E sequence.
        if ($this->qe_sequence_length > 1) {
            return;
        }
        // Check if there are enough characters in before.
        if ($this->charset_count < 3 || qtype_preg_unicode::substr($this->charset_set, $this->charset_count - 2, 1) != '-') {
            return;
        }
        $startchar = qtype_preg_unicode::substr($this->charset_set, $this->charset_count - 3, 1);
        $endchar = qtype_preg_unicode::substr($this->charset_set, $this->charset_count - 1, 1);

        // Modify userinscription;
        $userinscriptionend = array_pop($this->charset->userinscription);
        array_pop($this->charset->userinscription);
        $userinscriptionstart = array_pop($this->charset->userinscription);
        $this->charset->userinscription[] = new qtype_preg_userinscription($userinscriptionstart->data . '-' . $userinscriptionend->data);

        if (qtype_poasquestion_string::ord($startchar) <= qtype_poasquestion_string::ord($endchar)) {
            // Replace last 3 characters by all the characters between them.
            $this->charset_set = qtype_preg_unicode::substr($this->charset_set, 0, $this->charset_count - 3);
            $this->charset_count -= 3;
            $curord = qtype_poasquestion_string::ord($startchar);
            $endord = qtype_poasquestion_string::ord($endchar);
            while ($curord <= $endord) {
                $this->charset_set .= qtype_preg_unicode::code2utf8($curord++);
                $this->charset_count++;
            }
        } else {
            // Delete last 3 characters.
            $this->charset_count -= 3;
            $this->charset_set = qtype_preg_unicode::substr($this->charset_set, 0, $this->charset_count);
            // Form the error node.
            $this->create_error_node(qtype_preg_node_error::SUBTYPE_INCORRECT_CHARSET_RANGE, $startchar . '-' . $endchar, $this->yychar - 2, $this->yychar + $this->yylength() - 1, '');
        }
    }

    protected function push_opt_lvl($subexpr_num = -1) {
        if ($this->opt_count > 0) {
            $this->opt_stack[$this->opt_count] = clone $this->opt_stack[$this->opt_count - 1];
            if ($subexpr_num !== -1) {
                $this->opt_stack[$this->opt_count]->subexpr_num = $subexpr_num;
                $this->opt_stack[$this->opt_count]->parennum = $this->opt_count;
            }
            $this->opt_stack[$this->opt_count]->subexpr_name = null;   // Reset it anyway.
            $this->opt_count++;
        } // Else the error will be found in parser, lexer does nothing for this error (closing unopened bracket).
    }

    protected function pop_opt_lvl() {
        if ($this->opt_count > 0) {
            $item = $this->opt_stack[$this->opt_count - 1];
            $this->opt_count--;
            // Is it a pair for some opening paren?
            if ($item->parennum === $this->opt_count) {
                // Are we out of a (?|...) block?
                if ($this->opt_stack[$this->opt_count - 1]->subexpr_num !== -1) {
                    // Inside.
                    $this->last_subexpr = $this->opt_stack[$this->opt_count - 1]->subexpr_num;    // Reset subexpression numeration.
                } else {
                    // Outside.
                    $this->last_subexpr = $this->max_subexpr;
                }
            }
        }
    }

    /**
     * Adds a named subexpression to the map.
     */
    protected function map_subexpr($name) {
        if (!array_key_exists($name, $this->subexpr_map)) {   // This subexpression does not exists.
            $num = ++$this->last_subexpr;
            $this->subexpr_map[$name] = $num;
        } else {                                                // Subexpressions with same names should have same numbers.
            if ($this->opt_count > 0 && $this->opt_stack[$this->opt_count - 1]->subexpr_num === -1) {
                return null;
            }
            $num = $this->subexpr_map[$name];
            $this->last_subexpr++;

        }
        $this->max_subexpr = max($this->max_subexpr, $this->last_subexpr);
        return (int)$num;
    }

    /**
     * Calculates the character for a \cx sequence.
     * @param cx the sequence itself.
     * @return character corresponding to the given sequence.
     */
    protected function calculate_cx($cx) {
        $x = qtype_preg_unicode::strtoupper(qtype_preg_unicode::substr($cx, 2));
        $code = qtype_poasquestion_string::ord($x);
        if ($code > 127) {
            return null;
        }
        $code ^= 0x40;
        return qtype_preg_unicode::code2utf8($code);
    }

    /**
     * Adds a flag to the lexer's charset when lexer is in the YYCHARSET state.
     * @param userinscription a string typed by user and consumed by lexer.
     * @param type type of the flag, should be a constant of qtype_preg_leaf_charset.
     * @param data can contain either subtype of a flag or characters for a charset.
     * @param negative is this flag negative.
     * @param appendtoend if true, new characters are concatenated from right, from left otherwise.
     */
    protected function add_flag_to_charset($text, $type, $data, $negative = false, $appendtoend = true) {
        switch ($type) {
        case qtype_preg_charset_flag::SET:
            $this->charset->userinscription[] = new qtype_preg_userinscription($text);
            $this->charset_count++;
            if ($appendtoend) {
                $this->charset_set .= $data;
            } else {
                $this->charset_set = $data . $this->charset_set;
            }
            $this->expand_charset_range();
            break;
        case qtype_preg_charset_flag::FLAG:
        case qtype_preg_charset_flag::UPROP:
            $this->charset->userinscription[] = new qtype_preg_userinscription($text, qtype_preg_userinscription::TYPE_CHARSET_FLAG);
            $flag = new qtype_preg_charset_flag;
            $flag->set_data($type, $data);
            $flag->negative = $negative;
            $this->charset->flags[] = array($flag);
            break;
        }
    }

    /**
     * Returns a unicode property flag type corresponding to the consumed string.
     * @param str string consumed by the lexer, defines the property itself.
     * @return a constant of qtype_preg_leaf_charset if this property is known, null otherwise.
     */
    protected function get_uprop_flag($str) {
        if (array_key_exists($str, self::$upropflags)) {
            $error = null;
            return self::$upropflags[$str];
        }
        return null;
    }
%}

%%

<YYINITIAL> "(?#" {                    // Comment beginning
    $this->comment = $this->yytext();
    $this->comment_length = $this->yylength();
    $this->yybegin(self::YYCOMMENT);
}
<YYCOMMENT> [^)\\]* {                  // Comment body: all characters until ')' or '\' found
    $this->comment .= $this->yytext();
    $this->comment_length += $this->yylength();
}
<YYCOMMENT> "\)"|\\\\ {                // Comment body: \) or \\
    $this->comment .= $this->yytext();
    $this->comment_length += $this->yylength();
}
<YYCOMMENT> [^)] {                     // Comment body: not ')'
    $this->comment .= $this->yytext();
    $this->comment_length += $this->yylength();
}
<YYCOMMENT> ")" {                      // Comment ending
    //$this->comment .= $this->yytext();
    //$this->comment_length += $this->yylength();
    // TODO: make use of it?
    $this->comment = '';
    $this->comment_length = 0;
    $this->yybegin(self::YYINITIAL);
}
<YYINITIAL> "?"("?"|"+")? {                     // Quantifier ?
    $text = $this->yytext();
    $greed = $this->yylength() === 1;
    $lazy = qtype_preg_unicode::substr($text, 1, 1) === '?';
    $possessive = !$greed && !$lazy;
    return $this->form_quant($text, $this->yychar, $this->yylength(), false, 0, 1, $lazy, $greed, $possessive);
}
<YYINITIAL> "*"("?"|"+")? {                     // Quantifier *
    $text = $this->yytext();
    $greed = $this->yylength() === 1;
    $lazy = qtype_preg_unicode::substr($text, 1, 1) === '?';
    $possessive = !$greed && !$lazy;
    return $this->form_quant($text, $this->yychar, $this->yylength(), true, 0, null, $lazy, $greed, $possessive);
}
<YYINITIAL> "+"("?"|"+")? {                     // Quantifier +
    $text = $this->yytext();
    $greed = $this->yylength() === 1;
    $lazy = qtype_preg_unicode::substr($text, 1, 1) === '?';
    $possessive = !$greed && !$lazy;
    return $this->form_quant($text, $this->yychar, $this->yylength(), true, 1, null, $lazy, $greed, $possessive);
}
<YYINITIAL> "{"[0-9]+","[0-9]+"}"("?"|"+")? {   // Quantifier {m,n}
    $text = $this->yytext();
    $textlen = $this->yylength();
    $lastchar = qtype_preg_unicode::substr($text, $textlen - 1, 1);
    $greed = $lastchar === '}';
    $lazy = $lastchar === '?';
    $possessive = !$greed && !$lazy;
    $greed || $textlen--;
    $delimpos = qtype_preg_unicode::strpos($text, ',');
    $leftborder = (int)qtype_preg_unicode::substr($text, 1, $delimpos - 1);
    $rightborder = (int)qtype_preg_unicode::substr($text, $delimpos + 1, $textlen - 2 - $delimpos);
    return $this->form_quant($text, $this->yychar, $this->yylength(), false, $leftborder, $rightborder, $lazy, $greed, $possessive);
}
<YYINITIAL> "{"[0-9]+",}"("?"|"+")? {           // Quantifier {m,}
    $text = $this->yytext();
    $textlen = $this->yylength();
    $lastchar = qtype_preg_unicode::substr($text, $textlen - 1, 1);
    $greed = $lastchar === '}';
    $lazy = $lastchar === '?';
    $possessive = !$greed && !$lazy;
    $greed || $textlen--;
    $leftborder = (int)qtype_preg_unicode::substr($text, 1, $textlen - 1);
    return $this->form_quant($text, $this->yychar, $this->yylength(), true, $leftborder, null, $lazy, $greed, $possessive);
}
<YYINITIAL> "{,"[0-9]+"}"("?"|"+")? {           // Quantifier {,n}
    $text = $this->yytext();
    $textlen = $this->yylength();
    $lastchar = qtype_preg_unicode::substr($text, $textlen - 1, 1);
    $greed = ($lastchar === '}');
    $lazy = !$greed && $lastchar === '?';
    $possessive = !$greed && !$lazy;
    $greed || $textlen--;
    $rightborder = (int)qtype_preg_unicode::substr($text, 2, $textlen - 3);
    return $this->form_quant($text, $this->yychar, $this->yylength(), false, 0, $rightborder, $lazy, $greed, $possessive);
}
<YYINITIAL> "{"[0-9]+"}" {                      // Quantifier {m}
    $text = $this->yytext();
    $count = (int)qtype_preg_unicode::substr($text, 1, $this->yylength() - 2);
    return $this->form_quant($text, $this->yychar, $this->yylength(), false, $count, $count, false, true, false);
}
<YYINITIAL> "[^"|"["|"[^]"|"[]" {               // Beginning of a charset: [^ or [ or [^] or []
    $text = $this->yytext();
    $this->charset = new qtype_preg_leaf_charset();
    $this->charset->indfirst = $this->yychar;
    $this->charset->negative = ($text === '[^' || $text === '[^]');
    $this->charset->userinscription = array();
    $this->charset_count = 0;
    $this->charset_set = '';
    if ($text === '[^]' || $text === '[]') {
        $this->add_flag_to_charset(']', qtype_preg_charset_flag::SET, ']');
    }
    $this->yybegin(self::YYCHARSET);
}
<YYINITIAL> "(" {                               // Beginning of a subexpression
    $this->push_opt_lvl();
    $this->last_subexpr++;
    $this->max_subexpr = max($this->max_subexpr, $this->last_subexpr);
    return new JLexToken(qtype_preg_yyParser::OPENBRACK, new qtype_preg_lexem_subexpr(qtype_preg_node_subexpr::SUBTYPE_SUBEXPR, $this->yychar, $this->yychar, new qtype_preg_userinscription('('), $this->last_subexpr));
}
<YYINITIAL> ")" {
    $this->pop_opt_lvl();
    return new JLexToken(qtype_preg_yyParser::CLOSEBRACK, new qtype_preg_lexem(0, $this->yychar, $this->yychar, new qtype_preg_userinscription(')')));
}
<YYINITIAL> "(*"[^)]*")"? {                     // Control sequence
    return $this->form_control($this->yytext(), $this->yychar, $this->yylength());
}
<YYINITIAL> "(?>" {
    $this->push_opt_lvl();
    return new JLexToken(qtype_preg_yyParser::OPENBRACK, new qtype_preg_lexem_subexpr(qtype_preg_node_subexpr::SUBTYPE_ONCEONLY, $this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription('(?>'), -1));
}
<YYINITIAL> "(?<"{ALNUM}*">"? {                 // Named subexpression (?<name>...)
    return $this->form_named_subexpr($this->yytext(), $this->yychar, $this->yylength(), 3, '>');
}
<YYINITIAL> "(?'"{ALNUM}*"'"? {                 // Named subexpression (?'name'...)
    return $this->form_named_subexpr($this->yytext(), $this->yychar, $this->yylength(), 3, '\'');
}
<YYINITIAL> "(?P<"{ALNUM}*">"? {                // Named subexpression (?P<name>...)
    return $this->form_named_subexpr($this->yytext(), $this->yychar, $this->yylength(), 4, '>');
}
<YYINITIAL> "(?:" {
    $this->push_opt_lvl();
    return new JLexToken(qtype_preg_yyParser::OPENBRACK, new qtype_preg_lexem(qtype_preg_node_subexpr::SUBTYPE_GROUPING, $this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription('(?:')));
}
<YYINITIAL> "(?|" {                             // Duplicate subexpression numbers group
    $this->push_opt_lvl($this->last_subexpr);    // Save the top-level subexpression number.
    return new JLexToken(qtype_preg_yyParser::OPENBRACK, new qtype_preg_lexem(qtype_preg_node_subexpr::SUBTYPE_GROUPING, $this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription('(?|')));
}
<YYINITIAL> "(?()" {                            // Error - empty condition
    $text = $this->yytext();
    $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_CONDSUBEXPR_ASSERT_EXPECTED, $text, $this->yychar, $this->yychar + $this->yylength() - 1, '');
    return array(new JLexToken(qtype_preg_yyParser::CONDSUBEXPR, new qtype_preg_lexem(null, $this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription(''))),
                 new JLexToken(qtype_preg_yyParser::PARSLEAF, $error),
                 new JLexToken(qtype_preg_yyParser::CLOSEBRACK, new qtype_preg_lexem(null, -1, -1, null)));
}
<YYINITIAL> "(?(?=" {                           // Conditional subexpression - assertion
    return $this->form_cond_subexpr($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_node_cond_subexpr::SUBTYPE_PLA);
}
<YYINITIAL> "(?(?!" {                           // Conditional subexpression - assertion
    return $this->form_cond_subexpr($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_node_cond_subexpr::SUBTYPE_NLA);
}
<YYINITIAL> "(?(?<=" {                          // Conditional subexpression - assertion
    return $this->form_cond_subexpr($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_node_cond_subexpr::SUBTYPE_PLB);
}
<YYINITIAL> "(?(?<!" {                          // Conditional subexpression - assertion
    return $this->form_cond_subexpr($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_node_cond_subexpr::SUBTYPE_NLB);
}
<YYINITIAL> "(?(R"[^"<>()'"]*")"? {             // Conditional subexpression - recursion
    return $this->form_cond_subexpr($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_node_cond_subexpr::SUBTYPE_RECURSION, ')');
}
<YYINITIAL> "(?(DEFINE"")"? {                   // Conditional subexpression - define
    return $this->form_cond_subexpr($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_node_cond_subexpr::SUBTYPE_DEFINE, ')');
}
<YYINITIAL> "(?(<"[^"'<>()?!="]*(">)")? {       // Conditional subexpression - named
    return $this->form_cond_subexpr($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_node_cond_subexpr::SUBTYPE_SUBEXPR, '>)', false, 4);
}
<YYINITIAL> "(?('"[^"'<>()?!="]*("')")? {       // Conditional subexpression - named
    return $this->form_cond_subexpr($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_node_cond_subexpr::SUBTYPE_SUBEXPR, '\')', false, 4);
}
<YYINITIAL> "(?("[0-9+-]+")"? {                 // Conditional subexpression - numeric
    return $this->form_cond_subexpr($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_node_cond_subexpr::SUBTYPE_SUBEXPR, ')', true);
}
<YYINITIAL> "(?("[^"'<>()?!="]*")"? {           // Conditional subexpression - named or numeric
    $text = $this->yytext();
    $rightoffset = 0;
    qtype_preg_unicode::substr($text, $this->yylength() - 1, 1) === ')' && $rightoffset++;
    $data = qtype_preg_unicode::substr($text, 3, $this->yylength() - 3 - $rightoffset);
    $sign = qtype_preg_unicode::substr($data, 0, 1);
    if ($sign === '+' || $sign === '-') {
        $data = qtype_preg_unicode::substr($data, 1);    }
    $numeric = $data !== '' && ctype_digit($data);
    return $this->form_cond_subexpr($text, $this->yychar, $this->yylength(), qtype_preg_node_cond_subexpr::SUBTYPE_SUBEXPR, ')', $numeric, 3);
}
<YYINITIAL> "(?=" {
    $this->push_opt_lvl();
    return new JLexToken(qtype_preg_yyParser::OPENBRACK, new qtype_preg_lexem(qtype_preg_node_assert::SUBTYPE_PLA, $this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription('(?=')));
}
<YYINITIAL> "(?!" {
    $this->push_opt_lvl();
    return new JLexToken(qtype_preg_yyParser::OPENBRACK, new qtype_preg_lexem(qtype_preg_node_assert::SUBTYPE_NLA, $this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription('(?!')));
}
<YYINITIAL> "(?<=" {
    $this->push_opt_lvl();
    return new JLexToken(qtype_preg_yyParser::OPENBRACK, new qtype_preg_lexem(qtype_preg_node_assert::SUBTYPE_PLB, $this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription('(?<=')));
}
<YYINITIAL> "(?<!" {
    $this->push_opt_lvl();
    return new JLexToken(qtype_preg_yyParser::OPENBRACK, new qtype_preg_lexem(qtype_preg_node_assert::SUBTYPE_NLB, $this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription('(?<!')));
}
<YYINITIAL> "(?<". {       // ERROR: Unrecognized character after (?<
    $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_UNRECOGNIZED_LBA, '(?<', $this->yychar, $this->yychar + $this->yylength() - 1, '');
    return new JLexToken(qtype_preg_yyParser::OPENBRACK, $error);
}
<YYINITIAL> "(?C"[0-9]*")"? {
    // TODO: callouts. For now this rule will return either error or exception :)
    $text = $this->yytext();
    if (qtype_preg_unicode::substr($text, $this->yylength() - 1, 1) !== ')') {
        $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_MISSING_CALLOUT_ENDING, $text, $this->yychar, $this->yychar + $this->yylength() - 1, '');
        return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
    }
    throw new Exception('Callouts are not implemented yet');
    $number = (int)qtype_preg_unicode::substr($text, 3, $this->yylength() - 4);
    if ($number > 255) {
        $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_CALLOUT_BIG_NUMBER, $text, $this->yychar, $this->yychar + $this->yylength() - 1, '');
        return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
    } else {
        // TODO
    }
}
<YYINITIAL> "." {
    return $this->form_charset($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::META_DOT);
}
<YYINITIAL> "|" {
    // Reset subexpressions numeration inside a (?|...) group.
    if ($this->opt_count > 0 && $this->opt_stack[$this->opt_count - 1]->subexpr_num != -1) {
        $this->last_subexpr = $this->opt_stack[$this->opt_count - 1]->subexpr_num;
    }
    return new JLexToken(qtype_preg_yyParser::ALT, new qtype_preg_lexem(0, $this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription('|')));
}
<YYINITIAL> "\g"[0-9][0-9]? {
    $text = $this->yytext();
    return $this->form_backref($text, $this->yychar, $this->yylength(), (int)qtype_preg_unicode::substr($text, 2));
}
<YYINITIAL> ("\g{-"|"\g{")[0-9][0-9]?"}" {
    $text = $this->yytext();
    $num = (int)qtype_preg_unicode::substr($text, 3, $this->yylength() - 4);
    // Is it a relative backreference? Is so, convert it to an absolute one.
    if ($num < 0) {
        $num = $this->last_subexpr + $num + 1;
    }
    return $this->form_backref($text, $this->yychar, $this->yylength(), $num);
}
<YYINITIAL> "\g""{"?{ALNUM}*"}"? {              // Named backreference.
    return $this->form_named_backref($this->yytext(), $this->yychar, $this->yylength(), 3, '{', '}');
}
<YYINITIAL> "\k""{"?{ALNUM}*"}"? {              // Named backreference.
    return $this->form_named_backref($this->yytext(), $this->yychar, $this->yylength(), 3, '{', '}');
}
<YYINITIAL> "\k""'"?{ALNUM}*"'"? {              // Named backreference.
    return $this->form_named_backref($this->yytext(), $this->yychar, $this->yylength(), 3, '\'', '\'');
}
<YYINITIAL> "\k""<"?{ALNUM}*">"? {              // Named backreference.
    return $this->form_named_backref($this->yytext(), $this->yychar, $this->yylength(), 3, '<', '>');
}
<YYINITIAL> "(?P""="?{ALNUM}*")"? {             // Named backreference.
    return $this->form_named_backref($this->yytext(), $this->yychar, $this->yylength(), 4, '=', ')');
}
<YYINITIAL> "\a" {
    return $this->form_charset($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x07));
}
<YYINITIAL> "\c". {
    $text = $this->yytext();
    $char = $this->calculate_cx($text);
    if ($char === null) {
        $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_CX_SHOULD_BE_ASCII, $text, $this->yychar, $this->yychar + $this->yylength() - 1, '');
        return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
    } else {
        return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, $char);
    }
}
<YYINITIAL> "\e" {
    return $this->form_charset($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x1B));
}
<YYINITIAL> "\f" {
    return $this->form_charset($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x0C));
}
<YYINITIAL> "\n" {
    return $this->form_charset($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x0A));
}
<YYINITIAL> ("\p"|"\P"). {
    $text = $this->yytext();
    $str = qtype_preg_unicode::substr($text, 2);
    $negative = (qtype_preg_unicode::substr($text, 1, 1) === 'P');
    $subtype = $this->get_uprop_flag($str);
    if ($subtype === null) {
        $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_UNKNOWN_UNICODE_PROPERTY, $str, $this->yychar, $this->yychar + $this->yylength() - 1, '');
        return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
    } else {
        return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::UPROP, $subtype, $negative);
    }
}
<YYINITIAL> ("\p"|"\P")("{^"|"{")[^}]*"}" {
    $text = $this->yytext();
    $str = qtype_preg_unicode::substr($text, 3, $this->yylength() - 4);
    $negative = (qtype_preg_unicode::substr($text, 1, 1) === 'P');
    $circumflex = (qtype_preg_unicode::substr($str, 0, 1) === '^');
    $negative = ($negative xor $circumflex);
    if ($circumflex) {
        $str = qtype_preg_unicode::substr($str, 1);
    }
    if ($str === 'Any') {
        $res = $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::META_DOT, $negative);
    } else {
        $subtype = $this->get_uprop_flag($str);
        if ($subtype === null) {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_UNKNOWN_UNICODE_PROPERTY, $str, $this->yychar, $this->yychar + $this->yylength() - 1, '');
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
        } else {
            return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::UPROP, $subtype, $negative);
        }
    }
    return $res;
}
<YYINITIAL> "\r" {
    return $this->form_charset($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x0D));
}
<YYINITIAL> "\t" {
    return $this->form_charset($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x09));
}
<YYINITIAL> "\x"[0-9a-fA-F]?[0-9a-fA-F]? {
    $text = $this->yytext();
    if ($this->yylength() < 3) {
        $str = qtype_preg_unicode::substr($text, 1);
        return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, $str);
    } else {
        $code = hexdec(qtype_preg_unicode::substr($text, 2));
        if ($code > qtype_preg_unicode::max_possible_code()) {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_CHAR_CODE_TOO_BIG, '0x' . $str, $this->yychar, $this->yychar + $this->yylength() - 1, '');
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
        } else if (0xd800 <= $code && $code <= 0xdfff) {
            $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_CHAR_CODE_DISALLOWED, '0x' . $str, $this->yychar, $this->yychar + $this->yylength() - 1, '');
            return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
        } else {
            return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8($code));
        }
    }
}
<YYINITIAL> "\x{"[0-9a-fA-F]+"}" {
    $text = $this->yytext();
    $str = qtype_preg_unicode::substr($text, 3, $this->yylength() - 4);
    $code = hexdec($str);
    if ($code > qtype_preg_unicode::max_possible_code()) {
        $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_CHAR_CODE_TOO_BIG, '0x' . $str, $this->yychar, $this->yychar + $this->yylength() - 1, '');
        return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
    } else if (0xd800 <= $code && $code <= 0xdfff) {
        $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_CHAR_CODE_DISALLOWED, '0x' . $str, $this->yychar, $this->yychar + $this->yylength() - 1, '');
        return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
    } else {
        return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8($code));
    }
}
<YYINITIAL> "\d"|"\D" {
    $text = $this->yytext();
    return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::SLASH_D, $text === '\D');
}
<YYINITIAL> "\h"|"\H" {
    $text = $this->yytext();
    return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::SLASH_H, $text === '\H');
}
<YYINITIAL> "\s"|"\S" {
    $text = $this->yytext();
    return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::SLASH_S, $text === '\S');
}
<YYINITIAL> "\v"|"\V" {
    $text = $this->yytext();
    return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::SLASH_V, $text === '\V');
}
<YYINITIAL> "\w"|"\W" {
    $text = $this->yytext();
    return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::SLASH_W, $text === '\W');
}
<YYINITIAL> "\C" {
    // TODO: matches any one data unit. For now implemented the same way as dot.
    return $this->form_charset($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::META_DOT);
}
<YYINITIAL> "\N" {
    // TODO: matches any character except new line characters. For now, the same as dot.
    return $this->form_charset($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::META_DOT);
}
<YYINITIAL> "\K" {
    // TODO: reset start of match.
    throw new Exception('\K is not implemented yet');
}
<YYINITIAL> "\R" {
    // TODO: matches new line unicode sequences.
    // \B, \R, and \X are not special inside a character class.
    throw new Exception('\R is not implemented yet');
}
<YYINITIAL> "\X" {
    // TODO: matches  any number of Unicode characters that form an extended Unicode sequence.
    // \B, \R, and \X are not special inside a character class.
    throw new Exception('\R is not implemented yet');
}
<YYINITIAL> "\b"|"\B" {
    $text = $this->yytext();
    return $this->form_simple_assertion($text, $this->yychar, $this->yylength(), qtype_preg_leaf_assert::SUBTYPE_ESC_B, $text === '\B');
}
<YYINITIAL> "\A" {
    return $this->form_simple_assertion($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_leaf_assert::SUBTYPE_ESC_A);
}
<YYINITIAL> "\z"|"\Z" {
    $text = $this->yytext();
    return $this->form_simple_assertion($text, $this->yychar, $this->yylength(), qtype_preg_leaf_assert::SUBTYPE_ESC_Z/*, $text === '\Z'*/);
}
<YYINITIAL> "^" {
    return $this->form_simple_assertion($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_leaf_assert::SUBTYPE_CIRCUMFLEX);
}
<YYINITIAL> "$" {
    return $this->form_simple_assertion($this->yytext(), $this->yychar, $this->yylength(), qtype_preg_leaf_assert::SUBTYPE_DOLLAR);
}
<YYINITIAL> "(?"{MODIFIER}*-?{MODIFIER}*")" {
    $text = $this->yytext();
    $delimpos = qtype_preg_unicode::strpos($text, '-');
    if ($delimpos !== false) {
        $set = qtype_preg_unicode::substr($text, 2, $delimpos - 2);
        $unset = qtype_preg_unicode::substr($text, $delimpos + 1, $this->yylength() - $delimpos - 2);
    } else {
        $set = qtype_preg_unicode::substr($text, 2, $this->yylength() - 3);
        $unset = '';
    }
    $set = new qtype_poasquestion_string($set);
    $unset = new qtype_poasquestion_string($unset);
    $errors = $this->mod_top_opt($set, $unset);
    if ($this->handlingoptions->preserveallnodes) {
        $node = new qtype_preg_leaf_option($set, $unset);
        $node->errors = $errors;
        $node->set_user_info($this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription($text));
        return new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
    } else {
        // Do nothing in YYINITIAL state.
    }
}
<YYINITIAL> "(?"{MODIFIER}*-?{MODIFIER}*":" {
    $text = $this->yytext();
    $delimpos = qtype_preg_unicode::strpos($text, '-');
    if ($delimpos !== false) {
        $set = qtype_preg_unicode::substr($text, 2, $delimpos - 2);
        $unset = qtype_preg_unicode::substr($text, $delimpos + 1, $this->yylength() - $delimpos - 2);
    } else {
        $set = qtype_preg_unicode::substr($text, 2, $this->yylength() - 3);
        $unset = '';
    }
    $set = new qtype_poasquestion_string($set);
    $unset = new qtype_poasquestion_string($unset);
    $this->push_opt_lvl();
    $errors = $this->mod_top_opt($set, $unset);
    if ($this->handlingoptions->preserveallnodes) {
        $node = new qtype_preg_leaf_option($set, $unset);
        $node->errors = $errors;
        $node->set_user_info($this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription($text));
        $res = array();
        $res[] = new JLexToken(qtype_preg_yyParser::OPENBRACK, new qtype_preg_lexem(qtype_preg_node_subexpr::SUBTYPE_GROUPING, $this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription($text)));
        $res[] = new JLexToken(qtype_preg_yyParser::PARSLEAF, $node);
        return $res;
    } else {
        return new JLexToken(qtype_preg_yyParser::OPENBRACK, new qtype_preg_lexem(qtype_preg_node_subexpr::SUBTYPE_GROUPING, $this->yychar, $this->yychar + $this->yylength() - 1, new qtype_preg_userinscription($text)));
    }
}
<YYINITIAL> "(?"("R"|[0-9]+)")" {
    $text = $this->yytext();
    return $this->form_recursion($text, $this->yychar, $this->yylength(), $text);
}
<YYINITIAL> "\E" {
    // Do nothing in YYINITIAL state.
}
<YYINITIAL> "\Q" {                    // \Q...\E beginning
    $this->qe_sequence = '';
    $this->qe_sequence_length = 0;
    $this->yybegin(self::YYQEOUT);
}
<YYQEOUT> .|[\r\n] {                  // \Q...\E body
    $text = $this->yytext();
    $this->qe_sequence .= $text;
    $this->qe_sequence_length++;
    return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, $text);
}
<YYQEOUT> "\E" {                      // \Q...\E ending
    $this->qe_sequence = '';
    $this->qe_sequence_length = 0;
    $this->yybegin(self::YYINITIAL);
}
<YYINITIAL> "\c" {
    $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_C_AT_END_OF_PATTERN, '\c', $this->yychar, $this->yychar + $this->yylength() - 1, '');
    return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
}
<YYINITIAL> "\G"|"\u"|"\U"|"\l"|"\L"|"\N{"{ALNUM}*"}" {
    $text = $this->yytext();
    $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_GLNU_UNSUPPORTED, $text, $this->yychar, $this->yychar + $this->yylength() - 1, '');
    return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
}
<YYINITIAL> \\[1-9][0-9]?[0-9]? {
    $text = $this->yytext();
    $str = qtype_preg_unicode::substr($text, 1);
    if ((int)$str < 10 || ((int)$str <= $this->max_subexpr && (int)$str < 100)) {
        // Return a backreference.
        $res = $this->form_backref($text, $this->yychar, $this->yylength(), (int)$str);
    } else {
        // Return a character.
        $octal = '';
        $failed = false;
        for ($i = 0; !$failed && $i < qtype_preg_unicode::strlen($str); $i++) {
            $tmp = qtype_preg_unicode::substr($str, $i, 1);
            if (intval($tmp) < 8) {
                $octal = $octal . $tmp;
            } else {
                $failed = true;
            }
        }
        if (qtype_preg_unicode::strlen($octal) === 0) {    // If no octal digits found, it should be 0.
            $octal = '0';
            $tail = $str;
        } else {                      // Octal digits found.
            $tail = qtype_preg_unicode::substr($str, qtype_preg_unicode::strlen($octal));
        }
        // Return a single lexem if all digits are octal, an array of lexems otherwise.
        if (qtype_preg_unicode::strlen($tail) === 0) {
            $res = $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(octdec($octal)));
        } else {
            $res = array();
            $res[] = $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(octdec($octal)));
            for ($i = 0; $i < qtype_preg_unicode::strlen($tail); $i++) {
                $res[] = $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::substr($tail, $i, 1));
            }
        }
    }
    return $res;
}
<YYINITIAL> \\0[0-7]?[0-7]? {
    $text = $this->yytext();
    return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(octdec(qtype_preg_unicode::substr($text, 1))));
}
<YYINITIAL> \\. {
    $text = $this->yytext();
    return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, qtype_preg_unicode::substr($text, 1, 1));
}
<YYINITIAL> \\ {           // ERROR: \ at end of pattern.
    $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_SLASH_AT_END_OF_PATTERN, '\\', $this->yychar, $this->yychar + $this->yylength() - 1, '');
    return new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
}
<YYINITIAL> . {                 // Just to avoid exceptions.
    $text = $this->yytext();
    return $this->form_charset($text, $this->yychar, $this->yylength(), qtype_preg_charset_flag::SET, $text);
}
<YYCHARSET> "\d"|"\D" {
    $text = $this->yytext();
    $negative = ($text === '\D');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::SLASH_D, $negative);
}
<YYCHARSET> "\h"|"\H" {
    $text = $this->yytext();
    $negative = ($text === '\H');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::SLASH_H, $negative);
}
<YYCHARSET> "\s"|"\S" {
    $text = $this->yytext();
    $negative = ($text === '\S');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::SLASH_S, $negative);
}
<YYCHARSET> "\v"|"\V" {
    $text = $this->yytext();
    $negative = ($text === '\V');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::SLASH_V, $negative);
}
<YYCHARSET> "\w"|"\W" {
    $text = $this->yytext();
    $negative = ($text === '\W');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::SLASH_W, $negative);
}
<YYCHARSET> "[:alnum:]"|"[:^alnum:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^alnum:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_ALNUM, $negative);
}
<YYCHARSET> "[:alpha:]"|"[:^alpha:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^alpha:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_ALPHA, $negative);
}
<YYCHARSET> "[:ascii:]"|"[:^ascii:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^ascii:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_ASCII, $negative);
}
<YYCHARSET> "[:blank:]"|"[:^blank:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^blank:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_BLANK, $negative);
}
<YYCHARSET> "[:cntrl:]"|"[:^cntrl:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^cntrl:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_CNTRL, $negative);
}
<YYCHARSET> "[:digit:]"|"[:^digit:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^digit:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_DIGIT, $negative);
}
<YYCHARSET> "[:graph:]"|"[:^graph:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^graph:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_GRAPH, $negative);
}
<YYCHARSET> "[:lower:]"|"[:^lower:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^lower:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_LOWER, $negative);
}
<YYCHARSET> "[:print:]"|"[:^print:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^print:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_PRINT, $negative);
}
<YYCHARSET> "[:punct:]"|"[:^punct:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^punct:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_PUNCT, $negative);
}
<YYCHARSET> "[:space:]"|"[:^space:]"  {
    $text = $this->yytext();
    $negative = ($text === '[:^space:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_SPACE, $negative);
}
<YYCHARSET> "[:upper:]"|"[:^upper:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^upper:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_UPPER, $negative);
}
<YYCHARSET> "[:word:]"|"[:^word:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^word:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_WORD, $negative);
}
<YYCHARSET> "[:xdigit:]"|"[:^xdigit:]" {
    $text = $this->yytext();
    $negative = ($text === '[:^xdigit:]');
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::POSIX_XDIGIT, $negative);
}
<YYCHARSET> "[:"[^\]]*":]"|"[:^"[^\]]*":]"|"[."[^\]]*".]"|"[="[^\]]*"=]" {
    $text = $this->yytext();
    $this->create_error_node(qtype_preg_node_error::SUBTYPE_UNKNOWN_POSIX_CLASS, $text, $this->yychar, $this->yychar + $this->yylength() - 1, '');
    $this->charset->userinscription[] = new qtype_preg_userinscription($text);
}
<YYCHARSET> ("\p"|"\P"). {
    $text = $this->yytext();
    $str = qtype_preg_unicode::substr($text, 2);
    $negative = (qtype_preg_unicode::substr($text, 1, 1) === 'P');
    $subtype = $this->get_uprop_flag($str);
    if ($subtype === null) {
        $this->create_error_node(qtype_preg_node_error::SUBTYPE_UNKNOWN_UNICODE_PROPERTY, $str, $this->yychar, $this->yychar + $this->yylength() - 1, '');
        $this->charset->userinscription[] = new qtype_preg_userinscription($text, qtype_preg_userinscription::TYPE_CHARSET_FLAG);
    } else {
        $this->add_flag_to_charset($text, qtype_preg_charset_flag::UPROP, $subtype, $negative);
    }
}
<YYCHARSET> ("\p"|"\P")("{^"|"{")[^}]*"}" {
    $text = $this->yytext();
    $str = qtype_preg_unicode::substr($text, 3, $this->yylength() - 4);
    $negative = (qtype_preg_unicode::substr($text, 1, 1) === 'P');
    $circumflex = (qtype_preg_unicode::substr($str, 0, 1) === '^');
    $negative = ($negative xor $circumflex);
    if ($circumflex) {
        $str = qtype_preg_unicode::substr($str, 1);
    }
    if ($str === 'Any') {
        $this->add_flag_to_charset($text, qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::META_DOT, $negative);
    } else {
        $subtype = $this->get_uprop_flag($str);
        if ($subtype === null) {
            $this->create_error_node(qtype_preg_node_error::SUBTYPE_UNKNOWN_UNICODE_PROPERTY, $str, $this->yychar, $this->yychar + $this->yylength() - 1, '');
            $this->charset->userinscription[] = new qtype_preg_userinscription($text, qtype_preg_userinscription::TYPE_CHARSET_FLAG);
        } else {
            $this->add_flag_to_charset($text, qtype_preg_charset_flag::UPROP, $subtype, $negative);
        }
    }
}
<YYCHARSET> \\[0-7][0-7]?[0-7]? {
    $text = $this->yytext();
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(octdec(qtype_preg_unicode::substr($text, 1))));
}
<YYCHARSET> "\x"[0-9a-fA-F]?[0-9a-fA-F]? {
    $text = $this->yytext();
    if ($this->yylength() < 3) {
        $str = qtype_preg_unicode::substr($text, 1);
        $this->add_flag_to_charset($text, qtype_preg_charset_flag::SET, $str);
    } else {
        $code = hexdec(qtype_preg_unicode::substr($text, 2));
        if ($code > qtype_preg_unicode::max_possible_code()) {
            $this->create_error_node(qtype_preg_node_error::SUBTYPE_CHAR_CODE_TOO_BIG, '0x' . $str, $this->yychar, $this->yychar + $this->yylength() - 1, '');
            $this->charset->userinscription[] = new qtype_preg_userinscription($text);
        } else if (0xd800 <= $code && $code <= 0xdfff) {
            $this->create_error_node(qtype_preg_node_error::SUBTYPE_CHAR_CODE_DISALLOWED, '0x' . $str, $this->yychar, $this->yychar + $this->yylength() - 1, '');
        } else {
            $this->add_flag_to_charset($text, qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8($code));
        }
    }
}
<YYCHARSET> "\x{"[0-9a-fA-F]+"}" {
    $text = $this->yytext();
    $str = qtype_preg_unicode::substr($text, 3, $this->yylength() - 4);
    $code = hexdec($str);
    if ($code > qtype_preg_unicode::max_possible_code()) {
        $this->create_error_node(qtype_preg_node_error::SUBTYPE_CHAR_CODE_TOO_BIG, '0x' . $str, $this->yychar, $this->yychar + $this->yylength() - 1, '');
        $this->charset->userinscription[] = new qtype_preg_userinscription($text);
    } else if (0xd800 <= $code && $code <= 0xdfff) {
        $this->create_error_node(qtype_preg_node_error::SUBTYPE_CHAR_CODE_DISALLOWED, '0x' . $str, $this->yychar, $this->yychar + $this->yylength() - 1, '');
    } else {
        $this->add_flag_to_charset($text, qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8($code));
    }
}
<YYCHARSET> "\a" {
    $this->add_flag_to_charset($this->yytext(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x07));
}
<YYCHARSET> "\b" {
    $this->add_flag_to_charset($this->yytext(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x08));
}
<YYCHARSET> "\c". {
    $text = $this->yytext();
    $char = $this->calculate_cx($text);
    if ($char === null) {
        $this->create_error_node(qtype_preg_node_error::SUBTYPE_CX_SHOULD_BE_ASCII, $text, $this->yychar, $this->yychar + $this->yylength() - 1, '');
        $this->charset->userinscription[] = new qtype_preg_userinscription($text);
    } else {
        $this->add_flag_to_charset($text, qtype_preg_charset_flag::SET, $char);
    }
}
<YYCHARSET> "\e" {
    $this->add_flag_to_charset($this->yytext(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x1B));
}
<YYCHARSET> "\f" {
    $this->add_flag_to_charset($this->yytext(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x0C));
}
<YYCHARSET> "\n" {
    $this->add_flag_to_charset($this->yytext(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x0A));
}
<YYCHARSET> "\N" {
    // TODO: matches any character except new line characters. For now, the same as dot.
    $this->add_flag_to_charset($this->yytext(), qtype_preg_charset_flag::FLAG, qtype_preg_charset_flag::META_DOT);
}
<YYCHARSET> "\r" {
    $this->add_flag_to_charset($this->yytext(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x0D));
}
<YYCHARSET> "\t" {
    $this->add_flag_to_charset($this->yytext(), qtype_preg_charset_flag::SET, qtype_preg_unicode::code2utf8(0x09));
}
<YYCHARSET> "\u"|"\U"|"\l"|"\L"|"\N{"{ALNUM}*"}" {
    $text = $this->yytext();
    $this->create_error_node(qtype_preg_node_error::SUBTYPE_GLNU_UNSUPPORTED, $text, $this->yychar, $this->yychar + $this->yylength() - 1, '');
    $this->charset->userinscription[] = new qtype_preg_userinscription($text);
}
<YYCHARSET> "\E" {
    // Do nothing in YYCHARSET state.
}
<YYCHARSET> "\Q" {                   // \Q...\E beginning
    $this->qe_sequence = '';
    $this->qe_sequence_length = 0;
    $this->yybegin(self::YYQEIN);
}
<YYQEIN> .|[\r\n] {                  // \Q...\E body
    $text = $this->yytext();
    $this->qe_sequence .= $text;
    $this->qe_sequence_length++;
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::SET, $text);
}
<YYQEIN> "\E" {                      // \Q...\E ending
    $this->qe_sequence = '';
    $this->qe_sequence_length = 0;
    $this->yybegin(self::YYCHARSET);
}
<YYCHARSET> \\. {
    $text = $this->yytext();
    $char = qtype_preg_unicode::substr($text, 1, 1);
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::SET, $char, false, $char !== '-');
}
<YYCHARSET> [^\]] {
    $text = $this->yytext();
    $this->add_flag_to_charset($text, qtype_preg_charset_flag::SET, $text);
}
<YYCHARSET> "]" {
    // Form the charset.
    $this->charset->indlast = $this->yychar;
    $this->charset->israngecalculated = false;
    if ($this->charset_set !== '') {
        $flag = new qtype_preg_charset_flag;
        $flag->set_data(qtype_preg_charset_flag::SET, new qtype_poasquestion_string($this->charset_set));
        $this->charset->flags[] = array($flag);
    }
    if ($this->opt_count > 0 && $this->opt_stack[$this->opt_count - 1]->modifiers['i']) {
        $this->charset->caseinsensitive = true;
    }

    // Look for possible errors.
    $ui1 = $this->charset->userinscription[0];
    $ui2 = end($this->charset->userinscription);
    if (count($this->charset->userinscription) > 1 && $ui1->data == ':' && $ui2->data == ':') {
        $error = $this->create_error_node(qtype_preg_node_error::SUBTYPE_POSIX_CLASS_OUTSIDE_CHARSET, '', $this->charset->indfirst, $this->charset->indlast, '');
        $res = new JLexToken(qtype_preg_yyParser::PARSLEAF, $error);
    } else {
        $res = new JLexToken(qtype_preg_yyParser::PARSLEAF, $this->charset);
    }

    $this->charset = null;
    $this->charset_count = 0;
    $this->charset_set = '';
    $this->yybegin(self::YYINITIAL);

    return $res;
}
