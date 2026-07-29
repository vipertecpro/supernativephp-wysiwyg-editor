{{--
    The media under a post: photos, a video, a poll. Expects $content from
    App\Models\Post::content() and $post.

    Photo layout follows the shapes X, Facebook and Instagram all use — one
    fills the width, two sit side by side, three put one big beside a stacked
    pair, four make a square. Tapping any of them opens it full-screen.
--}}
@php($images = $content['images'])
@php($shape = \App\Support\PostContent::grid(count($images)))

@if ($images !== [])
    <column class="w-full mt-2 rounded-xl">
        @if ($shape === 'single')
            <image
                @press="preview('image', '{{ $images[0]['src'] }}', '{{ $images[0]['caption'] }}')"
                src="{{ $images[0]['src'] }}"
                alt="{{ $images[0]['alt'] ?: 'Photo' }}"
                class="w-full h-[200] rounded-xl"
                :fit="2"
            />
        @elseif ($shape === 'pair')
            <row class="w-full gap-[2]">
                @foreach ($images as $image)
                    <column class="flex-1">
                        <image
                            @press="preview('image', '{{ $image['src'] }}', '{{ $image['caption'] }}')"
                            src="{{ $image['src'] }}"
                            alt="{{ $image['alt'] ?: 'Photo' }}"
                            class="w-full h-[160] rounded-xl"
                            :fit="2"
                        />
                    </column>
                @endforeach
            </row>
        @elseif ($shape === 'feature')
            <row class="w-full gap-[2]">
                <column class="flex-1">
                    <image
                        @press="preview('image', '{{ $images[0]['src'] }}', '{{ $images[0]['caption'] }}')"
                        src="{{ $images[0]['src'] }}"
                        alt="{{ $images[0]['alt'] ?: 'Photo' }}"
                        class="w-full h-[200] rounded-xl"
                        :fit="2"
                    />
                </column>
                <column class="flex-1 gap-[2]">
                    @foreach (array_slice($images, 1, 2) as $image)
                        <image
                            @press="preview('image', '{{ $image['src'] }}', '{{ $image['caption'] }}')"
                            src="{{ $image['src'] }}"
                            alt="{{ $image['alt'] ?: 'Photo' }}"
                            class="w-full h-[99] rounded-xl"
                            :fit="2"
                        />
                    @endforeach
                </column>
            </row>
        @else
            <column class="w-full gap-[2]">
                @foreach (array_chunk(array_slice($images, 0, 4), 2) as $pair)
                    <row class="w-full gap-[2]">
                        @foreach ($pair as $image)
                            <column class="flex-1">
                                <image
                                    @press="preview('image', '{{ $image['src'] }}', '{{ $image['caption'] }}')"
                                    src="{{ $image['src'] }}"
                                    alt="{{ $image['alt'] ?: 'Photo' }}"
                                    class="w-full h-[110] rounded-xl"
                                    :fit="2"
                                />
                            </column>
                        @endforeach
                    </row>
                @endforeach
            </column>
        @endif
    </column>
@endif

@if ($content['video'])
    {{-- The poster with a play badge — what a timeline shows BEFORE playback.
         There is no video element on this platform, so tapping hands it to the
         plugin's player rather than playing in place. --}}
    <column class="w-full mt-2">
        <column
            @press="preview('video', '{{ $content['video']['src'] }}', '{{ $content['video']['caption'] }}')"
            a11y-label="Play video"
            class="w-full h-[200] rounded-xl bg-theme-surface-variant items-center justify-center"
        >
            <column class="w-[56] h-[56] rounded-full bg-theme-primary items-center justify-center">
                <icon name="play.fill" :size="24" class="text-theme-on-primary" />
            </column>
        </column>
    </column>
@endif

@if ($content['poll'])
    <column class="w-full mt-2 gap-2">
        @if ($content['poll']['question'] !== '')
            <text class="text-[15] font-semibold text-theme-on-surface">{{ $content['poll']['question'] }}</text>
        @endif

        @foreach ($content['poll']['options'] as $option)
            {{-- Not votable: this timeline renders posts, and where a vote is
                 stored is the app's problem, not the editor's. --}}
            <row class="w-full items-center rounded-full border border-theme-outline px-4 py-2">
                <text class="flex-1 text-[14] text-theme-on-surface">{{ $option }}</text>
            </row>
        @endforeach
    </column>
@endif
