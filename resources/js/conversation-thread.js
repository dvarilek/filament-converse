export function conversationThread({
    statePath,
    componentKey,
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
            ;['dragenter', 'dragover', 'dragleave', 'drop'].forEach(
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

            ;['dragenter', 'dragover'].forEach((eventName) => {
                this.$el.addEventListener(
                    eventName,
                    () => {
                        this.isDraggingOver = true
                    },
                    false,
                )
            })

            ;['dragleave', 'drop'].forEach((eventName) => {
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

            this.$el.addEventListener(
                'drop',
                (event) => {
                    this.isDraggingOver = false

                    const files = Array.from(
                        (event.dataTransfer && event.dataTransfer.files) || [],
                    )
                    if (files.length === 0) return

                    files.forEach((file) => this.handleUpload(file))
                },
                false,
            )
        },

        handleUpload(file) {
            if (
                fileAttachmentAcceptedFileTypes &&
                !fileAttachmentAcceptedFileTypes.includes(file.type)
            ) {
                this.updateFileAttachmentUploadFailureMessage(
                    fileAttachmentsAcceptedFileTypesMessage,
                )

                return
            }

            if (
                fileAttachmentMaxSize &&
                file.size > +fileAttachmentMaxSize * 1024
            ) {
                this.updateFileAttachmentUploadFailureMessage(
                    fileAttachmentsMaxSizeMessage,
                )

                return
            }

            $wire.upload('componentFileAttachments.' + statePath, file, () => {
                $wire
                    .callSchemaComponentMethod(
                        componentKey,
                        'saveUploadedFileAttachmentAndGetUrl',
                    )
                    .then((url) => {
                        if (!url) {
                            $wire.callSchemaComponentMethod(
                                componentKey,
                                'callAfterAttachmentUploadFailed',
                            )

                            return
                        }

                        $wire.callSchemaComponentMethod(
                            componentKey,
                            'callAfterAttachmentUploaded',
                        )
                    })
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
