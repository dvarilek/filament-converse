<div
    class="fi-converse-conversation-manager"
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
    {{ $this->content }}

    <x-filament-actions::modals />
    <x-filament-panels::unsaved-action-changes-alert />
</div>
