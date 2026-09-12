export function conversationThread({
    key,
    autoScrollOnForeignMessagesThreshold,
    shouldDispatchUserTypingEvent,
    userTypingEventDispatchThreshold,
    $wire,
}) {
    return {
        messagesCreatedDuringConversationSession: $wire.entangle(
            'messagesCreatedDuringConversationSession',
        ),

        lastUserTypingEventSentAt: null,

        isLoadingMoreMessages: false,

        init() {
            this.$watch(
                'messagesCreatedDuringConversationSession',
                (newMessages, oldMessages) => {
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
                        newForeignCount > oldForeignCount &&
                        this.isPositionedNearBottom()
                    ) {
                        this.$nextTick(() =>
                            this.scrollToBottom({ behaviour: 'smooth' }),
                        )
                    }

                    if (newNonForeignCount > oldNonForeignCount) {
                        this.$nextTick(() =>
                            this.scrollToBottom({ behaviour: 'smooth' }),
                        )
                    }
                },
            )
        },

        scrollToBottom(options) {
            this.$refs.conversationThreadContentEndMarker.scrollIntoView(
                options,
            )
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

        isUploadingFileAttachment() {
            return this.uploadingFileAttachments.length > 0
        },
    }
}
