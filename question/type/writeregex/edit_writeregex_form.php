<?php
// This file is part of WriteRegex question type - https://code.google.com/p/oasychev-moodle-plugins/
//
// WriteRegex is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// WriteRegex is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edited form for question type Write Regex.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/shortanswer/edit_shortanswer_form.php');
require_once($CFG->dirroot . '/question/type/preg/questiontype.php');
require_once($CFG->dirroot . '/question/type/writeregex/questiontype.php');

/**
 * Edited form for question type Write Regex.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_writeregex_edit_form extends qtype_shortanswer_edit_form {

    /** @var array Array of hints types. */
    private $hintsoptions = array();

    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function definition_inner($mform) {

        global $CFG;

        $menu = array(
            get_string('caseno', 'qtype_shortanswer'),
            get_string('caseyes', 'qtype_shortanswer')
        );
        $mform->addElement('select', 'usecase',
            get_string('casesensitive', 'qtype_shortanswer'), $menu);

        // Init hints options.
        $this->hintsoptions = array(
            '0' => get_string('none', 'qtype_writeregex'),
            '1' => get_string('student', 'qtype_writeregex'),
            '2' => get_string('answer', 'qtype_writeregex'),
            '3' => get_string('both', 'qtype_writeregex')
        );

        // Include preg.
        $pregclass = 'qtype_preg';
        $pregquestionobj = new $pregclass;

        // Add engines.
        $engines = $pregquestionobj->available_engines();
        $mform->addElement('select', 'engine', get_string('engine', 'qtype_preg'), $engines);
        $mform->setDefault('engine', $CFG->qtype_preg_defaultengine);
        $mform->addHelpButton('engine', 'engine', 'qtype_preg');

        // Add notations.
        $notations = $pregquestionobj->available_notations();
        $mform->addElement('select', 'notation', get_string('notation', 'qtype_preg'), $notations);
        $mform->setDefault('notation', $CFG->qtype_preg_defaultnotation);
        $mform->addHelpButton('notation', 'notation', 'qtype_preg');

        // Add syntax tree options.
        $mform->addElement('select', 'syntaxtreehinttype', get_string('wre_st', 'qtype_writeregex'),
            $this->hintsoptions);
        $mform->addHelpButton('syntaxtreehinttype', 'syntaxtreehinttype', 'qtype_writeregex');
        $mform->addElement('text', 'syntaxtreehintpenalty',
            get_string('penalty', 'qtype_writeregex'));
        $mform->setType('syntaxtreehintpenalty', PARAM_FLOAT);
        $mform->setDefault('syntaxtreehintpenalty', '0.0000000');
        $mform->addHelpButton('syntaxtreehintpenalty', 'syntaxtreehintpenalty', 'qtype_writeregex');

        // Add explaining graph options.
        $mform->addElement('select', 'explgraphhinttype', get_string('wre_eg', 'qtype_writeregex'),
            $this->hintsoptions);
        $mform->addHelpButton('explgraphhinttype', 'explgraphhinttype', 'qtype_writeregex');
        $mform->addElement('text', 'explgraphhintpenalty',
            get_string('penalty', 'qtype_writeregex'));
        $mform->addHelpButton('explgraphhintpenalty', 'explgraphhintpenalty', 'qtype_writeregex');
        $mform->setType('explgraphhintpenalty', PARAM_FLOAT);
        $mform->setDefault('explgraphhintpenalty', '0.0000000');

        // Add description options.
        $mform->addElement('select', 'descriptionhinttype', get_string('wre_d', 'qtype_writeregex'),
            $this->hintsoptions);
        $mform->addHelpButton('descriptionhinttype', 'descriptionhinttype', 'qtype_writeregex');
        $mform->addElement('text', 'descriptionhintpenalty',
            get_string('penalty', 'qtype_writeregex'));
        $mform->setType('descriptionhintpenalty', PARAM_FLOAT);
        $mform->setDefault('descriptionhintpenalty', '0.0000000');
        $mform->addHelpButton('descriptionhintpenalty', 'descriptionhintpenalty', 'qtype_writeregex');

        // Add test string option.
        $mform->addElement('select', 'teststringshinttype', get_string('teststrings', 'qtype_writeregex'),
            $this->hintsoptions);
        $mform->addHelpButton('teststringshinttype', 'teststringshinttype', 'qtype_writeregex');
        $mform->addElement('text', 'teststringshintpenalty',
            get_string('penalty', 'qtype_writeregex'));
        $mform->setType('teststringshintpenalty', PARAM_FLOAT);
        $mform->setDefault('teststringshintpenalty', '0.0000000');
        $mform->addHelpButton('teststringshintpenalty', 'teststringshintpenalty', 'qtype_writeregex');

        // Add compare regex percentage.
        $mform->addElement('text', 'compareregexpercentage',
            get_string('wre_cre_percentage', 'qtype_writeregex'));
        $mform->setType('compareregexpercentage', PARAM_FLOAT);
        $mform->setDefault('compareregexpercentage', '0');
        $mform->addHelpButton('compareregexpercentage', 'compareregexpercentage', 'qtype_writeregex');

        // Add compare regexps automata percentage.
        $mform->addElement('text', 'compareautomatapercentage',
            get_string('compareautomatapercentage', 'qtype_writeregex'));
        $mform->setType('compareautomatapercentage', PARAM_FLOAT);
        $mform->setDefault('compareautomatapercentage', '0');
        $mform->addHelpButton('compareautomatapercentage', 'compareautomatapercentage', 'qtype_writeregex');

        // Add compare regexp by test strings.
        $mform->addElement('text', 'compareregexpteststrings',
            get_string('compareregexpteststrings', 'qtype_writeregex'));
        $mform->setType('compareregexpteststrings', PARAM_FLOAT);
        $mform->setDefault('compareregexpteststrings', '100');
        $mform->addHelpButton('compareregexpteststrings', 'compareregexpteststrings', 'qtype_writeregex');

        // Add answers fields.
        $this->add_per_answer_fields($mform, 'wre_regexp_answers',
            question_bank::fraction_options());

        $this->add_per_answer_fields($mform, 'wre_regexp_ts',
            question_bank::fraction_options());

        $this->add_interactive_settings();
    }

    /**
     * Prepare answers data.
     * @param object $question Question's object.
     * @param bool $withanswerfiles If question has answers files.
     * @return object Question's object.
     */
    protected function data_preprocessing_answers($question, $withanswerfiles = false) {

        if (empty($question->options->answers)) {
            return $question;
        }

        // Separate answers to regexes and test strings.
        $key = 0;
        $index = 0;
        foreach ($question->options->answers as $answer) {

            if ($answer->answerformat != qtype_writeregex::TEST_STRING_ANSWER_FORMAT_VALUE) {
                $question->answer[$key] = $answer->answer;
                unset($this->_form->_defaultValues["fraction[$key]"]);
                $question->fraction[$key] = $answer->fraction;
                $question->feedback[$key] = array();
                $question->feedback[$key]['text'] = $answer->feedback;
                $question->feedback[$key]['format'] = $answer->feedbackformat;
                $key++;
            } else if ($answer->answerformat == qtype_writeregex::TEST_STRING_ANSWER_FORMAT_VALUE) {

                $question->wre_regexp_ts_answer[$index] = $answer->answer;
                $question->wre_regexp_ts_fraction[$index] = $answer->fraction;
                $index++;
            }

        }

        return $question;

    }

    /**
     * Validate form fields
     * @param array $data Forms data.
     * @param array $files Files.
     * @return array Errors array.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $errors = $this->validate_compare_values($data, $errors);

        $errors = $this->validate_test_strings($data, $errors, $data['compareregexpteststrings']);

        $errors = $this->validate_regexp($data, $errors);

        return $errors;
    }

    /**
     * Validate regexp answers.
     * @param $data array Forms data.
     * @param $errors array Errors array.
     * @return array Errors array.
     */
    private function validate_regexp ($data, $errors) {

        global $CFG;
        $test = $data['compareregexpteststrings'];

        if ($test > 0 && $test <= 100) {

            $pregquestionobj = new qtype_preg_question();
            $answers = $data['answer'];
            $i = 0;

            foreach ($answers as $key => $answer) {
                $trimmedanswer = trim($answer);
                if ($trimmedanswer !== '') {
                    $matcher = $pregquestionobj->get_matcher($data['engine'], $trimmedanswer, false,
                        $pregquestionobj->get_modifiers($data['usecase']), (-1) * $i, $data['notation']);

                    if ($matcher->errors_exist()) {
                        $regexerrors = $matcher->get_error_messages($CFG->qtype_writregex_maxerrorsshown);
                        $errors['answer['.$key.']'] = '';
                        foreach ($regexerrors as $item) {
                            $errors['answer['.$key.']'] .= $item . '<br />';
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Validation test string answer.
     * @param $data array Data from form.
     * @param $errors array Errors array.
     * @param $test string Value of compare by test strings.
     * @return array Errors array.
     */
    private function validate_test_strings ($data, $errors, $test) {
        $strings = $data['wre_regexp_ts_answer'];
        $answercount = 0;
        $sumgrade = 0;

        foreach ($strings as $key => $item) {
            $trimdata = trim($item);
            if ($trimdata !== '') {
                $answercount++;
                $sumgrade = $sumgrade + $data['wre_regexp_ts_fraction'][$key];
            }
        }

        if ($sumgrade != 1 and $test > 0) {
            $errors["wre_regexp_ts_answer[0]"] = get_string('invalidtssumvalue', 'qtype_writeregex');
        }

        if ($answercount > 0 and $test == 0) {
            $errors['compareregexpteststrings'] = get_string('invalidcomparets', 'qtype_writeregex');
        }

        return $errors;
    }

    /**
     * Validation values of compare values.
     * @param $data array Data from form.
     * @param $errors array Errors array.
     * @return array Errors array.
     */
    private function validate_compare_values ($data, $errors) {

        $regex = $data['compareregexpercentage'];
        $automata = $data['compareautomatapercentage'];
        $test = $data['compareregexpteststrings'];
        $flag = false;

        if ($regex < 0 || $regex > 100) {
            $errors['compareregexpercentage'] = get_string('compareinvalidvalue', 'qtype_writeregex');
            $flag = true;
        }

        if ($automata < 0 || $automata > 100) {
            $errors['compareautomatapercentage'] = get_string('compareinvalidvalue', 'qtype_writeregex');
        }

        if ($test < 0 || $test > 100) {
            $errors['compareregexpteststrings'] = get_string('compareinvalidvalue', 'qtype_writeregex');
        }

        if ($regex + $automata + $test != 100 and !$flag) {
            $errors['compareregexpercentage'] = get_string('wre_error_matching', 'qtype_writeregex');
        }

        return $errors;
    }

    /**
     * Insert fields for regexp answers.
     * @param $mform MoodleForm Form variable.
     * @param $label string Label of group fields.
     * @param $gradeoptions array Grade options array.
     * @param $repeatedoptions array Repeated options.
     * @param $answersoption array Answers option
     * @return array Group of fields.
     */
    private function  get_per_answer_fields_regexp($mform, $label, $gradeoptions,
                                                   &$repeatedoptions, &$answersoption) {

        $repeated = array();
        $answeroptions = array();
        $answeroptions[] = $mform->CreateElement('hidden', 'freply', 'yes');
        $repeated[] = $mform->createElement('group', 'answeroptions',
            '', $answeroptions, null, false);
        $repeated[] = $mform->createElement('textarea', 'answer',
            get_string($label, 'qtype_writeregex'), 'wrap="virtual" rows="4" cols="80"');
        $repeated[] = $mform->createElement('select', 'fraction',
            get_string('grade'), $gradeoptions);
        $repeated[] = $mform->createElement('editor', 'feedback',
            get_string('feedback', 'question'), array('rows' => 8, 'cols' => 80), $this->editoroptions);
        $repeatedoptions['freply']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $answersoption = 'answers';
        return $repeated;

    }

    /**
     * Insert fields for test strings answers.
     * @param $mform MoodleForm Form variable.
     * @param $label string Label of group fields.
     * @param $gradeoptions array Grade options array.
     * @param $repeatedoptions array Repeated options.
     * @param $answersoption array Answers option
     * @return array Group of fields.
     */
    private function  get_per_answer_fields_strings($mform, $label, $gradeoptions,
                                                    &$repeatedoptions, &$answersoption) {
        $repeated = array();

        $repeated [] =& $mform->createElement('textarea', $label . '_answer',
            get_string($label, 'qtype_writeregex'), 'wrap="virtual" rows="2" cols="80"', $this->editoroptions);

        $repeated[] =& $mform->createElement('select', $label . '_fraction', get_string('grade'), $gradeoptions);

        $repeatedoptions[$label . '_answer']['type'] = PARAM_RAW;
        $repeatedoptions['test_string_id']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $answersoption = $label;

        return $repeated;
    }

    /**
     * Add fields for test strings answers.
     * @param $mform MoodleForm Form variable.
     * @param $label string Label of group fields.
     * @param $gradeoptions array Grade options array.
     * @param int $minoptions Min options value.
     * @param int $addoptions Additional options value
     */
    private function add_test_strings(&$mform, $label, $gradeoptions,
                                      $minoptions = QUESTION_NUMANS_START, $addoptions = QUESTION_NUMANS_ADD) {
        $mform->addElement('header', 'teststrhdr', get_string($label, 'qtype_writeregex'), '');
        $mform->setExpanded('teststrhdr', 1);

        $answersoption = '';
        $repeatedoptions = array();
        $repeated = $this->get_per_answer_fields_strings($mform, $label, $gradeoptions,
            $repeatedoptions, $answersoption);

        $repeatsatstart = 5;
        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions,
            'noanswers', 'addanswers', $addoptions,
            $this->get_more_choices_string(), true);
    }

    /**
     * Add fields for regexp strings answers.
     * @param $mform MoodleForm Form variable.
     * @param $label string Label of group fields.
     * @param $gradeoptions array Grade options array.
     * @param int $minoptions Min options value.
     * @param int $addoptions Additional options value
     */
    private function add_regexp_strings(&$mform, $label, $gradeoptions,
                                        $minoptions = QUESTION_NUMANS_START, $addoptions = QUESTION_NUMANS_ADD) {
        $mform->addElement('header', 'answerhdr', get_string($label, 'qtype_writeregex'), '');
        $mform->setExpanded('answerhdr', 1);

        $answersoption = '';
        $repeatedoptions = array();
        $repeated = $this->get_per_answer_fields_regexp($mform, $label, $gradeoptions,
            $repeatedoptions, $answersoption);

        if (isset($this->question->options)) {
            $repeatsatstart = count($this->question->options->$answersoption);
        } else {
            $repeatsatstart = $minoptions;
        }

        $addoptions = 1;
        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions,
            'noanswers', 'addanswers', $addoptions,
            $this->get_more_choices_string(), true);
    }

    /**
     * Add a set of form fields, obtained from get_per_answer_fields, to the form,
     * one for each existing answer, with some blanks for some new ones.
     * @param object $mform the form being built.
     * @param the $label the label to use for each option.
     * @param the $gradeoptions the possible grades for each answer.
     * @param int|the $minoptions the minimum number of answer blanks to display.
     * @param int|the $addoptions the number of answer blanks to add.
     */
    protected function add_per_answer_fields(&$mform, $label, $gradeoptions,
                                             $minoptions = QUESTION_NUMANS_START, $addoptions = QUESTION_NUMANS_ADD) {
        // Select type of answers fields.
        if ($label == 'wre_regexp_ts') {
            $this->add_test_strings($mform, $label, $gradeoptions, $minoptions, $addoptions);
        } else {
            $this->add_regexp_strings($mform, $label, $gradeoptions, $minoptions, $addoptions);
        }
    }

    /**
     * Get value string for label which showing text for more choices string.
     * @return string String from lang file.
     */
    protected function get_more_choices_string() {
        return get_string('addmorechoiceblanks', 'question');
    }

    /**
     * Create the form elements required by one hint.
     * @param bool $withclearwrong whether this quesiton type uses the 'Clear wrong' option on hints.
     * @param bool $withshownumpartscorrect whether this quesiton type uses the 'Show num parts correct' option on hints.
     * @return array form field elements for one hint.
     */
    protected function get_hint_fields($withclearwrong = false, $withshownumpartscorrect = false) {
        $repeated = array();

        $parentresult = parent::get_hint_fields($withclearwrong, $withshownumpartscorrect);

        // Add our inputs.
        $mform = $this->_form;
        $count = count($parentresult[0]);

        // Add syntax tree options.
        $repeated[$count++] = $mform->createElement('select', 'syntaxtreehint', get_string('wre_st', 'qtype_writeregex'),
            $this->hintsoptions);

        // Add explaining graph options.
        $repeated[$count++] = $mform->createElement('select', 'explgraphhint', get_string('wre_eg', 'qtype_writeregex'),
            $this->hintsoptions);

        // Add description options.
        $repeated[$count++] = $mform->createElement('select', 'descriptionhint', get_string('wre_d', 'qtype_writeregex'),
            $this->hintsoptions);

        // Add test string option.
        $repeated[$count] = $mform->createElement('select', 'teststringshint', get_string('teststrings', 'qtype_writeregex'),
            $this->hintsoptions);

        $parentresult[0] = array_merge($parentresult[0], $repeated);

        return $parentresult;
    }

    /**
     * Function which create hints array for form
     * @param object $question Question object.
     * @param bool $withclearwrong (do not use)
     * @param bool $withshownumpartscorrect (do not use)
     * @return object Question object.
     */
    protected function data_preprocessing_hints($question, $withclearwrong = false,
                                                $withshownumpartscorrect = false) {
        if (empty($question->hints)) {
            return $question;
        }

        $question = parent::data_preprocessing_hints($question, $withclearwrong, $withshownumpartscorrect);

        foreach ($question->hints as $hint) {

            $options = explode('\n', $hint->options);

            if (count($options) == 4) {

                $question->syntaxtreehint[] = $options[0];
                $question->explgraphhint[] = $options[1];
                $question->descriptionhint[] = $options[2];
                $question->teststringshint[] = $options[3];
            } else {
                $question->syntaxtreehint[] = 0;
                $question->explgraphhint[] = 0;
                $question->descriptionhint[] = 0;
                $question->teststringshint[] = 0;
            }
        }

        return $question;
    }

    /**
     * Get qtype name.
     * @return string Name of qtype.
     */
    public function qtype() {

        return 'writeregex';
    }
}