export function conversationThread({
                                       key,
                                       conversationKey,
                                       statePath,
                                       autoScrollOnForeignMessagesThreshold,
                                       shouldDispatchUserTypingEvent,
                                       userTypingIndicatorTimeout,
                                       userTypingEventDispatchThreshold,
                                       fileAttachmentAcceptedFileTypes,
                                       fileAttachmentMaxSize,
                                       maxFileAttachments,
                                       fileAttachmentsAcceptedFileTypesValidationMessage,
                                       fileAttachmentsMaxSizeValidationMessage,
                                       maxFileAttachmentsValidationMessage,
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

        isDraggingFileAttachment: false,

        uploadingFileAttachments: [],

        fileAttachmentUploadValidationMessage: null,

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
                .listen('.message.updated', (event) => $wire.refresh())

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

            this.registerFileAttachmentUploadEventListeners()
        },

        areOtherUsersTyping() {
            return this.typingUsersMap.size > 0
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

        registerFileAttachmentUploadEventListeners() {
            new Set(['dragenter', 'dragover', 'dragleave', 'drop']).forEach(
                (eventName) =>
                    this.$el.addEventListener(
                        eventName,
                        (event) => {
                            event.preventDefault()
                            event.stopPropagation()
                        },
                        false,
                    ),
            )

            new Set(['dragenter', 'dragover']).forEach((eventName) => {
                this.$el.addEventListener(
                    eventName,
                    () => {
                        this.isDraggingFileAttachment = true
                    },
                    false,
                )
            })

            this.$el.addEventListener('dragleave', (event) => {
                if (!this.$el.contains(event.relatedTarget)) {
                    this.isDraggingFileAttachment = false
                }
            })

            this.$el.addEventListener('drop', async (event) => {
                this.isDraggingFileAttachment = false

                await this.handleAttachmentUpload(
                    (event.dataTransfer && event.dataTransfer.files) || [],
                )
            })
        },

        async handleAttachmentUpload(files) {
            if (!files.length) {
                return
            }

            this.fileAttachmentUploadValidationMessage = null
            let fileAttachmentUploadValidationMessage = null

            if (maxFileAttachments) {
                const uploadedFileAttachmentsCount = Array.from(
                    (await $wire.get(
                        'componentFileAttachments.' + statePath,
                    )) || [],
                ).length

                if (
                    uploadedFileAttachmentsCount + files.length >
                    maxFileAttachments
                ) {
                    this.fileAttachmentUploadValidationMessage =
                        maxFileAttachmentsValidationMessage

                    return
                }
            }

            const validFiles = Array.from(files).filter((file) => {
                if (
                    fileAttachmentAcceptedFileTypes &&
                    !fileAttachmentAcceptedFileTypes.includes(file.type)
                ) {
                    fileAttachmentUploadValidationMessage =
                        fileAttachmentsAcceptedFileTypesValidationMessage

                    return false
                }

                if (
                    fileAttachmentMaxSize &&
                    file.size > +fileAttachmentMaxSize * 1024
                ) {
                    fileAttachmentUploadValidationMessage =
                        fileAttachmentsMaxSizeValidationMessage

                    return false
                }

                return true
            })

            if (fileAttachmentUploadValidationMessage) {
                this.fileAttachmentUploadValidationMessage =
                    fileAttachmentUploadValidationMessage
            }

            if (validFiles.length === 0) {
                return
            }

            this.uploadingFileAttachments.push(...validFiles)

            await $wire.uploadMultiple(
                'componentFileAttachments.' + statePath,
                validFiles,
                () => (this.uploadingFileAttachments = []),
                () => (this.uploadingFileAttachments = []),
            )
        },
    }
}
