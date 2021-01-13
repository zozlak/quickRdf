<?php

/*
 * The MIT License
 *
 * Copyright 2021 zozlak.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace dumbrdf;

use rdfHelpers\NtriplesUtil;

/**
 * Description of Serializer
 *
 * @author zozlak
 */
class NquadsSerializer implements \rdfInterface\Serializer
{

    public function __construct()
    {
    }

    public function serialise(
        \rdfInterface\QuadIterator $graph,
        ?\rdfInterface\RdfNamespace $nmsp = null
    ): string {
        $output = '';
        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            throw new RdfException('Failed to convert input to stream');
        }
        $this->serialiseStream($stream, $graph, $nmsp);
        $len = ftell($stream);
        if ($len === false) {
            throw new RdfException('Failed to seek in output streem');
        }
        rewind($stream);
        $output = fread($stream, $len);
        if ($output === false) {
            throw new RdfException('Failed to read from output streem');
        }
        fclose($stream);

        return $output;
    }

    public function serialiseStream(
        $output,
        \rdfInterface\QuadIterator $graph,
        ?\rdfInterface\RdfNamespace $nmsp = null
    ): void {
        if (!is_resource($output)) {
            throw new RdfException("output has to be a resource");
        }
        foreach ($graph as $i) {
            /* @var $i \rdfInterface\Quad */
            $subject   = NtriplesUtil::serialiseIri($i->getSubject());
            $predicate = '<' . NtriplesUtil::escapeIri($i->getPredicate()->getValue()) . '>';
            $object    = NtriplesUtil::serialise($i->getObject());
            $graph     = $i->getGraphIri();
            if ($graph !== null) {
                $graph = NtriplesUtil::serialiseIri($graph);
            }

            fwrite($output, "$subject $predicate $object $graph .\n");
        }
    }
}
