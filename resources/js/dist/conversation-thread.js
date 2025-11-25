function c({
    statePath: i,
    fileAttachmentAcceptedFileTypes: l,
    fileAttachmentMaxSize: s,
    maxFileAttachments: r,
    fileAttachmentsAcceptedFileTypesValidationMessage: d,
    fileAttachmentsMaxSizeValidationMessage: h,
    maxFileAttachmentsValidationMessage: g,
    $wire: o,
}) {
    return {
        isDraggingFileAttachment: !1,
        uploadingFileAttachments: [],
        fileAttachmentUploadValidationMessage: null,
        init() {
            ;(new Set(['dragenter', 'dragover', 'dragleave', 'drop']).forEach(
                (t) =>
                    this.$el.addEventListener(
                        t,
                        (e) => {
                            ;(e.preventDefault(), e.stopPropagation())
                        },
                        !1,
                    ),
            ),
                new Set(['dragenter', 'dragover']).forEach((t) => {
                    this.$el.addEventListener(
                        t,
                        () => {
                            this.isDraggingFileAttachment = !0
                        },
                        !1,
                    )
                }),
                this.$el.addEventListener('dragleave', (t) => {
                    this.$el.contains(t.relatedTarget) ||
                        (this.isDraggingFileAttachment = !1)
                }),
                this.$el.addEventListener('drop', async (t) => {
                    ;((this.isDraggingFileAttachment = !1),
                        await this.handleAttachmentUpload(
                            (t.dataTransfer && t.dataTransfer.files) || [],
                        ))
                }))
        },
        scrollToBottom(t) {
            this.$refs.messageBoxEndMarker.scrollIntoView(t)
        },
        isUploadingFileAttachment() {
            return this.uploadingFileAttachments.length > 0
        },
        async handleAttachmentUpload(t) {
            if (!t.length) return
            this.fileAttachmentUploadValidationMessage = null
            let e = null
            if (
                r &&
                Array.from((await o.get('componentFileAttachments.' + i)) || [])
                    .length +
                    t.length >
                    r
            ) {
                this.fileAttachmentUploadValidationMessage = g
                return
            }
            let a = Array.from(t).filter((n) =>
                l && !l.includes(n.type)
                    ? ((e = d), !1)
                    : s && n.size > +s * 1024
                      ? ((e = h), !1)
                      : !0,
            )
            ;(e && (this.fileAttachmentUploadValidationMessage = e),
                a.length !== 0 &&
                    (this.uploadingFileAttachments.push(...a),
                    await o.uploadMultiple(
                        'componentFileAttachments.' + i,
                        a,
                        () => (this.uploadingFileAttachments = []),
                        () => (this.uploadingFileAttachments = []),
                    )))
        },
    }
}
export { c as conversationThread }
