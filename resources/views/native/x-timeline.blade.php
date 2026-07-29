{{--
    Demo 1 — a timeline in the shape of X. See App\NativeComponents\XTimeline.

    Everything here is ordinary layout. The part worth looking at is what
    happens when you tap compose: that whole screen is the plugin — no
    formatting bar, a media row, a countdown ring and a filled Post pill.

    Each post is ONE column rather than an avatar row wrapping everything
    else. Nesting the media and the action bar inside a flex child left the
    row under-measuring its own height, so the actions drew on top of the
    next post.
--}}
<column class="w-full h-full bg-theme-background safe-area">

    {{-- Top bar: back, wordmark, avatar. X keeps this almost empty. --}}
    <row class="w-full px-4 py-3 items-center justify-between">
        <column @navigate.back a11y-label="Back" class="w-[32] h-[32] items-center justify-center">
            <icon name="chevron.left" :size="22" class="text-theme-on-surface" />
        </column>
        <text class="text-[20] font-bold text-theme-on-surface">Timeline</text>
        <image
            src="https://i.pravatar.cc/150?u=you"
            alt="Your profile"
            class="w-[32] h-[32] rounded-full"
            :fit="2"
        />
    </row>

    <divider class="w-full" />

    {{-- A kept draft. The editor handed it over and stepped back; where it
         lives is ours to decide. --}}
    @if ($draft !== [])
        <row class="w-full items-center gap-3 px-4 py-3 bg-theme-surface">
            <icon name="square.and.pencil" :size="18" class="text-theme-primary" />
            <column class="flex-1">
                <text class="text-[14] font-semibold text-theme-on-surface">Unsent post</text>
                <text class="text-[13] text-theme-on-surface-variant">{{ \Illuminate\Support\Str::limit($draft['text'], 48) }}</text>
            </column>
            <column @press="resumeDraft" a11y-label="Resume draft" class="px-3 py-1">
                <text class="text-[14] font-semibold text-theme-primary">Resume</text>
            </column>
            <column @press="discardDraft" a11y-label="Discard draft" class="px-2 py-1">
                <icon name="xmark" :size="16" class="text-theme-on-surface-variant" />
            </column>
        </row>

        <divider class="w-full" />
    @endif

    @if ($posts->isEmpty())
        <column class="w-full flex-1 items-center justify-center gap-3 px-10">
            <icon name="square.and.pencil" :size="44" class="text-theme-on-surface-variant" />
            <text class="text-[17] font-bold text-theme-on-surface">Nothing here yet</text>
            <text class="text-center text-[14] text-theme-on-surface-variant">
                Tap the button below to write your first post with the native editor.
            </text>
        </column>
    @else
        <scroll-view class="w-full flex-1">
            <column class="w-full">
                @foreach ($posts as $post)
                    @php($content = $post->content())

                    <column class="w-full px-4 pt-3 pb-1">

                        {{-- Header: avatar, name, handle, age, and the menu --}}
                        <row class="w-full items-center gap-3">
                            <image
                                src="https://i.pravatar.cc/150?u={{ $post->author_handle }}"
                                alt="{{ $post->author_name }}"
                                class="w-[40] h-[40] rounded-full"
                                :fit="2"
                            />
                            <text class="text-[15] font-bold text-theme-on-surface">{{ $post->author_name }}</text>
                            <text class="text-[13] text-theme-on-surface-variant">{{ $post->author_handle }}</text>
                            <text class="text-[13] text-theme-on-surface-variant">· {{ $post->age() }}</text>

                            @if ($post->author_handle === $mine)
                                <column class="flex-1 items-end">
                                    <column
                                        @press="showActions({{ $post->id }})"
                                        a11y-label="Post actions"
                                        class="w-[28] h-[28] items-center justify-center"
                                    >
                                        <icon name="ellipsis" :size="16" class="text-theme-on-surface-variant" />
                                    </column>
                                </column>
                            @endif
                        </row>

                        {{-- Body, indented to clear the avatar --}}
                        <column class="w-full pl-[52] pt-1">
                            @if ($content['text'] !== '')
                                <text class="text-[15] text-theme-on-surface">{{ $content['text'] }}</text>
                            @endif

                            @include('native.partials.post-media', ['content' => $content, 'post' => $post])

                            <row class="w-full items-center justify-between pt-3 pb-2 pr-8">
                                <row class="items-center gap-1">
                                    <icon name="bubble.left" :size="16" class="text-theme-on-surface-variant" />
                                    <text class="text-[13] text-theme-on-surface-variant">{{ $post->metric($post->replies) }}</text>
                                </row>
                                <row class="items-center gap-1">
                                    <icon name="arrow.2.squarepath" :size="16" class="text-theme-on-surface-variant" />
                                    <text class="text-[13] text-theme-on-surface-variant">{{ $post->metric($post->reposts) }}</text>
                                </row>
                                <row class="items-center gap-1">
                                    <icon name="heart" :size="16" class="text-theme-on-surface-variant" />
                                    <text class="text-[13] text-theme-on-surface-variant">{{ $post->metric($post->likes) }}</text>
                                </row>
                                <icon name="square.and.arrow.up" :size="16" class="text-theme-on-surface-variant" />
                            </row>
                        </column>
                    </column>

                    <divider class="w-full" />
                @endforeach

                {{-- Clearance so the last post is not stuck under the button --}}
                <column class="w-full h-[96]" />
            </column>
        </scroll-view>
    @endif

    {{-- Actions for your own post. Always in the tree; visibility is driven
         by which post was tapped. --}}
    <bottom-sheet :visible="$actionsFor !== null" detents="small" @dismiss="closeActions">
        @if ($actionsFor !== null)
            @if ($confirmingDelete)
                {{-- Confirming in the SAME sheet: a system alert chained off
                     the sheet's dismissal is silently refused by iOS. --}}
                <column class="w-full py-2">
                    <column class="w-full px-6 py-4 gap-1">
                        <text class="text-base font-semibold text-theme-on-surface">Delete post?</text>
                        <text class="text-sm text-theme-on-surface-variant">This cannot be undone.</text>
                    </column>

                    <divider class="w-full" />

                    <row @press="delete({{ $actionsFor }})" a11y-label="Confirm delete" class="w-full items-center gap-4 px-6 py-4">
                        <icon name="trash" :size="20" class="text-[#EF4444]" />
                        <text class="text-base font-semibold text-[#EF4444]">Delete</text>
                    </row>

                    <divider class="w-full" />

                    <row @press="cancelDelete" a11y-label="Keep post" class="w-full items-center gap-4 px-6 py-4">
                        <icon name="xmark" :size="20" class="text-theme-on-surface" />
                        <text class="text-base text-theme-on-surface">Keep</text>
                    </row>
                </column>
            @else
                <column class="w-full py-2">
                    <row @press="edit({{ $actionsFor }})" a11y-label="Edit post" class="w-full items-center gap-4 px-6 py-4">
                        <icon name="square.and.pencil" :size="20" class="text-theme-on-surface" />
                        <text class="text-base text-theme-on-surface">Edit post</text>
                    </row>

                    <divider class="w-full" />

                    <row @press="confirmDelete" a11y-label="Delete post" class="w-full items-center gap-4 px-6 py-4">
                        <icon name="trash" :size="20" class="text-[#EF4444]" />
                        <text class="text-base text-[#EF4444]">Delete post</text>
                    </row>
                </column>
            @endif
        @endif
    </bottom-sheet>

    {{-- Compose. Absolute so it floats over the feed without blocking
         scrolling — it only occupies its own 56x56. --}}
    <column
        @press="compose"
        a11y-label="New post"
        class="absolute bottom-[20] right-[20] w-[56] h-[56] rounded-full bg-theme-primary items-center justify-center"
    >
        <icon name="square.and.pencil" :size="22" class="text-theme-on-primary" />
    </column>
</column>
