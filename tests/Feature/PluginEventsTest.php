<?php

use Native\Mobile\Attributes\On;

/**
 * Every plugin event the demo listens for must actually be constructable.
 *
 * `#[On(SomeEvent::class)]` does not autoload the class, so an event whose
 * base class is missing registers as a listener happily and then fails in
 * silence the moment the editor tries to dispatch it — the button simply does
 * nothing. The plugin's own suite has no Laravel to catch that, so it is
 * caught here, where the framework is present.
 */
it('can construct every plugin event a component listens for', function () {
    $components = glob(app_path('NativeComponents/*.php'));

    expect($components)->not->toBeEmpty();

    $listened = [];

    foreach ($components as $file) {
        $class = 'App\\NativeComponents\\'.basename($file, '.php');
        $reflect = new ReflectionClass($class);

        foreach ($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(On::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                // The attribute stores the name prefixed, e.g.
                // "native:Vendor\\Package\\Events\\Thing".
                $listened[] = preg_replace('/^native:/', '', $attribute->newInstance()->event);
            }
        }
    }

    $listened = array_values(array_unique($listened));

    expect($listened)->not->toBeEmpty();

    foreach ($listened as $event) {
        expect(class_exists($event))->toBeTrue("{$event} does not load");

        foreach (class_parents($event) ?: [] as $parent) {
            expect(class_exists($parent))->toBeTrue("{$event} extends missing {$parent}");
        }
    }
});
