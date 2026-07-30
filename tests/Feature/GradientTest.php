<?php

use App\Support\Gradient;

it('starts and ends on the colours it was given', function () {
    $steps = Gradient::steps('#000000', '#FFFFFF', 5);

    expect($steps)->toHaveCount(5)
        ->and($steps[0])->toBe('#000000')
        ->and($steps[4])->toBe('#FFFFFF');
});

it('interpolates evenly in between', function () {
    expect(Gradient::steps('#000000', '#FFFFFF', 3))->toBe(['#000000', '#808080', '#FFFFFF']);
});

/** A flat colour is a gradient with nowhere to go. */
it('treats a missing end colour as the start colour', function () {
    expect(Gradient::steps('#2563EB', '', 3))->toBe(['#2563EB', '#2563EB', '#2563EB']);
});

it('accepts the shorthand a host will write by hand', function () {
    expect(Gradient::steps('#f00', '#00f', 2))->toBe(['#FF0000', '#0000FF']);
});

/**
 * A black card is worse than a flat one: it looks like a rendering fault
 * rather than a colour the app does not have.
 */
it('yields nothing for a colour it cannot read', function (string $from, string $to) {
    expect(Gradient::steps($from, $to))->toBe([]);
})->with([
    ['cornflower', '#FFFFFF'],
    ['#FFFFFF', 'rgb(0,0,0)'],
    ['', ''],
]);

it('never returns fewer than two steps, whatever it is asked for', function () {
    expect(Gradient::steps('#000000', '#FFFFFF', 1))->toHaveCount(2)
        ->and(Gradient::steps('#000000', '#FFFFFF', 0))->toHaveCount(2);
});
