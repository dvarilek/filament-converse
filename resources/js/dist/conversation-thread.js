function f({
    statePath: i,
    fileAttachmentAcceptedFileTypes: l,
    fileAttachmentMaxSize: n,
    maxFileAttachments: s,
    fileAttachmentsAcceptedFileTypesValidationMessage: o,
    fileAttachmentsMaxSizeValidationMessage: h,
    maxFileAttachmentsValidationMessage: g,
    $wire: r,
}) {
    return {
        isDraggingOver: !1,
        isFileAttachmentUploading: !1,
        isFileAttachmentSuccessfullyUploaded: !1,
        fileAttachmentUploadValidationMessage: null,
        init() {
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
                            this.isDraggingOver = !0
                        },
                        !1,
                    )
                }),
                this.$el.addEventListener('dragleave', (e) => {
                    this.$el.contains(e.relatedTarget) ||
                        (this.isDraggingOver = !1)
                }),
                this.$el.addEventListener('drop', async (e) => {
                    ;((this.isDraggingOver = !1),
                        console.log('drop', e),
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
                s &&
                Array.from((await r.get('componentFileAttachments.' + i)) || [])
                    .length +
                    e.length >
                    s
            ) {
                this.fileAttachmentUploadValidationMessage = g
                return
            }
            let d = Array.from(e).filter((a) =>
                l && !l.includes(a.type)
                    ? ((t = o), !1)
                    : n && a.size > +n * 1024
                      ? ((t = h), !1)
                      : !0,
            )
            ;(t && (this.fileAttachmentUploadValidationMessage = t),
                d.length !== 0 &&
                    ((this.isFileAttachmentUploading = !0),
                    await r.uploadMultiple(
                        'componentFileAttachments.' + i,
                        d,
                        () => {
                            ;((this.isFileAttachmentSuccessfullyUploaded = !0),
                                (this.isFileAttachmentUploading = !1),
                                setTimeout(
                                    () =>
                                        (this.isFileAttachmentSuccessfullyUploaded =
                                            !1),
                                    1500,
                                ))
                        },
                        () => (this.isFileAttachmentUploading = !1),
                    )))
        },
    }
}
export { f as conversationThread }
