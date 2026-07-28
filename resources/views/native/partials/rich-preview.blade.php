{{--
    Renders the plugin's normalised HTML as native text blocks.

    Expects $blocks from App\Support\RichText::blocks() — headings, paragraphs,
    lists and quotes at block level; inline marks are flattened for preview
    (the stored HTML keeps them).
--}}
<column class="w-full gap-2">
    @foreach ($blocks as $block)
        @if ($block['type'] === 'h1')
            <text class="text-2xl font-bold text-theme-on-surface">{{ $block['text'] }}</text>
        @elseif ($block['type'] === 'h2')
            <text class="text-xl font-bold text-theme-on-surface">{{ $block['text'] }}</text>
        @elseif ($block['type'] === 'h3')
            <text class="text-lg font-semibold text-theme-on-surface">{{ $block['text'] }}</text>
        @elseif ($block['type'] === 'blockquote')
            <row class="w-full gap-3">
                <column class="w-[3] rounded-full bg-theme-primary" />
                <text class="flex-1 text-base italic text-theme-on-surface-variant">{{ $block['text'] }}</text>
            </row>
        @elseif ($block['type'] === 'ul')
            <column class="w-full gap-1">
                @foreach ($block['items'] as $item)
                    <row class="w-full gap-2">
                        <text class="text-base text-theme-on-surface">•</text>
                        <text class="flex-1 text-base text-theme-on-surface">{{ $item }}</text>
                    </row>
                @endforeach
            </column>
        @elseif ($block['type'] === 'ol')
            <column class="w-full gap-1">
                @foreach ($block['items'] as $item)
                    <row class="w-full gap-2">
                        <text class="text-base text-theme-on-surface">{{ $loop->iteration }}.</text>
                        <text class="flex-1 text-base text-theme-on-surface">{{ $item }}</text>
                    </row>
                @endforeach
            </column>
        @elseif ($block['text'] === '')
            <column class="w-full h-[8]" />
        @else
            <text class="text-base text-theme-on-surface">{{ $block['text'] }}</text>
        @endif
    @endforeach
</column>
