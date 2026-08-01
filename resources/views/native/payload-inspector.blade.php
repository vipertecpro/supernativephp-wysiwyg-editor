{{--
    Demo 0 — what your server actually receives.
    See App\NativeComponents\PayloadInspector.

    Deliberately plain: this screen is a window onto the data, so the data is
    the only thing on it. Monospaced where the content is a payload, because
    the difference between `<p>` and `<br>` matters when you are reading it.
--}}
<column class="w-full h-full bg-theme-background safe-area">

    <row class="w-full items-center gap-3 px-4 py-3">
        <pressable @navigate.back a11y-label="Back to examples" class="w-[32] h-[32] items-center justify-center">
            <icon name="chevron.left" :size="20" class="text-theme-primary" />
        </pressable>
        <column class="flex-1">
            <text class="text-[17] font-semibold text-theme-on-surface">The payload</text>
            <text class="text-[12] text-theme-on-surface-variant">
                What the editor hands you, and what you would send on
            </text>
        </column>
    </row>

    <row class="w-full items-center gap-2 px-4 pb-3">
        <pressable @press="compose" a11y-label="Write something" class="flex-1 items-center rounded-xl bg-theme-primary py-3">
            <text class="text-[15] font-semibold text-theme-on-primary">
                {{ $html === '' ? 'Write something' : 'Keep editing' }}
            </text>
        </pressable>
        @if ($changes > 0)
            <column class="items-center rounded-xl bg-theme-surface-variant px-3 py-3">
                <text class="text-[12] text-theme-on-surface-variant">
                    {{ $changes }} {{ $changes === 1 ? 'change' : 'changes' }}{{ $saved ? ' · sent' : '' }}
                </text>
            </column>
            {{-- Start over. Without this the screen can only be demonstrated
                 once per launch. --}}
            <pressable @press="reset" a11y-label="Reset the screen" class="w-[44] h-[44] items-center justify-center rounded-xl bg-theme-surface-variant">
                <icon name="arrow.2.circlepath" :size="18" class="text-theme-on-surface" />
            </pressable>
        @endif
    </row>

    {{-- Somewhere to prove the thing holds up. A coder passing a scale test
         off-device is not the same as an editor being usable with 600 blocks
         in it, and this is how you check the second one. --}}
    <row class="w-full items-center gap-2 px-4 pb-3">
        <pressable @press="loadHeavy" a11y-label="Load a large document" class="flex-1 items-center rounded-xl bg-theme-surface-variant py-3">
            <text class="text-[13] text-theme-on-surface">Stress it — open {{ \App\NativeComponents\PayloadInspector::HEAVY_BLOCKS }} blocks</text>
        </pressable>
    </row>

    {{-- The panes. Sizes live on the tabs because "which format do I store"
         is partly a question about how big each one is. --}}
    <scroll-view horizontal class="w-full">
        <row class="items-center gap-2 px-4 pb-3">
            @foreach ([
                ['id' => 'html', 'label' => 'HTML', 'note' => $sizes['html']],
                ['id' => 'text', 'label' => 'Text', 'note' => $sizes['text']],
                ['id' => 'json', 'label' => 'JSON', 'note' => $sizes['json']],
                ['id' => 'files', 'label' => 'Files', 'note' => count($files)],
                ['id' => 'log', 'label' => 'Log', 'note' => count($log)],
            ] as $tab)
                <pressable
                    @press="show('{{ $tab['id'] }}')"
                    a11y-label="{{ $tab['label'] }}"
                    class="items-center gap-1 rounded-full px-4 py-2 {{ $pane === $tab['id'] ? 'bg-theme-primary' : 'bg-theme-surface-variant' }}"
                >
                    <row class="items-center gap-2">
                        <text class="text-[14] {{ $pane === $tab['id'] ? 'font-semibold text-theme-on-primary' : 'text-theme-on-surface' }}">
                            {{ $tab['label'] }}
                        </text>
                        <text class="text-[11] {{ $pane === $tab['id'] ? 'text-theme-on-primary' : 'text-theme-on-surface-variant' }}">
                            {{ $tab['note'] }}
                        </text>
                    </row>
                </pressable>
            @endforeach
        </row>
    </scroll-view>

    <scroll-view class="w-full flex-1">
        <column class="w-full px-4 pb-6 gap-3">

            @if ($html === '' && $pane !== 'log')
                <column class="w-full items-center gap-3 py-16 px-6">
                    <icon name="doc" :size="40" class="text-theme-on-surface-variant" />
                    <text class="text-[16] font-semibold text-theme-on-surface">Nothing written yet</text>
                    <text class="text-center text-[13] text-theme-on-surface-variant">
                        Write a line and add a picture — every pane here fills in as you type,
                        and the Log records each event as it arrives.
                    </text>
                </column>

            @elseif ($pane === 'html')
                <text class="text-[12] text-theme-on-surface-variant">
                    Normalised and safe to render. This is what you store to display the
                    document again — note the picture is a reference, not the bytes.
                </text>
                <column class="w-full rounded-xl bg-theme-surface p-3">
                    <text font="mono" class="text-[12] text-theme-on-surface">{{ $html }}</text>
                </column>

            @elseif ($pane === 'text')
                <text class="text-[12] text-theme-on-surface-variant">
                    The plain rendition — what you index for search, or show as a preview
                    in a list. Markers survive as characters; marks do not.
                </text>
                <column class="w-full rounded-xl bg-theme-surface p-3">
                    <text font="mono" class="text-[12] text-theme-on-surface">{{ $text ?: '—' }}</text>
                </column>

            @elseif ($pane === 'json')
                <text class="text-[12] text-theme-on-surface-variant">
                    The fidelity format. The ONLY one that carries which to-dos are ticked
                    and which uploads are still in flight — so it is what you re-open the
                    editor with.
                </text>
                <column class="w-full rounded-xl bg-theme-surface p-3">
                    <text font="mono" class="text-[12] text-theme-on-surface">{{ $json ?: '—' }}</text>
                </column>

            @elseif ($pane === 'files')
                <text class="text-[12] text-theme-on-surface-variant">
                    <text class="font-semibold">WysiwygEditor::attachments($json)</text> — the files, split
                    out of the document. Their bytes are in none of the three formats; send
                    them wherever you send bytes.
                </text>

                @if ($optimization !== '')
                    <row class="w-full items-center gap-2 rounded-xl bg-theme-surface-variant px-3 py-3">
                        <icon name="arrow.down" :size="16" class="text-theme-primary" />
                        <text class="flex-1 text-[12] text-theme-on-surface">
                            Last optimize — {{ $optimization }}
                        </text>
                    </row>
                @endif

                @forelse ($files as $file)
                    <column class="w-full rounded-xl bg-theme-surface p-3 gap-2">
                        <row class="w-full items-center gap-2">
                            <text class="flex-1 text-[14] font-semibold text-theme-on-surface">
                                {{ $file['kind'] }} · {{ $file['size'] }}
                            </text>
                            <text class="text-[11] rounded-full px-2 py-1 {{ $file['state'] === 'pending' ? 'bg-theme-primary text-theme-on-primary' : 'bg-theme-surface-variant text-theme-on-surface-variant' }}">
                                {{ $file['state'] }}
                            </text>
                        </row>

                        {{-- Both paths, separately. `src` is what the document
                             points at; `localPath` is the file on this device.
                             They are not the same thing and a screen about the
                             payload must not pretend they are. --}}
                        <column class="w-full gap-[2]">
                            <text font="mono" class="text-[11] text-theme-on-surface-variant">src&nbsp;&nbsp;&nbsp;{{ $file['src'] }}</text>
                            <text font="mono" class="text-[11] text-theme-on-surface-variant">local&nbsp;{{ $file['local'] }}</text>
                        </column>

                        @if ($file['uploadId'] !== '')
                            <text font="mono" class="text-[11] text-theme-primary">
                                uploadId {{ $file['uploadId'] }} — still in flight
                            </text>
                        @endif

                        @if ($file['missing'])
                            <text class="text-[11] text-[#EF4444]">
                                The local copy is gone — the system cleared the cache it was in.
                            </text>
                        @endif
                    </column>
                @empty
                    <column class="w-full rounded-xl bg-theme-surface p-4 items-center">
                        <text class="text-[13] text-theme-on-surface-variant">
                            No files in this document yet.
                        </text>
                    </column>
                @endforelse

            @else
                <row class="w-full items-center gap-2">
                    <text class="flex-1 text-[12] text-theme-on-surface-variant">
                        Every event the editor fired and every call back into it, newest first.
                        Failures are caught and recorded — on a device they would otherwise
                        vanish without a trace.
                    </text>
                    @if (count($log) > 0)
                        <pressable @press="clearLog" a11y-label="Clear the log" class="rounded-full bg-theme-surface-variant px-3 py-2">
                            <text class="text-[12] text-theme-on-surface">Clear</text>
                        </pressable>
                    @endif
                </row>

                @forelse ($log as $entry)
                    <column class="w-full rounded-xl bg-theme-surface p-3 gap-1">
                        <row class="w-full items-center gap-2">
                            @if ($entry['kind'] === 'failure')
                                <icon name="exclamationmark.triangle" :size="13" class="text-[#EF4444]" />
                            @elseif ($entry['kind'] === 'call')
                                <icon name="arrow.up" :size="13" class="text-theme-on-surface-variant" />
                            @else
                                <icon name="arrow.down" :size="13" class="text-theme-primary" />
                            @endif
                            <text class="flex-1 text-[13] font-semibold {{ $entry['kind'] === 'failure' ? 'text-[#EF4444]' : 'text-theme-on-surface' }}">
                                {{ $entry['event'] }}
                            </text>
                            <text font="mono" class="text-[11] text-theme-on-surface-variant">{{ $entry['at'] }}</text>
                        </row>
                        @if ($entry['detail'] !== '')
                            <text font="mono" class="text-[11] text-theme-on-surface-variant">{{ $entry['detail'] }}</text>
                        @endif
                    </column>
                @empty
                    <column class="w-full rounded-xl bg-theme-surface p-4 items-center">
                        <text class="text-[13] text-theme-on-surface-variant">
                            Nothing yet — open the editor and type.
                        </text>
                    </column>
                @endforelse
            @endif
        </column>
    </scroll-view>
</column>
