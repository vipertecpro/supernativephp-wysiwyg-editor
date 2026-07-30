{{--
    Demo 2 — long-form posting in the shape of LinkedIn.
    See App\NativeComponents\LinkedInFeed.

    Where X's feed is terse, this one gives a post room: a headline under the
    name, the full text rather than a clipped line, and reactions counted
    rather than iconified.
--}}
<column class="w-full h-full bg-theme-background safe-area">

    <row class="w-full px-4 py-3 items-center justify-between">
        <column @navigate.back a11y-label="Back" class="w-[32] h-[32] items-center justify-center">
            <icon name="chevron.left" :size="22" class="text-theme-on-surface" />
        </column>
        <text class="text-[20] font-bold text-theme-on-surface">Feed</text>
        <image
            src="https://i.pravatar.cc/150?u=you"
            alt="Your profile"
            class="w-[32] h-[32] rounded-full"
            :fit="2"
        />
    </row>

    <divider class="w-full" />

    {{-- The composer prompt LinkedIn puts at the top of the feed --}}
    <row class="w-full items-center gap-3 px-4 py-3">
        <image
            src="https://i.pravatar.cc/150?u=you"
            alt="Your profile"
            class="w-[40] h-[40] rounded-full"
            :fit="2"
        />
        <column
            @press="compose"
            a11y-label="Start a post"
            class="flex-1 rounded-full border border-theme-outline px-4 py-3"
        >
            <text class="text-[14] text-theme-on-surface-variant">Start a post</text>
        </column>
    </row>

    <divider class="w-full" />

    @if ($posts->isEmpty())
        <column class="w-full flex-1 items-center justify-center gap-3 px-10">
            <icon name="doc" :size="44" class="text-theme-on-surface-variant" />
            <text class="text-[17] font-bold text-theme-on-surface">No posts yet</text>
            <text class="text-center text-[14] text-theme-on-surface-variant">
                Tap "Start a post" to write one — try @ to mention someone, or # for a topic.
            </text>
        </column>
    @else
        <scroll-view class="w-full flex-1">
            <column class="w-full">
                @foreach ($posts as $post)
                    @php($content = $post->content())

                    <column class="w-full bg-theme-surface mb-2 px-4 py-3">

                        <row class="w-full items-center gap-3">
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
                                <text class="text-[12] text-theme-on-surface-variant">{{ $post->age() }} · Anyone</text>
                            </column>
                        </row>

                        {{-- Full width, not indented: long-form gets the room --}}
                        <column class="w-full pt-3">
                            {{--
                                Mentions, hashtags and links draw in the accent
                                colour, the way they do in the real feed.

                                Every span is its own nested <text> — including
                                the plain ones — because the renderer composes
                                nested text into ONE wrapping run, so the
                                paragraph still wraps as a paragraph. Writing
                                the plain parts as raw text instead would leave
                                the surrounding indentation inside the run.
                            --}}
                            @foreach ($content['paragraphs'] as $spans)
                                <text class="text-[14] text-theme-on-surface">
                                    @foreach ($spans as $span)
                                        <text class="text-[14] {{ $span['link'] !== '' ? 'font-semibold text-theme-primary' : 'text-theme-on-surface' }}">{{ $span['text'] }}</text>
                                    @endforeach
                                </text>
                            @endforeach

                            @include('native.partials.post-media', ['content' => $content, 'post' => $post])
                        </column>

                        <divider class="w-full mt-2" />

                        <row class="w-full items-center justify-between pt-2">
                            <row class="items-center gap-2">
                                <icon name="hand.thumbsup" :size="18" class="text-theme-on-surface-variant" />
                                <text class="text-[13] text-theme-on-surface-variant">Like</text>
                            </row>
                            <row class="items-center gap-2">
                                <icon name="bubble.left" :size="18" class="text-theme-on-surface-variant" />
                                <text class="text-[13] text-theme-on-surface-variant">Comment</text>
                            </row>
                            <row class="items-center gap-2">
                                <icon name="arrow.2.squarepath" :size="18" class="text-theme-on-surface-variant" />
                                <text class="text-[13] text-theme-on-surface-variant">Repost</text>
                            </row>
                            <row class="items-center gap-2">
                                <icon name="paperplane" :size="18" class="text-theme-on-surface-variant" />
                                <text class="text-[13] text-theme-on-surface-variant">Send</text>
                            </row>
                        </row>
                    </column>
                @endforeach

                <column class="w-full h-[24]" />
            </column>
        </scroll-view>
    @endif
</column>
