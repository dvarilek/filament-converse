function g({
    statePath: l,
    fileAttachmentAcceptedFileTypes: t,
    fileAttachmentMaxSize: r,
    fileAttachmentsAcceptedFileTypesMessage: n,
    fileAttachmentsMaxSizeMessage: d,
    $wire: o,
}) {
    return {
        isDraggingOver: !1,
        fileAttachmentUploadFailureMessage: null,
        init() {
            ;(new Set(['dragenter', 'dragover', 'dragleave', 'drop']).forEach(
                (e) =>
                    this.$el.addEventListener(
                        e,
                        (a) => {
                            ;(a.preventDefault(), a.stopPropagation())
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
                new Set(['dragleave', 'drop']).forEach((e) => {
                    this.$el.addEventListener(
                        e,
                        (a) => {
                            this.$el.contains(a.relatedTarget) ||
                                (this.isDraggingOver = !1)
                        },
                        !1,
                    )
                }),
                this.$el.addEventListener('drop', (e) => {
                    this.isDraggingOver = !1
                    let a = null,
                        s = Array.from(
                            (e.dataTransfer && e.dataTransfer.files) || [],
                        ).filter((i) =>
                            t && !t.includes(i.type)
                                ? ((a = n), !1)
                                : r && i.size > +r * 1024
                                  ? ((a = d), !1)
                                  : !0,
                        )
                    ;(a && this.updateFileAttachmentUploadFailureMessage(a),
                        s.length !== 0 &&
                            o.uploadMultiple(
                                'componentFileAttachments.' + l,
                                s,
                            ))
                }))
        },
        updateFileAttachmentUploadFailureMessage(e) {
            ;((this.fileAttachmentUploadFailureMessage = e),
                setTimeout(() => {
                    this.fileAttachmentUploadFailureMessage = null
                }, 5e3))
        },
    }
}
export { g as conversationThread }
