{{--
    Demo 2 — long-form posting in the shape of LinkedIn.
    See App\NativeComponents\LinkedInFeed.

    The chrome is modelled on the real feed: a search bar rather than a title,
    a tab bar rather than a floating button, and a post row that leads with WHO
    is speaking — name, headline, when, and who can see it — because on a
    professional network the author is half the content.

    Composing hangs off the Post tab, which is where LinkedIn moved it.
--}}
<column class="w-full h-full bg-theme-background safe-area">

    {{-- Search, not a title: the feed is somewhere you look things up. --}}
    <row class="w-full items-center gap-3 px-3 py-2">
        <image
            src="https://i.pravatar.cc/150?u=you"
            alt="Your profile"
            class="w-[32] h-[32] rounded-full"
            :fit="2"
        />
        <row class="flex-1 items-center gap-2 rounded-lg border border-theme-outline px-3 py-2">
            <icon name="magnifyingglass" :size="18" class="text-theme-on-surface-variant" />
            <text class="text-[15] text-theme-on-surface-variant">Search</text>
        </row>
        <icon name="message" :size="24" class="text-theme-on-surface" />
    </row>

    @if ($posts->isEmpty())
        <column class="w-full flex-1 items-center justify-center gap-3 px-10">
            <icon name="doc" :size="44" class="text-theme-on-surface-variant" />
            <text class="text-[17] font-bold text-theme-on-surface">No posts yet</text>
            <text class="text-center text-[14] text-theme-on-surface-variant">
                Tap Post to write one — try @ to mention someone, or # for a topic.
            </text>
        </column>
    @else
        <scroll-view class="w-full flex-1">
            <column class="w-full">
                @foreach ($posts as $post)
                    @php($content = $post->content())

                    <column class="w-full bg-theme-surface mb-2 pt-3">

                        {{-- Who is speaking, which is half the content here. --}}
                        <row class="w-full items-start gap-3 px-4">
                            <image
                                {{-- Your own posts wear the same face as the composer. --}}
                                src="https://i.pravatar.cc/150?u={{ $post->author_handle === $mine ? 'you' : $post->author_handle }}"
                                alt="{{ $post->author_name }}"
                                class="w-[48] h-[48] rounded-full"
                                :fit="2"
                            />
                            <column class="flex-1">
                                <text class="text-[15] font-bold text-theme-on-surface">{{ $post->author_name }}</text>
                                <text class="text-[13] text-theme-on-surface-variant">Building a native editor for NativePHP</text>
                                {{-- When, and who can see it — the globe is the audience. --}}
                                <row class="items-center gap-1 pt-[2]">
                                    <text class="text-[12] text-theme-on-surface-variant">{{ $post->age() }} ·</text>
                                    <icon name="globe" :size="12" class="text-theme-on-surface-variant" />
                                </row>
                            </column>
                            <column
                                @press="showActions({{ $post->id }})"
                                a11y-label="Post options"
                                class="w-[32] h-[32] items-center justify-center"
                            >
                                <icon name="ellipsis" :size="18" class="text-theme-on-surface-variant" />
                            </column>
                        </row>

                        {{--
                            Mentions, hashtags and links draw in the accent
                            colour, the way they do in the real feed.

                            Every span is its own nested <text> — including the
                            plain ones — because the renderer composes nested
                            text into ONE wrapping run, so the paragraph still
                            wraps as a paragraph. Writing the plain parts as raw
                            text instead would leave the surrounding
                            indentation inside the run.
                        --}}
                        @php($body = in_array($post->id, $expanded, true)
                            ? ['paragraphs' => $content['paragraphs'], 'clipped' => false]
                            : \App\Support\PostContent::clip($content['paragraphs']))
                        @php($last = array_key_last($body['paragraphs']))

                        {{--
                            A clipped post is tappable anywhere, the way it is
                            in the real feed — "more" is the label on the
                            gesture, not a small target of its own.
                        --}}
                        <column
                            @if ($body['clipped']) @press="expand({{ $post->id }})" a11y-label="See more" @endif
                            class="w-full px-4 pt-2"
                        >
                            @foreach ($body['paragraphs'] as $index => $spans)
                                <text class="text-[14] text-theme-on-surface {{ $index > 0 ? 'pt-2' : '' }}">
                                    @foreach ($spans as $span)
                                        <text class="text-[14] {{ $span['link'] !== '' ? 'font-semibold text-theme-primary' : 'text-theme-on-surface' }}">{{ $span['text'] }}</text>
                                    @endforeach
                                    {{-- Inline, so it reads as the sentence trailing off. --}}
                                    @if ($body['clipped'] && $index === $last)
                                        <text class="text-[14] text-theme-on-surface-variant">… more</text>
                                    @endif
                                </text>
                            @endforeach
                        </column>

                        {{-- Edge to edge: a photo in this feed is not inset. --}}
                        @include('native.partials.post-media', ['content' => $content, 'post' => $post])

                        <divider class="w-full mt-3" />

                        {{--
                            Icons with counts, not icons with labels: the count
                            is the information, and Like/Comment/Repost/Send are
                            recognisable without being spelled out.
                        --}}
                        <row class="w-full items-center px-2 py-1">
                            <row class="items-center gap-2 px-3 py-2">
                                <icon name="hand.thumbsup" :size="20" class="text-theme-on-surface-variant" />
                                <text class="text-[13] text-theme-on-surface-variant">Like</text>
                            </row>
                            <row class="items-center gap-2 px-3 py-2">
                                <icon name="bubble.left" :size="20" class="text-theme-on-surface-variant" />
                                <text class="text-[13] text-theme-on-surface-variant">Comment</text>
                            </row>
                            <row class="items-center gap-2 px-3 py-2">
                                {{-- arrow.2.squarepath is not a name either platform maps. --}}
                                <icon name="arrow.2.circlepath" :size="20" class="text-theme-on-surface-variant" />
                                <text class="text-[13] text-theme-on-surface-variant">Repost</text>
                            </row>
                            <row class="items-center gap-2 px-3 py-2">
                                <icon name="paperplane" :size="20" class="text-theme-on-surface-variant" />
                                <text class="text-[13] text-theme-on-surface-variant">Send</text>
                            </row>
                        </row>
                    </column>
                @endforeach

                <column class="w-full h-[16]" />
            </column>
        </scroll-view>
    @endif

    {{-- The `···` menu. Deleting asks first, in the SAME sheet: a system
         alert chained off a sheet's dismissal is silently refused by iOS. --}}
    <bottom-sheet :visible="$actionsFor !== null" detents="small" @dismiss="closeActions">
        @if ($actionsFor !== null)
            @if ($confirmingDelete)
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

    {{-- Where LinkedIn moved composing: a tab, not a floating button. --}}
    <divider class="w-full" />
    <row class="w-full items-center">
        @foreach ([
            ['id' => 'home', 'icon' => 'house.fill', 'label' => 'Home', 'active' => true],
            ['id' => 'network', 'icon' => 'person.2', 'label' => 'My Network', 'active' => false],
            ['id' => 'post', 'icon' => 'plus', 'label' => 'Post', 'active' => false],
            ['id' => 'alerts', 'icon' => 'bell', 'label' => 'Notifications', 'active' => false],
            ['id' => 'jobs', 'icon' => 'shop', 'label' => 'Jobs', 'active' => false],
        ] as $tab)
            <column
                @if ($tab['id'] === 'post') @press="compose" @endif
                a11y-label="{{ $tab['label'] }}"
                class="flex-1 items-center gap-1 py-2"
            >
                <icon
                    name="{{ $tab['icon'] }}"
                    :size="22"
                    class="{{ $tab['active'] ? 'text-theme-on-surface' : 'text-theme-on-surface-variant' }}"
                />
                <text class="text-[10] {{ $tab['active'] ? 'font-semibold text-theme-on-surface' : 'text-theme-on-surface-variant' }}">
                    {{ $tab['label'] }}
                </text>
            </column>
        @endforeach
    </row>
</column>
