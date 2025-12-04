function T({
    key: d,
    conversationKey: o,
    statePath: l,
    autoScrollOnForeignMessagesThreshold: u,
    shouldDispatchUserTypingEvent: m,
    userTypingIndicatorTimeout: p,
    userTypingEventDispatchThreshold: f,
    userTypingTranslations: i,
    fileAttachmentAcceptedFileTypes: h,
    fileAttachmentMaxSize: g,
    maxFileAttachments: c,
    fileAttachmentsAcceptedFileTypesValidationMessage: v,
    fileAttachmentsMaxSizeValidationMessage: U,
    maxFileAttachmentsValidationMessage: A,
    $wire: n,
}) {
    return {
        messagesCreatedDuringConversationSession: n.entangle(
            'messagesCreatedDuringConversationSession',
        ),
        typingUsersMap: new Map(),
        typingUserTimeouts: new Map(),
        lastUserTypingEventSentAt: null,
        isLoadingMoreMessages: !1,
        isDraggingFileAttachment: !1,
        uploadingFileAttachments: [],
        fileAttachmentUploadValidationMessage: null,
        init() {
            ;(window.Echo.private('filament-converse.conversation.' + o)
                .listen('.user.typing', (e) => {
                    let t = e.user.id
                    ;(this.typingUsersMap.set(t, e.user.name),
                        this.typingUserTimeouts.has(t) &&
                            clearTimeout(this.typingUserTimeouts.get(t)))
                    let s = setTimeout(() => {
                        ;(this.typingUsersMap.delete(t),
                            this.typingUserTimeouts.delete(t))
                    }, p)
                    this.typingUserTimeouts.set(t, s)
                })
                .listen('.message.sent', (e) =>
                    n.call(
                        'registerMessageCreatedDuringConversationSession',
                        e.message.id,
                        e.message.authorId,
                    ),
                )
                .listen('.message.deleted', (e) =>
                    n.call(
                        'registerMessageCreatedDuringConversationSession',
                        e.message.id,
                        e.message.authorId,
                        !1,
                    ),
                )
                .listen('.message.updated', (e) => n.refresh()),
                this.$watch(
                    'messagesCreatedDuringConversationSession',
                    (e, t) => {
                        let s = (r) =>
                                !r.createdByAuthenticatedUser && r.exists,
                            a = (r) => r.createdByAuthenticatedUser && r.exists,
                            y = Object.values(e).filter(s).length,
                            M = Object.values(t).filter(s).length,
                            C = Object.values(e).filter(a).length,
                            F = Object.values(t).filter(a).length
                        ;(y > M &&
                            this.isPositionedNearBottom() &&
                            this.$nextTick(() =>
                                this.scrollToBottom({ behaviour: 'smooth' }),
                            ),
                            C > F &&
                                this.$nextTick(() =>
                                    this.scrollToBottom({
                                        behaviour: 'smooth',
                                    }),
                                ))
                    },
                ),
                this.registerFileAttachmentUploadEventListeners())
        },
        areOtherUsersTyping() {
            return this.typingUsersMap.size > 0
        },
        getTypingUsersMessage() {
            let e = Array.from(this.typingUsersMap.values())
            if (e.length === 0 || i.length === 0) return ''
            if (e.length === 1) return i.single.replace('{singleName}', e[0])
            if (e.length === 2)
                return i.double
                    .replace('{firstName}', e[0])
                    .replace('{secondName}', e[1])
            let t = e.length - 2,
                s = t === 1 ? i.other : i.others
            return i.multiple
                .replace('{firstName}', e[0])
                .replace('{secondName}', e[1])
                .replace('{count}', t)
                .replace('{others}', s)
        },
        scrollToBottom(e) {
            this.$refs.conversationThreadContentEndMarker.scrollIntoView(e)
        },
        async fireUserTypingEvent(e) {
            let t = e.target.value
            if (!t || t.trim() === '' || !m) return
            let s = Date.now()
            ;(this.lastUserTypingEventSentAt &&
                s - this.lastUserTypingEventSentAt < f) ||
                ((this.lastUserTypingEventSentAt = s),
                await n.callSchemaComponentMethod(
                    d,
                    'broadcastUserTypingEvent',
                ))
        },
        async loadMoreMessages() {
            let e = this.$refs.conversationThreadContent,
                t = e.scrollHeight
            this.isLoadingMoreMessages = !0
            try {
                await n.call('incrementActiveConversationMessagesPage')
            } finally {
                ;((this.isLoadingMoreMessages = !1),
                    (e.scrollTop += e.scrollHeight - t))
            }
        },
        isPositionedNearBottom() {
            let e = this.$refs.conversationThreadContent
            return e.scrollHeight - e.scrollTop - e.clientHeight < (u ?? 0)
        },
        isUploadingFileAttachment() {
            return this.uploadingFileAttachments.length > 0
        },
        registerFileAttachmentUploadEventListeners() {
            ;(new Set(['dragenter', 'dragover', 'dragleave', 'drop']).forEach(
                (e) =>
                    this.$el.addEventListener(
                        e,
                        (t) => {
                            ;(t.preventDefault(), t.stopPropagation())
                        },
                        !1,
                    ),
            ),
                new Set(['dragenter', 'dragover']).forEach((e) => {
                    this.$el.addEventListener(
                        e,
                        () => {
                            this.isDraggingFileAttachment = !0
                        },
                        !1,
                    )
                }),
                this.$el.addEventListener('dragleave', (e) => {
                    this.$el.contains(e.relatedTarget) ||
                        (this.isDraggingFileAttachment = !1)
                }),
                this.$el.addEventListener('drop', async (e) => {
                    ;((this.isDraggingFileAttachment = !1),
                        await this.handleAttachmentUpload(
                            (e.dataTransfer && e.dataTransfer.files) || [],
                        ))
                }))
        },
        async handleAttachmentUpload(e) {
            if (!e.length) return
            this.fileAttachmentUploadValidationMessage = null
            let t = null
            if (
                c &&
                Array.from((await n.get('componentFileAttachments.' + l)) || [])
                    .length +
                    e.length >
                    c
            ) {
                this.fileAttachmentUploadValidationMessage = A
                return
            }
            let s = Array.from(e).filter((a) =>
                h && !h.includes(a.type)
                    ? ((t = v), !1)
                    : g && a.size > +g * 1024
                      ? ((t = U), !1)
                      : !0,
            )
            ;(t && (this.fileAttachmentUploadValidationMessage = t),
                s.length !== 0 &&
                    (this.uploadingFileAttachments.push(...s),
                    await n.uploadMultiple(
                        'componentFileAttachments.' + l + '.' + o,
                        s,
                        () => (this.uploadingFileAttachments = []),
                        () => (this.uploadingFileAttachments = []),
                    )))
        },
    }
}
export { T as conversationThread }
