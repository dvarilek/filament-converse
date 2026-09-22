export function conversationThread({
                                       key,
                                       autoScrollOnForeignMessagesThreshold,
                                       shouldDispatchUserTypingEvent,
                                       userTypingEventDispatchThreshold,
                                       $wire,
                                   }) {
    return {
        resizeObserver: null,

        previousFooterHeight: 0,

        messageChangesDuringConversationSession: $wire.entangle(
            'messageChangesDuringConversationSession',
        ),

        lastUserTypingEventSentAt: null,

        isLoadingMoreMessages: false,

        init() {
            this.$watch(
                'messageChangesDuringConversationSession',
                this.handleConversationMessagesChange.bind(this),
            )

            this.observeConversationThreadFooterResize()
        },

        observeConversationThreadFooterResize() {
            const footer = this.$refs.conversationThreadFooter
            const threadContent = this.$refs.conversationThreadContent

            if (!footer || !threadContent) {
                return
            }

            this.previousFooterHeight = footer.getBoundingClientRect().height

            this.resizeObserver = new ResizeObserver(([entry]) => {
                const newHeight = entry.target.getBoundingClientRect().height
                const difference = newHeight - this.previousFooterHeight

                if (difference === 0) {
                    return
                }

                const distanceFromBottom =
                    threadContent.scrollHeight -
                    threadContent.clientHeight -
                    threadContent.scrollTop

                if (distanceFromBottom === 0) {
                    threadContent.scrollTop = threadContent.scrollHeight
                } else {
                    threadContent.scrollTop += difference
                }

                this.previousFooterHeight = newHeight
            })

            this.resizeObserver.observe(footer)
        },

        handleConversationMessagesChange(newMessages, oldMessages) {
            const isForeign = (message) =>
                !message.createdByAuthenticatedUser && message.exists
            const isNonForeign = (message) =>
                message.createdByAuthenticatedUser && message.exists

            const newForeignCount =
                Object.values(newMessages).filter(isForeign).length
            const oldForeignCount =
                Object.values(oldMessages).filter(isForeign).length

            const newNonForeignCount =
                Object.values(newMessages).filter(isNonForeign).length
            const oldNonForeignCount =
                Object.values(oldMessages).filter(isNonForeign).length

            if (
                (newForeignCount > oldForeignCount &&
                    this.isPositionedNearBottom()) ||
                newNonForeignCount > oldNonForeignCount
            ) {
                this.scrollToBottom({ behavior: 'smooth' })
            }
        },

        scrollToBottom(options) {
            this.$refs.conversationThreadContentEndMarker.scrollIntoView(
                options,
            )
        },

        navigateToMessage(messageKey) {
            const element = document.getElementById(
                'fi-converse-conversation-thread-message-content-' + messageKey
            )

            if (!element) {
                return
            }

            element.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            })

            element.classList.add('message-highlight')

            setTimeout(() => {
                element.classList.remove('message-highlight')
            }, 1500)
        },

        async fireUserTypingEvent(event) {
            const data = event.target.value

            if (!data || data.trim() === '') {
                return
            }

            if (!shouldDispatchUserTypingEvent) {
                return
            }

            const now = Date.now()

            if (
                this.lastUserTypingEventSentAt &&
                now - this.lastUserTypingEventSentAt <
                userTypingEventDispatchThreshold
            ) {
                return
            }

            this.lastUserTypingEventSentAt = now

            await $wire.callSchemaComponentMethod(
                key,
                'broadcastUserTypingEvent',
            )
        },

        async loadMoreMessages() {
            const element = this.$refs.conversationThreadContent
            const previousScrollHeight = element.scrollHeight

            this.isLoadingMoreMessages = true

            try {
                await $wire.call('incrementActiveConversationMessagesPage')
            } finally {
                this.isLoadingMoreMessages = false
                element.scrollTop += element.scrollHeight - previousScrollHeight
            }
        },

        isPositionedNearBottom() {
            const element = this.$refs.conversationThreadContent

            return (
                element.scrollHeight -
                element.scrollTop -
                element.clientHeight <
                (autoScrollOnForeignMessagesThreshold ?? 0)
            )
        },

        destroy() {
            this.resizeObserver?.disconnect()
        },
    }
}
