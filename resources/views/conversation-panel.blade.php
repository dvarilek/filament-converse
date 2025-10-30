<div
    {{
        $attributes
            ->class(['fi-converse'])
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
    }}
    x-data="{
        showConversationListSidebar: false,
        isBelowLg: window.innerWidth < 1024,

        init() {
            this.checkBreakpoint()
            window.addEventListener('resize', () => this.checkBreakpoint())
        },

        checkBreakpoint() {
            this.isBelowLg = window.innerWidth < 1024
            if (! this.isBelowLg) {
                this.showConversationListSidebar = false
            }
        },
    }"
>
    {{ $getChildSchema() }}
</div>
