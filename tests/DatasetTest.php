<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace quickRdf;

use rdfHelpers\GenericQuadIterator;
use rdfInterface\Literal as iLiteral;
use termTemplates\LiteralTemplate;
use termTemplates\QuadTemplate;

/**
 * Description of LoggerTest
 *
 * @author zozlak
 */
class DatasetTest extends \rdfInterface\tests\DatasetTest {

    use TestTrait;

    public function testOffsetGetNoIndex(): void {
        $d = new Dataset(false);
        $d->add(new GenericQuadIterator(self::$quads));
        $q = $d[static::getQuadTemplate(self::$quads[1]->getSubject())];
        $this->assertTrue(self::$quads[1]->equals($q));
    }

    /**
     * Corner cases for findByIndices()
     * @return void
     */
    public function testFindByIndices(): void {
        foreach ([0, 1] as $indexed) {
            // single non-indexable term
            $qt = new QuadTemplate(null, null, new LiteralTemplate(null, LiteralTemplate::EQUALS, ''));
            //0 <foo> <bar> "baz"
            //1 <baz> <foo> <bar>
            //2 <bar> <baz> <foo>
            //3 <foo> <bar> "baz"@en <graph>
            $d  = new Dataset((bool) $indexed);
            $d->add(new GenericQuadIterator(self::$quads));

            $dd = $d->copy($qt);
            $this->assertCount(1, $dd, "Indexed: $indexed");
            foreach ($dd as $i) {
                $obj = $i?->getObject();
                $this->assertTrue($obj instanceof iLiteral && $obj->getLang() === 'en', "Indexed: $indexed");
            }

            $dd = $d->copyExcept($qt);
            $this->assertCount(3, $dd, "Indexed: $indexed");
            foreach ($dd as $i) {
                $obj = $i?->getObject();
                $this->assertTrue(!($obj instanceof iLiteral) || $obj->getLang() === null, "Indexed: $indexed");
            }

            // mix of indexable and non-indexable terms
            $qt = new QuadTemplate(static::$df::namedNode('foo'), null, new LiteralTemplate(null, LiteralTemplate::EQUALS, ''));
            $d->add(self::$quads[3]->withSubject(self::$quads[1]->getSubject()));
            //0 <foo> <bar> "baz"
            //1 <baz> <foo> <bar>
            //2 <bar> <baz> <foo>
            //3 <foo> <bar> "baz"@en <graph>
            //4 <baz> <bar> "baz"@en <graph>

            $dd = $d->copy($qt);
            $this->assertCount(1, $dd);
            foreach ($dd as $i) {
                $obj = $i?->getObject();
                $this->assertEquals('foo', $i?->getSubject()?->getValue(), "Indexed: $indexed");
                $this->assertTrue($obj instanceof iLiteral && $obj->getLang() === 'en', "Indexed: $indexed");
            }

            $dd = $d->copyExcept($qt);
            $this->assertCount(4, $dd, "Indexed: $indexed");
            foreach ($dd as $i) {
                $obj = $i?->getObject();
                $this->assertTrue(!($obj instanceof iLiteral) || $obj->getLang() === null || $i?->getSubject()->getValue() !== 'foo', "Indexed: $indexed");
            }
        }
    }
}
