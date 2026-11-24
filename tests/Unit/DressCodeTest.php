<?php

namespace Tests\Unit;

use App\Support\Invitations\DressCode;
use PHPUnit\Framework\TestCase;

class DressCodeTest extends TestCase
{
    public function test_khmer_english_and_hex_colours_become_swatches_in_written_order(): void
    {
        $colours = DressCode::colours('សូមស្លៀកពាក់ពណ៌ក្រែម ឬ dusty pink, #C9A14A ហើយក្រហមដិត');

        $this->assertSame(['ក្រែម', 'Dusty pink', '#C9A14A', 'ក្រហមដិត'], array_column($colours, 'name'));
        $this->assertSame(['#efe4cc', '#d9a5a5', '#c9a14a', '#7d1f2b'], array_column($colours, 'hex'));
    }

    public function test_longest_word_wins_and_each_colour_shows_once(): void
    {
        // «ក្រហមដិត» is one colour, not «ក្រហម» + leftovers; «red» repeats the same hex as nothing new
        $this->assertSame(['ក្រហមដិត'], array_column(DressCode::colours('ក្រហមដិត'), 'name'));
        $this->assertCount(1, DressCode::colours('pink pink pink'));
    }

    public function test_no_colours_or_empty_text_gives_no_swatches(): void
    {
        $this->assertSame([], DressCode::colours(''));
        $this->assertSame([], DressCode::colours(null));
        $this->assertSame([], DressCode::colours('Formal attire'));
    }

    public function test_at_most_six_swatches(): void
    {
        $this->assertCount(6, DressCode::colours('red blue green yellow purple black white gold'));
    }
}
