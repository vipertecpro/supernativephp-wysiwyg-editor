{{--
    Notes example — a SQLite-backed notes list; every note is written with the
    full native editor. See App\NativeComponents\Notes.
--}}
<column fill class="safe-area bg-theme-background">

    {{-- Top bar --}}
    <row class="w-full items-center px-4 py-3">
        <pressable a11y-label="Back" class="w-[40] h-[40] items-center justify-center rounded-full" @navigate.back>
            <icon name="chevron.left" :size="22" class="text-theme-on-surface" />
        </pressable>
        <text class="flex-1 text-center text-base font-semibold text-theme-on-surface">Notes</text>
        <pressable a11y-label="New note" class="w-[40] h-[40] items-center justify-center rounded-full" @press="newNote">
            <icon name="square.and.pencil" :size="20" class="text-theme-primary" />
        </pressable>
    </row>

    @if ($notes->isEmpty())
        <column class="w-full flex-1 items-center justify-center gap-4 px-8">
            <icon name="doc" :size="60" class="text-theme-on-surface-variant" />
            <text class="text-center text-base font-semibold text-theme-on-surface">No notes yet</text>
            <text class="text-center text-sm text-theme-on-surface-variant">
                Tap the pencil to write your first note with the native editor.
            </text>
        </column>
    @else
        <scroll-view class="w-full flex-1">
            <column class="w-full gap-3 px-4 py-2">
                @foreach ($notes as $note)
                    <row
                        a11y-label="Edit note"
                        class="w-full items-center gap-3 rounded-2xl bg-theme-surface border border-theme-outline px-4 py-4"
                        @press="editNote({{ $note->id }})"
                    >
                        <column class="flex-1 gap-[2]">
                            <text class="text-base font-semibold text-theme-on-surface">{{ $note->title() }}</text>
                            @if ($note->excerpt() !== '')
                                <text class="text-xs text-theme-on-surface-variant">{{ $note->excerpt() }}</text>
                            @endif
                            <text class="text-xs text-theme-on-surface-variant">{{ $note->updated_at->diffForHumans() }}</text>
                        </column>
                        <pressable a11y-label="Delete note" class="w-[36] h-[36] items-center justify-center rounded-full" @press="deleteNote({{ $note->id }})">
                            <icon name="trash" :size="16" class="text-theme-on-surface-variant" />
                        </pressable>
                    </row>
                @endforeach
            </column>
        </scroll-view>

        <text class="text-center text-xs text-theme-on-surface-variant px-6 py-3">
            Tap a note to re-open it in the editor — the saved HTML round-trips losslessly.
        </text>
    @endif
</column>
