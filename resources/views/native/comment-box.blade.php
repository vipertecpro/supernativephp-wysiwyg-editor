{{--
    Comment box example — a fake thread; replies use the locked-down `comment`
    preset (bold / italic / link, 500 chars). See App\NativeComponents\CommentBox.
--}}
<column fill class="safe-area bg-theme-background">

    {{-- Top bar --}}
    <row class="w-full items-center px-4 py-3">
        <pressable a11y-label="Back" class="w-[40] h-[40] items-center justify-center rounded-full" @navigate.back>
            <icon name="chevron.left" :size="22" class="text-theme-on-surface" />
        </pressable>
        <text class="flex-1 text-center text-base font-semibold text-theme-on-surface">Comments</text>
        <column class="w-[40] h-[40]" />
    </row>

    <scroll-view class="w-full flex-1">
        <column class="w-full gap-3 px-4 py-2">
            @foreach ($comments as $comment)
                <column class="w-full gap-1 rounded-2xl bg-theme-surface border border-theme-outline px-4 py-3">
                    <text class="text-xs font-semibold text-theme-primary">{{ $comment['author'] }}</text>
                    <text class="text-sm text-theme-on-surface">{{ $comment['text'] }}</text>
                </column>
            @endforeach
        </column>
    </scroll-view>

    {{-- Reply bar --}}
    <column class="w-full px-4 py-3 border-t border-theme-outline gap-2">
        <row
            a11y-label="Add comment"
            a11y-hint="Opens a minimal editor: bold, italic and links only"
            class="w-full items-center gap-3 rounded-full bg-theme-surface border border-theme-outline px-4 py-3"
            @press="startEdit"
        >
            <icon name="text.bubble" :size="18" class="text-theme-on-surface-variant" />
            <text class="flex-1 text-sm text-theme-on-surface-variant">Write a comment…</text>
            <icon name="textformat" :size="16" class="text-theme-primary" />
        </row>
        <text class="text-center text-xs text-theme-on-surface-variant">
            `comment` preset · bold / italic / link · 500-character cap with live counter
        </text>
    </column>
</column>
