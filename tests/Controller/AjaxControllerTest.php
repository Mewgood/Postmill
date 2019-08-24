<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

/**
 * @covers \App\Controller\AjaxController
 */
class AjaxControllerTest extends WebTestCase {
    public function testMarkdownPreview(): void {
        $client = self::createUserClient();
        $client->request('POST', '/md', [], [], [
            'CONTENT_TYPE' => 'text/html; charset=UTF-8',
        ], <<<EOMARKDOWN
# This is a test

This is a test of the markdown endpoint.

1. what's
2. up
EOMARKDOWN
        );

        $this->assertEquals(<<<EOHTML
<h1>This is a test</h1>
<p>This is a test of the markdown endpoint.</p>
<ol>
<li>what's</li>
<li>up</li>
</ol>\n
EOHTML
            , $client->getResponse()->getContent()
        );
    }
}
