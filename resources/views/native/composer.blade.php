{{--
    Composer example — full toolbar; preview flips between rendered blocks and
    the RAW normalised HTML the plugin returned. See App\NativeComponents\Composer.
--}}
<column fill class="safe-area bg-theme-background">

    {{-- Top bar --}}
    <row class="w-full items-center px-4 py-3">
        <pressable a11y-label="Back" class="w-[40] h-[40] items-center justify-center rounded-full" @navigate.back>
            <icon name="chevron.left" :size="22" class="text-theme-on-surface" />
        </pressable>
        <text class="flex-1 text-center text-base font-semibold text-theme-on-surface">Composer</text>
        @if ($html !== '')
            <pressable a11y-label="Toggle HTML view" class="w-[40] h-[40] items-center justify-center rounded-full" @press="toggleHtml">
                <icon name="{{ $showHtml ? 'doc' : 'chevron.left.forwardslash.chevron.right' }}" :size="18" class="text-theme-primary" />
            </pressable>
        @else
            <column class="w-[40] h-[40]" />
        @endif
    </row>

    @if ($html === '')
        <column class="w-full flex-1 items-center justify-center gap-4 px-8">
            <icon name="doc" :size="60" class="text-theme-on-surface-variant" />
            <text class="text-center text-base font-semibold text-theme-on-surface">Nothing written yet</text>
            <text class="text-center text-sm text-theme-on-surface-variant">
                Open the editor with every tool enabled — headings, lists, quotes, colors, links…
            </text>
        </column>
    @else
        <scroll-view class="w-full flex-1">
            <column class="w-full px-5 py-3">
                @if ($showHtml)
                    <column class="w-full rounded-2xl bg-theme-surface border border-theme-outline px-4 py-4">
                        <text class="text-xs font-mono text-theme-on-surface" selectable>{{ $html }}</text>
                    </column>
                @else
                    @include('native.partials.rich-preview', ['blocks' => $this->previewBlocks()])
                @endif
            </column>
        </scroll-view>
    @endif

    {{-- Bottom bar --}}
    <column class="w-full px-4 py-3 border-t border-theme-outline gap-2">
        <row
            a11y-label="{{ $html === '' ? 'Start writing' : 'Keep editing' }}"
            class="w-full items-center justify-center gap-2 rounded-full bg-theme-primary px-4 py-3"
            @press="startEdit"
        >
            <icon name="square.and.pencil" :size="18" class="text-theme-on-primary" />
            <text class="text-base font-semibold text-theme-on-primary">{{ $html === '' ? 'Start writing' : 'Keep editing' }}</text>
        </row>
        <text class="text-center text-xs text-theme-on-surface-variant">
            The toggle up top shows the exact HTML the plugin returned — clean and normalised.
        </text>
    </column>
</column>
