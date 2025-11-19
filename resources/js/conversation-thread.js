export function conversationThread({
    statePath,
    fileAttachmentAcceptedFileTypes,
    fileAttachmentMaxSize,
    maxFileAttachments,
    fileAttachmentsAcceptedFileTypesValidationMessage,
    fileAttachmentsMaxSizeValidationMessage,
    maxFileAttachmentsValidationMessage,
    $wire,
}) {
    return {
        isDraggingOver: false,

        isFileAttachmentUploading: false,

        isFileAttachmentSuccessfullyUploaded: false,

        fileAttachmentUploadValidationMessage: null,

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

            this.$el.addEventListener('dragleave', (event) => {
                if (!this.$el.contains(event.relatedTarget)) {
                    this.isDraggingOver = false
                }
            })

            this.$el.addEventListener('drop', async (event) => {
                this.isDraggingOver = false

                console.log('drop', event)

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

            this.isFileAttachmentUploading = true

            await $wire.uploadMultiple(
                'componentFileAttachments.' + statePath,
                validFiles,
                () => {
                    this.isFileAttachmentSuccessfullyUploaded = true
                    this.isFileAttachmentUploading = false

                    setTimeout(
                        () =>
                            (this.isFileAttachmentSuccessfullyUploaded = false),
                        1500,
                    )
                },
                () => (this.isFileAttachmentUploading = false),
            )
        },
    }
}
