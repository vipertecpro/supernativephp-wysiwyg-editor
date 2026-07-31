{{--
    Demo 5 — native mobile interactions, in the shape of Apple Notes.
    See App\NativeComponents\AppleNotes.

    There is no Save button anywhere in this demo. The editor writes the note
    away as you type; the list below shows the result of that, not of anything
    you pressed.

    Folders, pinning and swipe-to-delete are filing rather than editing, which
    is why the editor knows nothing about any of them.
--}}
<column class="w-full h-full bg-theme-background safe-area">

    <row class="w-full items-center justify-between px-4 py-3">
        <row @navigate.back a11y-label="Back to examples" class="items-center gap-1">
            <icon name="chevron.left" :size="20" class="text-[#F59E0B]" />
            <text class="text-[17] text-[#F59E0B]">Folders</text>
        </row>
        <text class="text-[17] font-semibold text-theme-on-surface">{{ $folder }}</text>
        <column @press="newNote" a11y-label="New note" class="w-[32] h-[32] items-center justify-center">
            <icon name="square.and.pencil" :size="20" class="text-[#F59E0B]" />
        </column>
    </row>

    {{-- The folders, as a row you can flick along. --}}
    <scroll-view horizontal class="w-full">
        <row class="items-center gap-2 px-4 pb-3">
            @foreach ($folders as $name)
                <row
                    @press="showFolder('{{ $name }}')"
                    a11y-label="{{ $name }}"
                    class="items-center gap-2 rounded-full px-4 py-2 {{ $folder === $name ? 'bg-[#F59E0B]' : 'bg-theme-surface-variant' }}"
                >
                    <text class="text-[14] {{ $folder === $name ? 'font-semibold text-[#FFFFFF]' : 'text-theme-on-surface' }}">
                        {{ $name }}
                    </text>
                    <text class="text-[12] {{ $folder === $name ? 'text-[#FFFFFF]' : 'text-theme-on-surface-variant' }}">
                        {{ $counts[$name] ?? 0 }}
                    </text>
                </row>
            @endforeach
        </row>
    </scroll-view>

    @if ($notes->isEmpty())
        <column class="w-full flex-1 items-center justify-center gap-3 px-10">
            <icon name="note.text" :size="44" class="text-theme-on-surface-variant" />
            <text class="text-[17] font-bold text-theme-on-surface">No notes in {{ $folder }}</text>
            <text class="text-center text-[14] text-theme-on-surface-variant">
                Start one — there is no Save button, because there is nothing to save.
            </text>
        </column>
    @else
        {{-- A NativeUI list rather than a scroll-view of rows, because swipe
             actions live in the list renderer: `@swipeDelete` on a hand-built
             row compiles fine and then does nothing, since nothing is there to
             read it. The row markup below is unchanged — the list renders
             whatever child it is given and only adds the gesture. --}}
        <native:list class="w-full flex-1">
                @foreach ($notes as $note)
                    <column
                        @press="openNote({{ $note->id }})"
                        @swipeDelete="deleteNote({{ $note->id }})"
                        a11y-label="{{ $note->title() }}"
                        class="w-full rounded-xl bg-theme-surface px-4 py-3 mb-2"
                    >
                        <row class="w-full items-center gap-2">
                            @if ($note->pinned)
                                <icon name="mappin" :size="13" class="text-[#F59E0B]" />
                            @endif
                            <text class="flex-1 text-[16] font-semibold text-theme-on-surface">{{ $note->title() }}</text>
                            <pressable
                                @press="togglePin({{ $note->id }})"
                                a11y-label="{{ $note->pinned ? 'Unpin' : 'Pin' }}"
                                class="w-[28] h-[28] items-center justify-center"
                            >
                                <icon name="mappin.circle" :size="18" class="{{ $note->pinned ? 'text-[#F59E0B]' : 'text-theme-on-surface-variant' }}" />
                            </pressable>
                        </row>

                        <row class="w-full items-center gap-2 pt-[2]">
                            <text class="text-[13] text-theme-on-surface-variant">{{ $note->age() }}</text>
                            <text class="flex-1 text-[13] text-theme-on-surface-variant">
                                {{ \Illuminate\Support\Str::limit($note->excerpt(), 42) ?: 'No additional text' }}
                            </text>
                        </row>
                    </column>
                @endforeach
        </native:list>
    @endif

    {{-- What autosave looks like from out here: a count that goes up on its
         own while you type, with nothing to press. --}}
    <row class="w-full items-center justify-center gap-2 py-2">
        <icon name="checkmark.circle" :size="14" class="text-theme-on-surface-variant" />
        <text class="text-[12] text-theme-on-surface-variant">
            Saved automatically — {{ $notes->count() }} {{ $notes->count() === 1 ? 'note' : 'notes' }} in {{ $folder }}
        </text>
    </row>
</column>
