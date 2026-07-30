{{--
    Home — a gallery of WysiwygEditor examples. Each card opens the SAME native
    plugin configured differently (full notes editor, locked comment box,
    composer with raw HTML view, branded theme).
--}}
<column fill class="safe-area bg-theme-background px-6 py-6">
    <scroll-view class="w-full flex-1">
        <column class="w-full items-center gap-7">

            {{-- Header --}}
            <column class="w-full items-center gap-2 mt-2">
                <column class="w-16 h-16 rounded-2xl items-center justify-center bg-theme-surface-variant">
                    <icon name="doc" :size="34" class="text-theme-primary" />
                </column>
                <text font="accent" class="text-2xl font-bold text-center text-theme-on-surface">
                    WYSIWYG Editor
                </text>
                <text class="text-center text-sm text-theme-on-surface-variant px-2">
                    One configurable native rich text editor — tap an example to see it set up differently.
                </text>
            </column>

            {{-- Example cards --}}
            <column class="w-full gap-3">

                <row
                    a11y-label="Notes"
                    a11y-hint="A real notes app backed by the full editor"
                    class="w-full items-center gap-4 rounded-2xl bg-theme-surface border border-theme-outline px-4 py-4"
                    @press="openX"
                >
                    <column class="w-[44] h-[44] rounded-xl items-center justify-center bg-theme-surface-variant">
                        <icon name="bubble.left.and.bubble.right" :size="22" class="text-theme-primary" />
                    </column>
                    <column class="flex-1 gap-[2]">
                        <text class="text-base font-semibold text-theme-on-surface">X · short posts</text>
                        <text class="text-xs text-theme-on-surface-variant">Full toolbar · saved to SQLite · edit &amp; re-edit</text>
                    </column>
                    <icon name="chevron.right" :size="16" class="text-theme-on-surface-variant" />
                </row>

                    <row
                    a11y-label="Notes"
                    a11y-hint="A real notes app backed by the full editor"
                    class="w-full items-center gap-4 rounded-2xl bg-theme-surface border border-theme-outline px-4 py-4"
                    @press="openLinkedIn"
                >
                    <column class="w-[44] h-[44] rounded-xl items-center justify-center bg-theme-surface-variant">
                        <icon name="bubble.left.and.bubble.right" :size="22" class="text-theme-primary" />
                    </column>
                    <column class="flex-1 gap-[2]">
                        <text class="text-base font-semibold text-theme-on-surface">LinkedIn · long-form</text>
                        <text class="text-xs text-theme-on-surface-variant">Full toolbar · saved to SQLite · edit &amp; re-edit</text>
                    </column>
                    <icon name="chevron.right" :size="16" class="text-theme-on-surface-variant" />
                </row>

                    <row
                    a11y-label="Notes"
                    a11y-hint="A real notes app backed by the full editor"
                    class="w-full items-center gap-4 rounded-2xl bg-theme-surface border border-theme-outline px-4 py-4"
                    @press="openNotes"
                >
                    <column class="w-[44] h-[44] rounded-xl items-center justify-center bg-theme-surface-variant">
                        <icon name="note.text" :size="22" class="text-theme-primary" />
                    </column>
                    <column class="flex-1 gap-[2]">
                        <text class="text-base font-semibold text-theme-on-surface">Notes</text>
                        <text class="text-xs text-theme-on-surface-variant">Full toolbar · saved to SQLite · edit &amp; re-edit</text>
                    </column>
                    <icon name="chevron.right" :size="16" class="text-theme-on-surface-variant" />
                </row>

                <row
                    a11y-label="Comment Box"
                    a11y-hint="A minimal bold-italic-link reply editor with a length cap"
                    class="w-full items-center gap-4 rounded-2xl bg-theme-surface border border-theme-outline px-4 py-4"
                    @press="openComments"
                >
                    <column class="w-[44] h-[44] rounded-xl items-center justify-center bg-theme-surface-variant">
                        <icon name="text.bubble" :size="22" class="text-theme-primary" />
                    </column>
                    <column class="flex-1 gap-[2]">
                        <text class="text-base font-semibold text-theme-on-surface">Comment Box</text>
                        <text class="text-xs text-theme-on-surface-variant">Bold / italic / link only · 500-char cap · like a reply</text>
                    </column>
                    <icon name="chevron.right" :size="16" class="text-theme-on-surface-variant" />
                </row>

                <row
                    a11y-label="Composer"
                    a11y-hint="Every tool enabled, with the raw HTML output visible"
                    class="w-full items-center gap-4 rounded-2xl bg-theme-surface border border-theme-outline px-4 py-4"
                    @press="openComposer"
                >
                    <column class="w-[44] h-[44] rounded-xl items-center justify-center bg-theme-surface-variant">
                        <icon name="chevron.left.forwardslash.chevron.right" :size="22" class="text-theme-primary" />
                    </column>
                    <column class="flex-1 gap-[2]">
                        <text class="text-base font-semibold text-theme-on-surface">Composer</text>
                        <text class="text-xs text-theme-on-surface-variant">Every tool · rendered preview or the raw HTML</text>
                    </column>
                    <icon name="chevron.right" :size="16" class="text-theme-on-surface-variant" />
                </row>

                <row
                    a11y-label="Branded Theme"
                    a11y-hint="The editor recoloured to match the host app"
                    class="w-full items-center gap-4 rounded-2xl bg-theme-surface border border-theme-outline px-4 py-4"
                    @press="openThemed"
                >
                    <column class="w-[44] h-[44] rounded-xl items-center justify-center bg-theme-surface-variant">
                        <icon name="slider.horizontal.3" :size="22" class="text-theme-primary" />
                    </column>
                    <column class="flex-1 gap-[2]">
                        <text class="text-base font-semibold text-theme-on-surface">Branded Theme</text>
                        <text class="text-xs text-theme-on-surface-variant">Custom background · accent · highlight colors</text>
                    </column>
                    <icon name="chevron.right" :size="16" class="text-theme-on-surface-variant" />
                </row>

            </column>

            <text class="text-center text-xs text-theme-on-surface-variant px-4">
                Same plugin, different config — presets, toolbar &amp; theme. On Save the editor
                returns clean HTML plus a plain-text rendition via an event.
            </text>

        </column>
    </scroll-view>
</column>
