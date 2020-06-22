<?php
// This file is part of Preg question type - https://bitbucket.org/oasychev/moodle-plugins/overview
//
// Preg question type is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/preg/fa_matcher/fa_matcher.php');

use PHPUnit\Framework\TestCase;

class qtype_preg_fa_fuzzy_pcre_tests extends TestCase
{
    protected $optionwith2typos;
    protected $optionwith3typos;
    protected $optionwith4typos;

    public function setUp()
    {
        parent::setUp();

        $option = new qtype_preg_matching_options();
        $option->approximatematch = true;
        $option->langid = null;
        $option->mergeassertions = true;

        $this->optionwith2typos = clone $option;
        $this->optionwith2typos->typolimit = 2;

        $this->optionwith3typos = clone $option;
        $this->optionwith3typos->typolimit = 3;

        $this->optionwith4typos = clone $option;
        $this->optionwith4typos->typolimit = 4;
    }

    public function test_simple_asserts1() {
        $regex = 'a((\b\t|bde)g)+f';

        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'a	gP	gf';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('a	g	gf', $result->typos->apply());

        $str = 'ag  f';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(2, $result->typos->count());
        $this->assertEquals('a	gf f', $result->typos->apply());
    }

    public function test_simple_asserts2() {
        $regex = '\b([a!]b)+c';

        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = '  aabJc';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(2, $result->typos->count());
        $this->assertEquals('  abcbJc', $result->typos->apply());

        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = '  ababuc';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(2, $result->typos->count());
        $this->assertEquals(' ababc', $result->typos->apply());
    }


    public function test_backref1() {
        $regex = '(a(b|c)d)\1';

        // correct string
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'abdabd';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(0, $result->typos->count());

        // insertion of unambiguous char in submatch
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'ababd';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('abdabd', $result->typos->apply());

        // insertion of unambiguous char in backref
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'abdbd';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('abdabd', $result->typos->apply());

        // substitution of unambiguous char in backref
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'abdabbd';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('abdabdd', $result->typos->apply());

        // transposition between submatch & backref
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'abadbd';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('abdabd', $result->typos->apply());

        // deletion of unambiguous char in backref
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'abdaabd';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('abdabd', $result->typos->apply());

        // insertion of ambiguous char in submatch
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'adacd';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('acdacd', $result->typos->apply());

        // insertion of ambiguous char in backref
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'acdad';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('acdacd', $result->typos->apply());

        // insertion of ambiguous char in submatch & backref
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'adad';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(2, $result->typos->count());
        $this->assertEquals('abdabd', $result->typos->apply());
    }

    public function test_backref2() {
        $regex = '(a[bc][de]f)\1';

        // equal typo count in backref and submatch
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = '!ceface';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(2, $result->typos->count());
        $this->assertEquals('acefacef', $result->typos->apply());

        // mutual typo dependency between backref and submatch
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'ab!fa!ef';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(2, $result->typos->count());
        $this->assertEquals('abefabef', $result->typos->apply());

        // more typos in submatch
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith3typos);
        $str = 'afacf';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(3, $result->typos->count());
        $this->assertEquals('acefacef', $result->typos->apply());

        // more typos in backref
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith3typos);
        $str = 'acfaf';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(3, $result->typos->count());
        $this->assertEquals('acefacef', $result->typos->apply());
    }

    public function test_recursion1() {
        $regex = '^(\d+|\((?1)([+*-])(?1)\)|-(?1))$';
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);

        // correct answer
        $str = '(((2+2)*-3)-7)';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(0, $result->typos->count());

        // in-level transpose
        $str = '(((2+2)*3-)-7)';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('(((2+2)*-3)-7)', $result->typos->apply());

        // cross-level transpose
        $str = '(((2+2*)-3)-7)';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('(((2+2)*-3)-7)', $result->typos->apply());

        // redundant bracket
        $str = '(((2+2)*-3-7)';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('(((2+2)*-3)-7)', $result->typos->apply());
    }

    public function test_recursion2() {
        $regex = '^([^()]|\((?1)*\))*$';

        // correct
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = '(((2+2)*-3)-7)';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(0, $result->typos->count());

        // typos1
        $str = '((())';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('((a))', $result->typos->apply());

        // typos2
        $str = '(df(s(dd)sdf';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('(df(s)dd)sdf', $result->typos->apply());
    }

    public function test_positive_complex_asserts() {
        $regex = '([a-z])*(?=(|efk)g(?<=((abd)+c|efk)g))(g|[a-f])';
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'bdcg';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('abdcg', $result->typos->apply());

        $regex = 'a(bc(?<=([a-z][a-k])+)d)+';
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'aafbacd';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('aafabcd', $result->typos->apply());

        $regex = 'a(bc(?<=([a-z][a-k])+)d)+';
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'aafacd';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('aafabcd', $result->typos->apply());

        $regex = 'b(a|c)$(?<=b[ac])';
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);
        $str = 'acb';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('bcb', $result->typos->apply());
    }

    public function test_conditional_asserts1() {
        $regex = '(a)?b(?(1)c|d)';
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);

        $str = 'a';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(2, $result->typos->count());
        $this->assertEquals('bd', $result->typos->apply());

        $str = '';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(2, $result->typos->count());
        $this->assertEquals('bd', $result->typos->apply());

        $str = 'bac';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('abc', $result->typos->apply());

        $str = 'acb';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('abc', $result->typos->apply());

        $str = 'bc';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('bd', $result->typos->apply());

        $str = 'abX';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('abc', $result->typos->apply());
    }

    public function test_conditional_asserts2() {
        $regex = '^((x)|(y)) (?(2)abcd|xz)';
        $matcher = new qtype_preg_fa_matcher($regex, $this->optionwith2typos);

        $str = 'x axzd';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(2, $result->typos->count());
        $this->assertEquals('x abcd', $result->typos->apply());

        // this is fails now
        $str = 'x xz';
        $result = $matcher->match($str);
        $this->assertTrue($result->full);
        $this->assertEquals(1, $result->typos->count());
        $this->assertEquals('y xz', $result->typos->apply());
    }
}
