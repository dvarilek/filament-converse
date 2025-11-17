export function conversationThread({
    statePath,
    fileAttachmentAcceptedFileTypes,
    fileAttachmentMaxSize,
    fileAttachmentsAcceptedFileTypesMessage,
    fileAttachmentsMaxSizeMessage,
    $wire,
}) {
    return {
        isDraggingOver: false,

        fileAttachmentUploadFailureMessage: null,

        init() {
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
                        this.isDraggingOver = true
                    },
                    false,
                )
            })

            new Set(['dragleave', 'drop']).forEach((eventName) => {
                this.$el.addEventListener(
                    eventName,
                    (event) => {
                        if (!this.$el.contains(event.relatedTarget)) {
                            this.isDraggingOver = false
                        }
                    },
                    false,
                )
            })

            this.$el.addEventListener('drop', (event) => {
                this.isDraggingOver = false

                let fileAttachmentUploadFailureMessage = null

                const files = Array.from(
                    (event.dataTransfer && event.dataTransfer.files) || [],
                ).filter((file) => {
                    if (
                        fileAttachmentAcceptedFileTypes &&
                        !fileAttachmentAcceptedFileTypes.includes(file.type)
                    ) {
                        fileAttachmentUploadFailureMessage =
                            fileAttachmentsAcceptedFileTypesMessage

                        return false
                    }

                    if (
                        fileAttachmentMaxSize &&
                        file.size > +fileAttachmentMaxSize * 1024
                    ) {
                        fileAttachmentUploadFailureMessage =
                            fileAttachmentsMaxSizeMessage

                        return false
                    }

                    return true
                })

                if (fileAttachmentUploadFailureMessage) {
                    this.updateFileAttachmentUploadFailureMessage(
                        fileAttachmentUploadFailureMessage,
                    )
                }

                if (files.length === 0) {
                    return
                }

                $wire.uploadMultiple(
                    'componentFileAttachments.' + statePath,
                    files,
                )
            })
        },

        updateFileAttachmentUploadFailureMessage(message) {
            this.fileAttachmentUploadFailureMessage = message

            setTimeout(() => {
                this.fileAttachmentUploadFailureMessage = null
            }, 5000)
        },
    }
}
