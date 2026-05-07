<!DOCTYPE html>

<html
    lang="{{ app()->getLocale() }}"
    dir="{{ core()->getCurrentLocale()->direction }}"
>

<head>
    <meta charset="UTF-8" />

    <title>POS (Point of Sale)</title>

    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge"
    >

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="base-url"
        content="{{ url()->to('/') }}"
    >

    <link
        rel="icon"
        sizes="16x16"
        href="{{ bagisto_asset('images/favicon.ico', 'pos') }}"
    />

    @bagistoVite(['src/Resources/assets/css/shop.css', 'src/Resources/assets/js/shop.js'], 'pos')
</head>

<body>
    <div
        id="app"
        class="h-full"
    >
    </div>
</body>

</html>