{{--
    Demo 3 — rich social posting in the shape of Facebook.
    See App\NativeComponents\FacebookFeed.

    The part worth looking at is a post written ON a colour: the editor holds a
    few words large and centred while you type them, and hands the colour's id
    back with the document. This feed renders it the same way.

    NativePHP has no gradient, so the card takes the `from` colour flat where
    the composer draws the full gradient. It is the one place the two do not
    match exactly.
--}}
<column class="w-full h-full bg-theme-background safe-area">

    {{--
        Wordmark and the two circles, which is all Facebook keeps up here.

        The real app has no back button, because it is not somewhere you
        arrive from. This one IS — so the wordmark carries the way out rather
        than adding a chevron the app does not have.
    --}}
    <row class="w-full items-center justify-between px-4 py-2">
        {{-- The directive rides on a CONTAINER, not on the <text> itself, for
             the same reason as LinkedIn's avatar: it is not reliably pressable
             on both platforms, and this is the only way out of the screen. --}}
        <pressable @navigate.back a11y-label="Back to examples">
            <text class="text-[24] font-bold text-theme-primary">facebook</text>
        </pressable>
        <row class="items-center gap-2">
            <column class="w-[36] h-[36] rounded-full bg-theme-surface-variant items-center justify-center">
                <icon name="magnifyingglass" :size="18" class="text-theme-on-surface" />
            </column>
            <column class="w-[36] h-[36] rounded-full bg-theme-surface-variant items-center justify-center">
                <icon name="message" :size="18" class="text-theme-on-surface" />
            </column>
        </row>
    </row>

    <divider class="w-full" />

    <scroll-view class="w-full flex-1">
        <column class="w-full">

            {{-- The prompt row, which is how a post actually starts here. --}}
            <row class="w-full items-center gap-3 px-4 py-3 bg-theme-surface">
                <image
                    src="https://i.pravatar.cc/150?u=you"
                    alt="Your profile"
                    class="w-[40] h-[40] rounded-full"
                    :fit="2"
                />
                <column
                    @press="compose"
                    a11y-label="What's on your mind?"
                    class="flex-1 rounded-full border border-theme-outline px-4 py-2"
                >
                    <text class="text-[15] text-theme-on-surface-variant">What's on your mind?</text>
                </column>
                <column @press="compose" a11y-label="Add a photo" class="w-[32] h-[32] items-center justify-center">
                    <icon name="photo" :size="22" class="text-[#22C55E]" />
                </column>
            </row>

            @if ($posts->isEmpty())
                <column class="w-full items-center justify-center gap-3 px-10 py-20">
                    <icon name="doc" :size="44" class="text-theme-on-surface-variant" />
                    <text class="text-[17] font-bold text-theme-on-surface">No posts yet</text>
                    <text class="text-center text-[14] text-theme-on-surface-variant">
                        Write a short one and pick a colour — it turns into a card.
                    </text>
                </column>
            @endif

            @foreach ($posts as $post)
                @php($content = $post->content())
                @php($card = $backgrounds[$content['background']] ?? null)

                <column class="w-full bg-theme-surface mt-2 pt-3">

                    <row class="w-full items-center gap-3 px-4">
                        <image
                            src="https://i.pravatar.cc/150?u={{ $post->author_handle === $mine ? 'you' : $post->author_handle }}"
                            alt="{{ $post->author_name }}"
                            class="w-[40] h-[40] rounded-full"
                            :fit="2"
                        />
                        <column class="flex-1">
                            <text class="text-[15] font-semibold text-theme-on-surface">{{ $post->author_name }}</text>
                            <row class="items-center gap-1">
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

                    @if ($card && $content['text'] !== '')
                        {{--
                            Written ON a colour: large, centred, edge to edge.
                            The post is a card, so it stops being a paragraph.

                            The renderer has no gradient, so the card is built
                            from thin strips of interpolated colour with the
                            words laid over them. A single flat fill read
                            noticeably unlike the composer, which draws the
                            real thing.
                        --}}
                        @php($ramp = \App\Support\Gradient::steps($card['from'], $card['to'] ?? ''))
                        @php($half = intdiv(count($ramp), 2))

                        {{--
                            An absolutely positioned child is clipped to its
                            parent AND ignores the alignment classes, so the
                            words cannot simply be laid over the strips. They
                            live in a band of their own instead, taking the
                            colour the ramp has at that point — flat across 60
                            points, between two shades close enough that the
                            seam does not read.
                        --}}
                        <column class="w-full mt-3">
                            @foreach (array_slice($ramp, 0, $half) as $shade)
                                <column class="w-full h-[9] bg-[{{ $shade }}]" />
                            @endforeach

                            <column class="w-full h-[64] items-center justify-center px-8 bg-[{{ $ramp[$half] ?? $card['from'] }}]">
                                {{-- The colour has to be a class: the renderer
                                     has no style attribute to read. --}}
                                <text
                                    class="text-center text-[26] font-bold text-[{{ $card['textColor'] ?? '#FFFFFF' }}]"
                                >{{ $content['text'] }}</text>
                            </column>

                            @foreach (array_slice($ramp, $half + 1) as $shade)
                                <column class="w-full h-[9] bg-[{{ $shade }}]" />
                            @endforeach
                        </column>
                    @else
                        @if ($content['paragraphs'] !== [])
                            <column class="w-full px-4 pt-2">
                                @foreach ($content['paragraphs'] as $index => $spans)
                                    <text class="text-[15] text-theme-on-surface {{ $index > 0 ? 'pt-2' : '' }}">
                                        @foreach ($spans as $span)
                                            <text class="text-[15] {{ $span['link'] !== '' ? 'font-semibold text-theme-primary' : 'text-theme-on-surface' }}">{{ $span['text'] }}</text>
                                        @endforeach
                                    </text>
                                @endforeach
                            </column>
                        @endif

                        @include('native.partials.post-media', ['content' => $content, 'post' => $post])
                    @endif

                    {{-- Reactions, then the three buttons. --}}
                    <row class="w-full items-center gap-2 px-4 pt-3 pb-2">
                        <column class="w-[18] h-[18] rounded-full bg-theme-primary items-center justify-center">
                            <icon name="hand.thumbsup.fill" :size="10" class="text-theme-on-primary" />
                        </column>
                        <text class="text-[13] text-theme-on-surface-variant">{{ $post->metric($post->likes) ?: '0' }}</text>
                        <column class="flex-1" />
                        <text class="text-[13] text-theme-on-surface-variant">{{ $post->metric($post->replies) ?: '0' }} comments</text>
                    </row>

                    <divider class="w-full" />

                    <row class="w-full items-center px-2 py-1">
                        @foreach ([
                            ['icon' => 'hand.thumbsup', 'label' => 'Like'],
                            ['icon' => 'bubble.left', 'label' => 'Comment'],
                            ['icon' => 'arrow.2.circlepath', 'label' => 'Share'],
                        ] as $action)
                            <row class="flex-1 items-center justify-center gap-2 py-2">
                                <icon name="{{ $action['icon'] }}" :size="18" class="text-theme-on-surface-variant" />
                                <text class="text-[14] text-theme-on-surface-variant">{{ $action['label'] }}</text>
                            </row>
                        @endforeach
                    </row>
                </column>
            @endforeach

            <column class="w-full h-[16]" />
        </column>
    </scroll-view>

    {{-- The `···` menu. Deleting asks first, in the SAME sheet: a system alert
         chained off a sheet's dismissal is silently refused by iOS. --}}
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

    <divider class="w-full" />
    <row class="w-full items-center">
        @foreach ([
            ['icon' => 'house.fill', 'label' => 'Home', 'active' => true],
            ['icon' => 'person.2', 'label' => 'Friends', 'active' => false],
            ['icon' => 'shop', 'label' => 'Marketplace', 'active' => false],
            ['icon' => 'bell', 'label' => 'Notifications', 'active' => false],
            ['icon' => 'line.3.horizontal', 'label' => 'Menu', 'active' => false],
        ] as $tab)
            <column a11y-label="{{ $tab['label'] }}" class="flex-1 items-center py-3">
                <icon
                    name="{{ $tab['icon'] }}"
                    :size="22"
                    class="{{ $tab['active'] ? 'text-theme-primary' : 'text-theme-on-surface-variant' }}"
                />
            </column>
        @endforeach
    </row>
</column>
