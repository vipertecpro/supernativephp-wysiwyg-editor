{{--
    Demo 4 — block-based writing in the shape of Notion.
    See App\NativeComponents\NotionPages.

    A page list rather than a feed: no author, no reactions, no audience. What
    a page has instead is an icon and a title, and the title is the first line
    of the document rather than a field somebody filled in separately.

    The part worth looking at is inside: `/` opens a command menu, and a to-do
    keeps its tick across a save.
--}}
<column class="w-full h-full bg-theme-background safe-area">

    {{-- The workspace, which is all Notion keeps up here. --}}
    <row class="w-full items-center justify-between px-4 py-3">
        <row @navigate.back a11y-label="Back to examples" class="items-center gap-2">
            <column class="w-[28] h-[28] rounded-md bg-theme-surface-variant items-center justify-center">
                <text class="text-[15]">🗒️</text>
            </column>
            <text class="text-[17] font-semibold text-theme-on-surface">My workspace</text>
            <icon name="chevron.down" :size="14" class="text-theme-on-surface-variant" />
        </row>
        <column @press="newPage" a11y-label="New page" class="w-[32] h-[32] items-center justify-center">
            <icon name="square.and.pencil" :size="20" class="text-theme-on-surface" />
        </column>
    </row>

    <divider class="w-full" />

    @if ($pages->isEmpty())
        <column class="w-full flex-1 items-center justify-center gap-3 px-10">
            <text class="text-[40]">🗒️</text>
            <text class="text-[17] font-bold text-theme-on-surface">No pages yet</text>
            <text class="text-center text-[14] text-theme-on-surface-variant">
                Start one, then type <text class="font-semibold text-theme-on-surface">/</text> to pick a block.
            </text>
            <column @press="newPage" a11y-label="New page" class="rounded-lg bg-theme-primary px-4 py-2 mt-2">
                <text class="text-[15] font-semibold text-theme-on-primary">New page</text>
            </column>
        </column>
    @else
        <scroll-view class="w-full flex-1">
            <column class="w-full py-1">
                @foreach ($pages as $page)
                    <row class="w-full items-center gap-3 px-4 py-3">
                        {{-- The icon is its own target: tapping it picks a new
                             one rather than opening the page. --}}
                        <column
                            @press="chooseIcon({{ $page->id }})"
                            a11y-label="Change icon"
                            class="w-[34] h-[34] rounded-md bg-theme-surface-variant items-center justify-center"
                        >
                            <text class="text-[18]">{{ $page->icon }}</text>
                        </column>

                        <column @press="openPage({{ $page->id }})" a11y-label="Open page" class="flex-1">
                            <text class="text-[16] font-medium text-theme-on-surface">{{ $page->title() }}</text>
                            <row class="items-center gap-2 pt-[2]">
                                <text class="text-[13] text-theme-on-surface-variant">{{ $page->edited() }}</text>
                                @if ($page->excerpt() !== '')
                                    <text class="flex-1 text-[13] text-theme-on-surface-variant">{{ \Illuminate\Support\Str::limit($page->excerpt(), 40) }}</text>
                                @endif
                            </row>
                        </column>

                        <column
                            @press="showActions({{ $page->id }})"
                            a11y-label="Page options"
                            class="w-[32] h-[32] items-center justify-center"
                        >
                            <icon name="ellipsis" :size="18" class="text-theme-on-surface-variant" />
                        </column>
                    </row>
                @endforeach

                <column class="w-full h-[16]" />
            </column>
        </scroll-view>
    @endif

    {{-- The `···` menu. Deleting asks first, in the SAME sheet: a system alert
         chained off a sheet's dismissal is silently refused by iOS. --}}
    <bottom-sheet :visible="$actionsFor !== null" detents="small" @dismiss="closeActions">
        @if ($actionsFor !== null)
            @if ($confirmingDelete)
                <column class="w-full py-2">
                    <column class="w-full px-6 py-4 gap-1">
                        <text class="text-base font-semibold text-theme-on-surface">Delete page?</text>
                        <text class="text-sm text-theme-on-surface-variant">This cannot be undone.</text>
                    </column>

                    <divider class="w-full" />

                    <row @press="delete({{ $actionsFor }})" a11y-label="Confirm delete" class="w-full items-center gap-4 px-6 py-4">
                        <icon name="trash" :size="20" class="text-[#EF4444]" />
                        <text class="text-base font-semibold text-[#EF4444]">Delete</text>
                    </row>

                    <divider class="w-full" />

                    <row @press="cancelDelete" a11y-label="Keep page" class="w-full items-center gap-4 px-6 py-4">
                        <icon name="xmark" :size="20" class="text-theme-on-surface" />
                        <text class="text-base text-theme-on-surface">Keep</text>
                    </row>
                </column>
            @else
                <column class="w-full py-2">
                    <row @press="openPage({{ $actionsFor }})" a11y-label="Open page" class="w-full items-center gap-4 px-6 py-4">
                        <icon name="square.and.pencil" :size="20" class="text-theme-on-surface" />
                        <text class="text-base text-theme-on-surface">Open</text>
                    </row>

                    <divider class="w-full" />

                    <row @press="chooseIcon({{ $actionsFor }})" a11y-label="Change icon" class="w-full items-center gap-4 px-6 py-4">
                        <icon name="face.smiling" :size="20" class="text-theme-on-surface" />
                        <text class="text-base text-theme-on-surface">Change icon</text>
                    </row>

                    <divider class="w-full" />

                    <row @press="confirmDelete" a11y-label="Delete page" class="w-full items-center gap-4 px-6 py-4">
                        <icon name="trash" :size="20" class="text-[#EF4444]" />
                        <text class="text-base text-[#EF4444]">Delete page</text>
                    </row>
                </column>
            @endif
        @endif
    </bottom-sheet>

    {{-- Choosing a page's face. Drawn by US, because we are outside the editor
         here — the editor's own sheets are for when it owns the screen. --}}
    <bottom-sheet :visible="$iconFor !== null" detents="small" @dismiss="closeIcons">
        @if ($iconFor !== null)
            <column class="w-full py-2">
                <column class="w-full px-6 py-3">
                    <text class="text-base font-semibold text-theme-on-surface">Page icon</text>
                </column>

                @foreach (array_chunk($icons, 6) as $row)
                    <row class="w-full px-4 py-1">
                        @foreach ($row as $icon)
                            <column
                                @press="setIcon('{{ $icon }}')"
                                a11y-label="Use {{ $icon }}"
                                class="flex-1 h-[48] items-center justify-center"
                            >
                                <text class="text-[26]">{{ $icon }}</text>
                            </column>
                        @endforeach
                    </row>
                @endforeach

                <column class="w-full h-[8]" />
            </column>
        @endif
    </bottom-sheet>
</column>
