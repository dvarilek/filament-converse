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
        isDraggingFileAttachment: false,

        uploadingFileAttachments: [],

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

        scrollToBottom() {
            this.$refs.messageBoxEndMarker.scrollIntoView()
        },

        isUploadingFileAttachment() {
            return this.uploadingFileAttachments.length > 0
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
