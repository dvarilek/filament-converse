export function conversationSchema({
    activeConversationKey,
    conversationKeys,
    userTypingIndicatorTimeout,
    userTypingTranslations,
    $wire,
}) {
    return {
        typingUsers: new Map(),

        init() {
            conversationKeys.forEach((conversationKey) => {
                const isActiveConversationOpened =
                    conversationKey === activeConversationKey

                window.Echo.private(
                    'filament-converse.conversation.' + conversationKey,
                )
                    .listen('.user.typing', (event) => {
                        if (!this.typingUsers.has(conversationKey)) {
                            this.typingUsers.set(conversationKey, new Map())
                        }

                        const userId = event.user.id
                        const conversationTypingUsers =
                            this.typingUsers.get(conversationKey)

                        if (conversationTypingUsers.has(userId)) {
                            clearTimeout(
                                conversationTypingUsers.get(userId).timeoutId,
                            )
                        }

                        const timeoutId = setTimeout(() => {
                            conversationTypingUsers.delete(userId)

                            if (conversationTypingUsers.size === 0) {
                                this.typingUsers.delete(conversationKey)
                            }
                        }, userTypingIndicatorTimeout)

                        conversationTypingUsers.set(userId, {
                            name: event.user.name,
                            timeoutId,
                        })
                    })
                    .listen('.message.sent', (event) => {
                        if (isActiveConversationOpened) {
                            $wire.call(
                                'trackMessageChangeDuringConversationSession',
                                event.message.id,
                                event.message.authorId,
                            )

                            return
                        }

                        $wire.$refresh()
                    })
                    .listen('.message.deleted', (event) => {
                        if (isActiveConversationOpened) {
                            $wire.call(
                                'trackMessageChangeDuringConversationSession',
                                event.message.id,
                                event.message.authorId,
                                false,
                            )

                            return
                        }

                        $wire.$refresh()
                    })
                    .listen('.conversation.read', (event) => {
                        if (isActiveConversationOpened) {
                            $wire.$refresh()
                        }
                    })
                    .listen('.message.updated', (event) => $wire.$refresh())
            })
        },

        areOtherUsersTyping(conversationKey) {
            return this.typingUsers.get(conversationKey)?.size > 0
        },

        getTypingUsersMessage(conversationKey) {
            const names = Array.from(
                this.typingUsers.get(conversationKey)?.values() ?? [],
            ).map((user) => user.name)

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
    }
}
