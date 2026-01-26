export function conversationThread({
    key,
    conversationKey,
    statePath,
    autoScrollOnForeignMessagesThreshold,
    shouldDispatchUserTypingEvent,
    userTypingIndicatorTimeout,
    userTypingEventDispatchThreshold,
    userTypingTranslations,
    $wire,
}) {
    return {
        messagesCreatedDuringConversationSession: $wire.entangle(
            'messagesCreatedDuringConversationSession',
        ),

        typingUsersMap: new Map(),

        typingUserTimeouts: new Map(),

        lastUserTypingEventSentAt: null,

        isLoadingMoreMessages: false,

        init() {
            window.Echo.private(
                'filament-converse.conversation.' + conversationKey,
            )
                .listen('.user.typing', (event) => {
                    const userId = event.user.id
                    this.typingUsersMap.set(userId, event.user.name)

                    if (this.typingUserTimeouts.has(userId)) {
                        clearTimeout(this.typingUserTimeouts.get(userId))
                    }

                    const timeoutId = setTimeout(() => {
                        this.typingUsersMap.delete(userId)
                        this.typingUserTimeouts.delete(userId)
                    }, userTypingIndicatorTimeout)

                    this.typingUserTimeouts.set(userId, timeoutId)
                })
                .listen('.message.sent', (event) =>
                    $wire.call(
                        'registerMessageCreatedDuringConversationSession',
                        event.message.id,
                        event.message.authorId,
                    ),
                )
                .listen('.message.deleted', (event) =>
                    $wire.call(
                        'registerMessageCreatedDuringConversationSession',
                        event.message.id,
                        event.message.authorId,
                        false,
                    ),
                )
                .listen('.conversation.read', (event) => $wire.$refresh())
                .listen('.message.updated', (event) => $wire.$refresh())

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

        areOtherUsersTyping() {
            return this.typingUsersMap.size > 0
        },

        getTypingUsersMessage() {
            const names = Array.from(this.typingUsersMap.values())

            if (names.length === 0 || userTypingTranslations.length === 0)
                return ''

            if (names.length === 1) {
                return userTypingTranslations.single.replace(
                    '{singleName}',
                    names[0],
                )
            }

            if (names.length === 2) {
                return userTypingTranslations.double
                    .replace('{firstName}', names[0])
                    .replace('{secondName}', names[1])
            }

            const othersCount = names.length - 2
            const othersText =
                othersCount === 1
                    ? userTypingTranslations.other
                    : userTypingTranslations.others

            return userTypingTranslations.multiple
                .replace('{firstName}', names[0])
                .replace('{secondName}', names[1])
                .replace('{count}', othersCount)
                .replace('{others}', othersText)
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
