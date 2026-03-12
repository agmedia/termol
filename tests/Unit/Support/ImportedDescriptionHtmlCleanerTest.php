<?php

namespace Tests\Unit\Support;

use App\Support\ImportedDescriptionHtmlCleaner;
use PHPUnit\Framework\TestCase;

class ImportedDescriptionHtmlCleanerTest extends TestCase
{
    public function test_it_removes_span_tags_and_inline_styles_but_keeps_useful_html(): void
    {
        $cleaner = new ImportedDescriptionHtmlCleaner();

        $cleaned = $cleaner->clean(
            '<style>.x{color:red}</style><p style="color:red">Hello <span style="font-weight:bold">world</span></p><ul><li><span>One</span></li></ul>'
        );

        $this->assertSame('<p>Hello world</p><ul><li>One</li></ul>', $cleaned);
    }

    public function test_it_decodes_html_entities_before_cleaning(): void
    {
        $cleaner = new ImportedDescriptionHtmlCleaner();

        $cleaned = $cleaner->clean('&lt;p style=&quot;color:red&quot;&gt;A &lt;span&gt;B&lt;/span&gt;&lt;/p&gt;');

        $this->assertSame('<p>A B</p>', $cleaned);
    }
}
