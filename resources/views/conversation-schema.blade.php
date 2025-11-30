<div
    {{
        $attributes
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
    }}
>
    {{ $getChildSchema() }}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</div>
