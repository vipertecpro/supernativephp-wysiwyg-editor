{{--
    Branded theme example — the same editor recoloured with the host app's
    palette. See App\NativeComponents\ThemedEditor.
--}}
<column fill class="safe-area bg-[#121417]">

    {{-- Top bar --}}
    <row class="w-full items-center px-4 py-3">
        <pressable a11y-label="Back" class="w-[40] h-[40] items-center justify-center rounded-full" @navigate.back>
            <icon name="chevron.left" :size="22" class="text-white" />
        </pressable>
        <text class="flex-1 text-center text-base font-semibold text-white">Branded Theme</text>
        <column class="w-[40] h-[40]" />
    </row>

    <scroll-view class="w-full flex-1">
        <column class="w-full gap-5 px-5 py-3">

            {{-- Palette swatches --}}
            <column class="w-full gap-3 rounded-2xl bg-white/5 border border-white/10 px-4 py-4">
                <text class="text-sm font-semibold text-white">This screen's palette drives the editor</text>
                <row class="w-full gap-3">
                    <column class="flex-1 items-center gap-1">
                        <column class="w-[36] h-[36] rounded-full bg-[#121417] border border-white/20" />
                        <text class="text-xs text-white/60">background</text>
                    </column>
                    <column class="flex-1 items-center gap-1">
                        <column class="w-[36] h-[36] rounded-full bg-white" />
                        <text class="text-xs text-white/60">text</text>
                    </column>
                    <column class="flex-1 items-center gap-1">
                        <column class="w-[36] h-[36] rounded-full bg-[#F97316]" />
                        <text class="text-xs text-white/60">accent</text>
                    </column>
                    <column class="flex-1 items-center gap-1">
                        <column class="w-[36] h-[36] rounded-full bg-[#22C55E]" />
                        <text class="text-xs text-white/60">highlight</text>
                    </column>
                </row>
            </column>

            @if ($html !== '')
                <column class="w-full rounded-2xl bg-white/5 border border-white/10 px-4 py-4">
                    <text class="text-xs font-semibold text-white/60 mb-2">Last saved</text>
                    <text class="text-sm text-white">{{ \App\Support\RichText::excerpt($text, 240) }}</text>
                </column>
            @endif

        </column>
    </scroll-view>

    {{-- Bottom bar --}}
    <column class="w-full px-4 py-3 border-t border-white/10 gap-2">
        <row
            a11y-label="Open branded editor"
            class="w-full items-center justify-center gap-2 rounded-full bg-[#F97316] px-4 py-3"
            @press="startEdit"
        >
            <icon name="paintpalette" :size="18" class="text-white" />
            <text class="text-base font-semibold text-white">Open branded editor</text>
        </row>
        <text class="text-center text-xs text-white/50">
            `note` preset · theme keys: background, text, accent, highlight — omitted keys stay system-adaptive.
        </text>
    </column>
</column>
